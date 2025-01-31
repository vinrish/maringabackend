<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
//        $this->authorize('viewAny', Company::class);
//        $user = auth()->user(); // Get the currently logged-in user
        $query = Company::query()->whereNull('deleted_at')->with('client.user');

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
        $companies = $query->paginate($itemsPerPage);

        // Structure the data for response
        $data = $companies->map(function ($company) {
            $client = $company->client;
            $user = $client ? $client->user : null;

            return [
                'id' => $company->id,
                'name' => $company->name,
                'reg_number' => $company->reg_number,
                'reg_date' => $company->reg_date,
                'phone' => $company->phone,
                'address' => $company->address,
                'kra_pin' => $company->kra_pin,
                'fiscal_year' => $company->fiscal_year,
                'client_id' => $company->client_id,
                'client_name' => $user ? $user->first_name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name : 'No Client',
            ];
        });

        // Return paginated data
        return response()->json([
            'companies' => $data->values(),  // Current page of companies
            'total' => $companies->total(),  // Total number of companies
            'message' => 'success',
        ]);
    }
//    public function index(Request $request)
//    {
//        $query = Company::query()
//            ->whereNull('deleted_at')
//            ->with('client.user');
//
//        // Search by company name or client's name
//        if ($request->has('q')) {
//            $searchTerm = $request->input('q');
//            $query->where('name', 'like', "%$searchTerm%")
//                ->orWhereHas('client.user', function ($userQuery) use ($searchTerm) {
//                    $userQuery->where('first_name', 'like', "%$searchTerm%")
//                        ->orWhere('last_name', 'like', "%$searchTerm%");
//                });
//        }
//
//        // Sorting
//        if ($request->has('sortBy') && $request->has('orderBy')) {
//            $sortBy = $request->input('sortBy');
//            $orderBy = $request->input('orderBy', 'asc');
//            $query->orderBy($sortBy, $orderBy);
//        }
//
//        // Pagination
//        $itemsPerPage = $request->input('itemsPerPage', 10);
//        $companies = $query->paginate($itemsPerPage);
//
//        // Structure the data for response
//        $data = $companies->map(function ($company) {
//            $client = $company->client;
//            $user = $client ? $client->user : null;
//
//            return [
//                'id' => $company->id,
//                'name' => $company->name,
//                'reg_number' => $company->reg_number,
//                'reg_date' => $company->reg_date,
//                'phone' => $company->phone,
//                'address' => $company->address,
//                'kra_pin' => $company->kra_pin,
//                'fiscal_year' => $company->fiscal_year,
//                'client_id' => $company->client_id,
//                'client_name' => $user ? $user->first_name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name : 'No Client',
//            ];
//        });
//
//        // Return paginated data
//        return response()->json([
//            'companies' => $data->values(),  // Current page of companies
//            'total' => $companies->total(),  // Total number of companies
//            'message' => 'success',
//        ]);
//    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
//        $this->authorize('create', Company::class);
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
//        $this->authorize('create', Company::class);
        // Validate the incoming request
        $request->validate([
            'name' => 'required|string',
            'logo' => 'sometimes|required|string',
            'city' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string',
            'email' => 'sometimes|required|string|email|unique:companies',
            'county' => 'sometimes|required|string',
            'country' => 'sometimes|required|string',
            'address' => 'sometimes|required|string',
            'postal_code' => 'sometimes|required|string',
            'website' => 'nullable|url',
            'kra_pin' => 'sometimes|required|string|unique:companies',
            'fiscal_year' => 'nullable|string',
//            'revenue' => 'sometimes|numeric',
            'employees' => 'sometimes|string',
            'industry' => 'sometimes|string',
            'reg_date' => 'sometimes|required|date',
            'reg_number' => 'sometimes|required|string|unique:companies',
            'notes' => 'sometimes|string',
            'client_id' => 'required|integer|exists:clients,id', // Validate client ID
        ]);

        try {
            DB::transaction(function () use ($request) {
                $uuid = \Illuminate\Support\Str::uuid();

                // Create the company
                $company = new Company([
                    'uuid' => $uuid,
                    'name' => $request->name,
                    'logo' => $request->logo,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'city' => $request->city,
                    'country' => $request->country,
                    'county' => $request->county,
                    'postal_code' => $request->postal_code,
                    'website' => $request->website,
                    'kra_pin' => $request->kra_pin,
                    'fiscal_year' => $request->fiscal_year,
                    'revenue' => $request->revenue,
                    'employees' => $request->employees,
                    'industry' => $request->industry,
                    'notes' => $request->notes,
                    'reg_date' => $request->reg_date,
                    'reg_number' => $request->reg_number,
                    'client_id' => $request->client_id, // Associate with the client
                ]);
                $company->save();
            }, 10);

            return response()->json([
                'message' => 'Successfully created company!'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create company.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company)
    {
//        $this->authorize('view', $company);
        try {
            $company->load([
                'client.user:id,first_name,last_name,phone,email,created_at',
                'directors.client.user', 'feenotes.payments', 'feenotes.task']);

            // Calculate totals
            $totalFeeNoteAmount = $company->feenotes->sum('amount');
            $totalPaidAmount = $company->feenotes->flatMap(function ($feenote) {
                return $feenote->payments;
            })->sum('amount');
            $totalOutstandingAmount = $totalFeeNoteAmount - $totalPaidAmount;

            // Map payments with details
            $payments = $company->feenotes->flatMap(function ($feenote) {
                return $feenote->payments;
            })->map(function ($payment) {
                return [
                    'amount' => $payment->amount,
                    'transaction_reference' => $payment->transaction_reference,
                    'payment_date' => $payment->created_at->format('Y-m-d'),
                ];
            });

            // Get unique directors
            $uniqueDirectors = $company->directors->unique('client_id')->map(function ($director) {
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
            unset($company->directors);

            // Respond with company data, totals, and payments
            return response()->json([
                'company' => $company,
                'directors' => $uniqueDirectors->values(),
                'total_fee_note_amount' => $totalFeeNoteAmount,
                'total_paid_amount' => $totalPaidAmount,
                'total_outstanding_amount' => $totalOutstandingAmount,
                'payments' => $payments,
                'message' => 'Company retrieved successfully!'
            ], 200);
        } catch (ModelNotFoundException $e) {
            \Log::error('Company not found: ' . $company->id);
            return response()->json(['error' => 'Company not found'], 404);
        }
    }
//    public function show(Company $company)
//    {
//        $client = $company->client()->with('user:id,first_name,last_name')->first();
//        $user = $client ? $client->user : null;
//
//        return response()->json([
//            'company' => [
//                'id' => $company->id,
//                'name' => $company->name,
//                'reg_number' => $company->reg_number,
//                'reg_date' => $company->reg_date,
//                'phone' => $company->phone,
//                'address' => $company->address,
//                'city' => $company->city,
//                'county' => $company->county,
//                'country' => $company->country,
//                'postal_code' => $company->postal_code,
//                'email' => $company->email,
//                'kra_pin' => $company->kra_pin,
//                'fiscal_year' => $company->fiscal_year,
//                'revenue' => $company->revenue,
//                'employees' => $company->employees,
//                'industry' => $company->industry,
//                'notes' => $company->notes,
//                'client_id' => $company->client_id,
//                'client_name' => $user ? $user->first_name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name : 'No Client',
//            ],
//            'message' => 'Company retrieved successfully',
//        ], 200);
//    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Company $company)
    {
//        $this->authorize('update', $company);
        // Retrieve the client with the related user details
        $client = $company->client()->with('user:id,first_name,last_name,middle_name')->first();
        $user = $client ? $client->user : null;

        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'city' => $company->city,
                'county' => $company->county,
                'country' => $company->country,
                'postal_code' => $company->postal_code,
                'website' => $company->website,
                'kra_pin' => $company->kra_pin,
                'fiscal_year' => $company->fiscal_year,
                'revenue' => $company->revenue,
                'employees' => $company->employees,
                'industry' => $company->industry,
                'notes' => $company->notes,
                'reg_date' => $company->reg_date,
                'reg_number' => $company->reg_number,
                'client_id' => $company->client_id,
                'client_name' => $user ? $user->first_name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name : 'No Client',
            ],
            'clients' => Client::with('user:id,first_name,middle_name,last_name')->whereNull('deleted_at')->get(),
            'message' => 'Company and clients retrieved successfully for editing',
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company)
    {
//        $this->authorize('update', $company);
        // Validate the incoming request
        $request->validate([
            'name' => 'sometimes|required|string',
            'logo' => 'sometimes|string',
            'city' => 'sometimes|required|string',
            'phone' => 'sometimes|required|string',
            'email' => [
                'sometimes',
                'nullable',
                'email',
                // Rule::unique('companies', 'email')->ignore($company->id),
            ],
            'county' => 'sometimes|required|string',
            'country' => 'nullable|string',
            'address' => 'nullable|required|string',
            'postal_code' => 'sometimes|required|string',
            'website' => 'nullable|url',
            // 'kra_pin' => [
            //     'required',
            //     'string',
            //     Rule::unique('companies', 'kra_pin')->ignore($company->id),
            // ],
            'fiscal_year' => 'nullable|string',
            'revenue' => 'nullable|numeric',
            'employees' => 'sometimes|required|string',
            'industry' => 'sometimes|string',
            'reg_date' => 'sometimes|required|date',
            // 'reg_number' => 'sometimes|required|string|unique:companies,reg_number,' . $company->id,
            'notes' => 'nullable|string',
            'client_id' => 'sometimes|integer|exists:clients,id', // Validate client ID
        ]);

        try {
            DB::transaction(function () use ($request, $company) {
                $company->update([
                    'name' => $request->name,
                    'logo' => $request->logo,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'city' => $request->city,
                    'country' => $request->country,
                    'county' => $request->county,
                    'postal_code' => $request->postal_code,
                    'website' => $request->website,
                    'kra_pin' => $request->kra_pin,
                    'fiscal_year' => $request->fiscal_year,
                    'revenue' => $request->revenue,
                    'employees' => $request->employees,
                    'industry' => $request->industry,
                    'notes' => $request->notes,
                    'reg_date' => $request->reg_date,
                    'reg_number' => $request->reg_number,
                    'client_id' => $request->client_id, // Update client association if provided
                ]);
            }, 10);

            return response()->json([
                'message' => 'Successfully updated company!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update company.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Log::info('Company ID to be deleted:', ['id' => $id]);

        // Find the company or fail
        $company = Company::with('employees')->find($id);
        $this->authorize('delete', $company);

        if (!$company) {
            Log::error('Company not found:', ['id' => $id]);
            return response()->json(['error' => 'Company not found'], 404);
        }

        try {
            // Begin a database transaction
            DB::transaction(function () use ($company) {
                $employees = $company->employees;

                // Ensure employees is a collection
                if ($employees instanceof \Illuminate\Support\Collection && $employees->count()) {
                    foreach ($employees as $employee) {
                        // Delete associated payrolls
                        $employee->payrolls()->delete();
                        // Delete the employee
                        $employee->delete();
                    }
                }

                // Delete the company
                $company->delete();
            });

            return response()->json([
                'message' => 'Company and associated employees successfully deleted!',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete company:', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete company.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function recycleBin(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Company::onlyTrashed()->with('client.user');

        if ($request->has('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('name', 'like', "%$searchTerm%")
                    ->orWhereHas('client.user', function ($userQuery) use ($searchTerm) {
                        $userQuery->where('first_name', 'like', "%$searchTerm%")
                            ->orWhere('last_name', 'like', "%$searchTerm%")
                            ->orWhere('middle_name', 'like', "%$searchTerm%");
                    });
            });
        }

        // Sorting
        if ($request->has('sortBy') && $request->has('orderBy')) {
            $sortBy = $request->input('sortBy');
            $orderBy = $request->input('orderBy', 'asc');
            $query->orderBy($sortBy, $orderBy);
        }

        Log::info($query->toSql(), $query->getBindings());

        // Pagination
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $companies = $query->paginate($itemsPerPage);

        // Structure the data for response
        $data = $companies->map(function ($company) {
            $client = $company->client;
            $user = $client ? $client->user : null;

            return [
                'id' => $company->id,
                'name' => $company->name,
                'reg_number' => $company->reg_number,
                'reg_date' => $company->reg_date,
                'phone' => $company->phone,
                'address' => $company->address,
                'kra_pin' => $company->kra_pin,
                'fiscal_year' => $company->fiscal_year,
                'client_id' => $company->client_id,
                'client_name' => $user ? $user->first_name . ' ' . ($user->middle_name ?? '') . ' ' . $user->last_name : 'No Client',
            ];
        });

        // Return paginated data
        return response()->json([
            'companies' => $data->values(),
            'total' => $companies->total(),
            'message' => 'success',
        ]);
    }

    public function restore($id): \Illuminate\Http\JsonResponse
    {
        try {
            // Find the soft-deleted company
            $company = Company::onlyTrashed()->find($id);

            if (!$company) {
                return response()->json(['message' => 'Company not found in recycle bin.'], 404);
            }

            // Restore the company
            $company->restore();
//            $company->employees()->restore();

            return response()->json([
                'message' => 'Company successfully restored!',
                'company' => $company
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to restore company.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

//    public function destroy($id)
//    {
//        Log::info('Company ID to be deleted:', ['id' => $id]);
//
//        // Find the company or fail
//        $company = Company::with('employees')->find($id);
//
//        if (!$company) {
//            Log::error('Company not found:', ['id' => $id]);
//            return response()->json(['error' => 'Company not found'], 404);
//        }
//
//        // Begin a database transaction
//        try {
//            DB::transaction(function () use ($company) {
//                // Load associated employees
//                $employees = $company->employees;
//
//                // Delete all employees associated with the company
//                if ($employees->count()) {
//                    foreach ($employees as $employee) {
//                        // Delete associated payrolls
//                        $employee->payrolls()->delete();
//                        // Delete the employee
//                        $employee->delete();
//                    }
//                }
//
//                // Delete the company
//                $company->delete();
//            }, 2); // 2 retries if needed
//
//            return response()->json([
//                'message' => 'Company and associated employees successfully deleted!'
//            ], 200);
//        } catch (\Exception $e) {
//            Log::error('Failed to delete company:', [
//                'id' => $id,
//                'error' => $e->getMessage(),
//            ]);
//            return response()->json([
//                'message' => 'Failed to delete company.',
//                'error' => $e->getMessage(),
//            ], 500);
//        }
//    }
}
