<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Business;
use App\Models\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Business::query()
            ->whereNull('deleted_at')
            ->with('client.user');

        // Search by company name or client's name
        if ($request->has('q')) {
            $searchTerm = $request->input('q');
            $query->where('name', 'like', "%$searchTerm%")
                ->orWhereHas('client.user', function ($userQuery) use ($searchTerm) {
                    $userQuery->where('first_name', 'like', "%$searchTerm%")
                        ->orWhere('last_name', 'like', "%$searchTerm%")
                        ->orWhere('middle_name', 'like', "%$searchTerm%");
                });
        }

        // Sorting
        if ($request->has('sortBy') && $request->has('orderBy')) {
            $sortBy = $request->input('sortBy');
            $orderBy = $request->input('orderBy', 'asc');
            $query->orderBy($sortBy, $orderBy);
        }

        // Pagination
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $businesses = $query->paginate($itemsPerPage);

        // Structure the data for response
        $data = $businesses->map(function ($business) {
            $client = $business->client;
            $user = $client ? $client->user : null;

            return [
                'id' => $business->id,
                'name' => $business->name,
                'business_no' => $business->business_no,
                'registration_date' => $business->registration_date,
                'business_phone' => $business->business_phone,
                'business_address' => $business->business_address,
                'business_email' => $business->business_email,
                'client_id' => $business->client_id,
                'client_name' => $user ? $user->first_name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name : 'No Client',
            ];
        });

        // Return paginated data
        return response()->json([
            'businesses' => $data->values(),  // Current page of companies
            'total' => $businesses->total(),  // Total number of companies
            'message' => 'success',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Client::with(['user:id,first_name,last_name,middle_name'])
            ->whereNull('deleted_at') // Exclude soft-deleted clients if applicable
            ->get();

        return response()->json([
            'clients' => $clients,
            'message' => 'Clients retrieved successfully',
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('Request Data:', $request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'business_no' => 'required|string|unique:businesses,business_no',
            'registration_date' => 'required|date',
            'business_phone' => 'required|string',
            'business_address' => 'required|string',
            'client_id' => 'required|exists:clients,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Convert registration_date to 'Y-m-d' format
//        $formattedDate = date('Y-m-d', strtotime($request->input('registration_date')));

        // Create the business with formatted date
        $business = Business::create([
            'name' => $request->input('name'),
            'business_no' => $request->input('business_no'),
            'registration_date' => $request->input('registration_date'),
            'business_phone' => $request->input('business_phone'),
            'business_address' => $request->input('business_address'),
            'business_email' => $request->input('business_email'),
            'client_id' => $request->input('client_id'),
        ]);

        return response()->json([
            'message' => 'Business created successfully!',
            'business' => $business,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Business $business)
    {
        try {
            $business->load([
                'client.user:id,first_name,last_name,phone,email,created_at',
                'directors.client.user', 'feenotes.payments', 'feenotes.task']);

            // Calculate totals
            $totalFeeNoteAmount = $business->feenotes->sum('amount');
            $totalPaidAmount = $business->feenotes->flatMap(function ($feenote) {
                return $feenote->payments;
            })->sum('amount');
            $totalOutstandingAmount = $totalFeeNoteAmount - $totalPaidAmount;

            // Map payments with details
            $payments = $business->feenotes->flatMap(function ($feenote) {
                return $feenote->payments;
            })->map(function ($payment) {
                return [
                    'amount' => $payment->amount,
                    'transaction_reference' => $payment->transaction_reference,
                    'payment_date' => $payment->created_at->format('Y-m-d'),
                ];
            });

            // Get unique directors
            $uniqueDirectors = $business->directors->unique('client_id')->map(function ($director) {
                return [
                    'id' => $director->id,
                    'client_id' => $director->client_id,
                    'client' => $director->client ? [
                        'id' => $director->client->id,
                        'uuid' => $director->client->uuid,
                        'user' => $director->client->user ? [
                            'id' => $director->client->user->id,
                            'first_name' => $director->client->user->first_name,
                            'middle_name' => $director->client->user->middle_name,
                            'last_name' => $director->client->user->last_name,
                            'email' => $director->client->user->email,
                            'phone' => $director->client->user->phone,
                        ] : null,
                    ] : null,
                ];
            });

            // Remove directors from the company object
            unset($business->directors);

            // Respond with business data, totals, and payments
            return response()->json([
                'business' => $business,
                'directors' => $uniqueDirectors->values(),
                'total_fee_note_amount' => $totalFeeNoteAmount,
                'total_paid_amount' => $totalPaidAmount,
                'total_outstanding_amount' => $totalOutstandingAmount,
                'payments' => $payments,
                'message' => 'Business retrieved successfully!'
            ], 200);
        } catch (ModelNotFoundException $e) {
            \Log::error('business not found: ' . $business->id);
            return response()->json(['error' => 'Business not found'], 404);
        }
    }
//        $client = $business->client;
//        $user = $client ? $client->user : null;
//
//        $data = [
//            'id' => $business->id,
//            'name' => $business->name,
//            'business_no' => $business->business_no,
//            'registration_date' => $business->registration_date,
//            'business_phone' => $business->business_phone,
//            'business_address' => $business->business_address,
//            'client_id' => $business->client_id,
//            'business_email' => $business->business_email,
//            'client_name' => $user ? $user->first_name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name : 'No Client',
//        ];
//
//        return response()->json([
//            'message' => 'Business retrieved successfully',
//            'business' => $data,
//        ], 200);
//    }

    public function edit(Business $business)
    {
        $client = $business->client;
        $user = $client ? $client->user : null;

        $data = [
            'id' => $business->id,
            'name' => $business->name,
            'business_no' => $business->business_no,
            'registration_date' => $business->registration_date,
            'business_phone' => $business->business_phone,
            'business_address' => $business->business_address,
            'client_id' => $business->client_id,
            'business_email' => $business->business_email,
            'client_name' => $user ? $user->first_name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name : 'No Client',
        ];

        $clients = Client::with(['user:id,first_name,last_name,middle_name'])
            ->whereNull('deleted_at') // Exclude soft-deleted clients if applicable
            ->get();

        return response()->json([
            'message' => 'Business edit data retrieved successfully',
            'business' => $data,
            'clients' => $clients,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Business $business)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string',
            'business_no' => 'sometimes|required|string|unique:businesses,business_no,' . $business->id,
            'registration_date' => 'sometimes|required|date',
            'business_phone' => 'sometimes|required|string',
            'business_address' => 'sometimes|required|string',
            'client_id' => 'sometimes|required|exists:clients,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        $formattedDate = date('Y-m-d', strtotime($request->input('registration_date')));

        $business->update([
            'name' => $request->input('name'),
            'business_no' => $request->input('business_no'),
            'registration_date' => $request->input('registration_date'),
            'business_phone' => $request->input('business_phone'),
            'business_address' => $request->input('business_address'),
            'business_email' => $request->input('business_email'),
            'client_id' => $request->input('client_id'),
        ]);

        return response()->json([
            'message' => 'Business updated successfully!',
            'business' => $business,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Business $business)
    {
        $business->delete();

        return response()->json([
            'message' => 'Business deleted successfully!',
        ], 200);
    }
}
