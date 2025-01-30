<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\client\StoreClientRequest;
use App\Http\Requests\client\UpdateClientRequest;
use App\Models\Business;
use App\Models\Client;
use App\Models\ClientFolder;
use App\Models\Company;
use App\Models\role_user;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Client::with(['user:id,first_name,middle_name,last_name,email,avatar,phone,status,allow_login'])
            ->whereHas('user', function ($query) {
                $query->where('role_id', UserRole::CLIENT->value);
            })
            ->whereNull('deleted_at');

        // Search by customer name or email
        if ($request->has('q')) {
            $searchTerm = $request->input('q');
            $query->whereHas('user', function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', "%$searchTerm%")
                    ->orWhere('last_name', 'like', "%$searchTerm%")
                    ->orWhere('email', 'like', "%$searchTerm%");
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
        $clients = $query->paginate($itemsPerPage);

        // Return structured response
        return response()->json([
            'customers' => $clients->items(),  // Customer data
            'total' => $clients->total(),      // Total number of customers
            'message' => 'success',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        DB::transaction(function () use ($request) {
            $uuid = \Illuminate\Support\Str::uuid();

            // Create the user
            $user = new User([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => true,
                'allow_login' => $request->allow_login,
                'role_id' => UserRole::CLIENT->value,
                'password' => bcrypt($request->password)
            ]);
            $user->save();

            // Assign the role
            $role_user = new role_user;
            $role_user->user_id = $user->id;
            $role_user->role_id = UserRole::CLIENT->value;
            $role_user->save();

            // Create the client
            $client = new Client([
                'uuid' => $uuid,
                'user_id' => $user->id,
                'kra_pin' => $request->kra_pin,
                'id_no' => $request->id_no,
                'post_address' => $request->post_address,
                'post_code' => $request->post_code,
                'city' => $request->city,
                'county' => $request->county,
                'country' => $request->country,
                'notes' => $request->notes,
            ]);
            $client->save();

            Log::info($client->id);

            // Create the folder using ClientFolder model
            ClientFolder::createClientFolder($client->id, $client->uuid, $user->first_name.''.$user->middle_name);
        });

        return response()->json([
            'message' => 'Successfully created client!'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
//
    public function show(Client $client)
    {
        try {
            $client->load([
                'user:id,first_name,middle_name,last_name,email,avatar,phone,status,allow_login',
                'feenotes.payments',
                'feenotes.task',
                'obligations'
            ]);

            // Fetch businesses through directors
            $businessesFromDirectors = $client->directors()
                ->whereHas('business')
                ->with('business:id,name,business_email,business_no,business_phone,registration_date')
                ->get()
                ->pluck('business')
                ->unique();

            $directBusinesses = Business::where('client_id', $client->id)
                ->select('id', 'name', 'business_email', 'business_phone', 'registration_date', 'business_no')
                ->get();

            // Fetch companies through directors
            $companiesFromDirectors = $client->directors()
                ->whereHas('company')
                ->with('company:id,name,phone,reg_date,reg_number')
                ->get()
                ->pluck('company')
                ->unique();

            // Fetch companies directly related to the client
            $directCompanies = Company::where('client_id', $client->id)
                ->select('id', 'name', 'phone', 'reg_date', 'reg_number')
                ->get();

            // Merge companies from both sources and remove duplicates
            $companies = $companiesFromDirectors->merge($directCompanies)->unique('id');
            $businesses = $businessesFromDirectors->merge($directBusinesses)->unique('id');

            $totalFeeNoteAmount = $client->feenotes->sum('amount');
            $totalPaidAmount = $client->feenotes->flatMap(function ($feenote) {
                return $feenote->payments;
            })->sum('amount');

            $totalOutstandingAmount = $totalFeeNoteAmount - $totalPaidAmount;

            // Map payments with details
            $payments = $client->feenotes->flatMap(function ($feenote) {
                return $feenote->payments;
            })->map(function ($payment) {
                return [
                    'amount' => $payment->amount,
                    'transaction_reference' => $payment->transaction_reference,
                    'payment_date' => $payment->created_at ? $payment->created_at->format('Y-m-d') : null,
                ];
            });

            if (is_null($client->folder_path)) {
                \Log::warning('Client ' . $client->id . ' has a null folder_path.');
            }

            // Respond with client data, totals, and payments
            return response()->json([
                'client' => $client,
                'total_fee_note_amount' => $totalFeeNoteAmount,
                'total_paid_amount' => $totalPaidAmount,
                'total_outstanding_amount' => $totalOutstandingAmount,
                'payments' => $payments,
                'businesses' => $businesses,
                'companies' => $companies,
                'message' => 'Client retrieved successfully!'
            ], 200);
        } catch (ModelNotFoundException $e) {
            \Log::error('Client not found: ' . $client->id);
            return response()->json(['error' => 'Client not found'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, $id)
    {
        try {
            $client = Client::with('user')->findOrFail($id);

            DB::transaction(function () use ($request, $client) {
                // Fetch the associated User model
                $user = $client->user;

                // Check if the user exists
                if (!$user) {
                    throw new \Exception('Associated user not found for client ID: '.$client->id);
                }

                // Update User data
                $user->update([
                    'first_name' => $request->input('first_name', $user->first_name),
                    'last_name' => $request->input('last_name', $user->last_name),
                    'middle_name' => $request->input('middle_name', $user->middle_name),
                    'phone' => $request->input('phone', $user->phone),
                    'email' => $request->input('email', $user->email),
                    'status' => $request->input('status', $user->status),
                    'allow_login' => $request->input('allow_login', $user->allow_login),
                    'password' => $request->filled('password') ? bcrypt($request->password) : $user->password,
                ]);

                // Update Client data
                $client->update([
                    'kra_pin' => $request->input('kra_pin', $client->kra_pin),
                    'id_no' => $request->input('id_no', $client->id_no),
                    'post_address' => $request->input('post_address', $client->post_address),
                    'post_code' => $request->input('post_code', $client->post_code),
                    'city' => $request->input('city', $client->city),
                    'county' => $request->input('county', $client->county),
                    'country' => $request->input('country', $client->country),
                    'notes' => $request->input('notes', $client->notes),
                ]);
            });

            return response()->json(['message' => 'Client updated successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        DB::transaction(function () use ($client) {
            // Soft delete the User and Client
            $client->user()->delete();
            $client->delete();
        });

        return response()->json([
            'message' => 'Client deleted successfully!'
        ], 200);
    }
}
