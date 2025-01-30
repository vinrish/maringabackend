<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Client;
use App\Models\Company;
use App\Models\FeeNote;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

class FeeNoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Validate request parameters for search, sorting, and pagination
        $validator = Validator::make($request->all(), [
            'q' => ['nullable', 'string'],       // Search query
            'sortBy' => ['nullable', 'string'],  // Sort field
            'orderBy' => ['nullable', 'in:asc,desc'],  // Order by asc/desc
            'itemsPerPage' => ['nullable', 'integer', 'min:1'],  // Pagination size
            'page' => ['nullable', 'integer', 'min:1'],  // Page number
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve validated input
        $searchTerm = $request->input('q');
        $sortBy = $request->input('sortBy', 'created_at'); // Default sorting by created_at
        $orderBy = $request->input('orderBy', 'asc');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);

        // Initialize the query with relationships
        $feeNotesQuery = FeeNote::with(['task', 'client.user:id,first_name,last_name', 'company', 'payments']);

        // Search functionality: allows searching by client name, company, or task title
        if ($searchTerm) {
            $feeNotesQuery->whereHas('client.user', function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', "%$searchTerm%")
                    ->orWhere('last_name', 'like', "%$searchTerm%");
            })->orWhereHas('company', function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%$searchTerm%");
            })->orWhereHas('task', function ($query) use ($searchTerm) {
                $query->where('title', 'like', "%$searchTerm%");
            });
        }

        // Sorting
        $feeNotesQuery->orderBy($sortBy, $orderBy);

        // Pagination
        $feeNotes = $feeNotesQuery->paginate($itemsPerPage, ['*'], 'page', $page);

        // Add dynamic payment_status to each fee note
        $feeNotes->getCollection()->transform(function ($feeNote) {
            $totalPayments = $feeNote->payments->sum('amount');
            $feeNote->total_paid = $totalPayments;
            $feeNote->payment_status = $totalPayments >= $feeNote->amount
                ? 'Paid'
                : ($totalPayments > 0 ? 'Partial' : 'Due');
            return $feeNote;
        });

        // Return paginated fee notes along with the total count
        return response()->json([
            'fee_notes' => $feeNotes->items(),  // Current page data
            'total' => $feeNotes->total(),      // Total number of fee notes
            'message' => 'success',
        ]);
    }
//    initial
//    public function index(Request $request)
//    {
//        // Validate request parameters for search, sorting, and pagination
//        $validator = Validator::make($request->all(), [
//            'q' => ['nullable', 'string'],       // Search query
//            'sortBy' => ['nullable', 'string'],  // Sort field
//            'orderBy' => ['nullable', 'in:asc,desc'],  // Order by asc/desc
//            'itemsPerPage' => ['nullable', 'integer', 'min:1'],  // Pagination size
//            'page' => ['nullable', 'integer', 'min:1'],  // Page number
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json(['error' => $validator->errors()->first()], 400);
//        }
//
//        // Retrieve validated input
//        $searchTerm = $request->input('q');
//        $sortBy = $request->input('sortBy', 'created_at'); // Default sorting by created_at
//        $orderBy = $request->input('orderBy', 'asc');
//        $itemsPerPage = $request->input('itemsPerPage', 10);
//        $page = $request->input('page', 1);
//
//        // Initialize the query with relationships
//        $feeNotesQuery = FeeNote::with(['task', 'client.user:id,first_name,last_name', 'company', 'payments']);
//
//        // Search functionality: allows searching by client name, company, or task title
//        if ($searchTerm) {
//            $feeNotesQuery->whereHas('client.user', function ($query) use ($searchTerm) {
//                $query->where('first_name', 'like', "%$searchTerm%")
//                    ->orWhere('last_name', 'like', "%$searchTerm%");
//            })->orWhereHas('company', function ($query) use ($searchTerm) {
//                $query->where('name', 'like', "%$searchTerm%");
//            })->orWhereHas('task', function ($query) use ($searchTerm) {
//                $query->where('title', 'like', "%$searchTerm%");
//            });
//        }
//
//        // Sorting
//        $feeNotesQuery->orderBy($sortBy, $orderBy);
//
//        // Pagination
//        $feeNotes = $feeNotesQuery->paginate($itemsPerPage, ['*'], 'page', $page);
//
//        // Return paginated fee notes along with the total count
//        return response()->json([
//            'fee_notes' => $feeNotes->items(),  // Current page data
//            'total' => $feeNotes->total(),      // Total number of fee notes
//            'message' => 'success',
//        ]);
//    }
    /**
     * Display a listing of the resource.
     */
//    public function index(Request $request)
//    {
//        // Validate request parameters for search, sorting, and pagination
//        $validator = Validator::make($request->all(), [
//            'q' => ['nullable', 'string'],       // Search query
//            'sortBy' => ['nullable', 'string'],  // Sort field
//            'orderBy' => ['nullable', 'in:asc,desc'],  // Order by asc/desc
//            'itemsPerPage' => ['nullable', 'integer', 'min:1'],  // Pagination size
//            'page' => ['nullable', 'integer', 'min:1'],  // Page number
//        ]);
//
//        if ($validator->fails()) {
//            return response()->json(['error' => $validator->errors()->first()], 400);
//        }
//
//        // Retrieve validated input
//        $searchTerm = $request->input('q');
//        $sortBy = $request->input('sortBy', 'created_at'); // Default sorting by created_at
//        $orderBy = $request->input('orderBy', 'asc');
//        $itemsPerPage = $request->input('itemsPerPage', 10);
//        $page = $request->input('page', 1);
//
//        // Initialize the query with relationships
//        $feeNotesQuery = FeeNote::with(['task', 'client.user:id,first_name,last_name', 'company', 'payments']);
//
//        // Search functionality: allows searching by client name, company, or task title
//        if ($searchTerm) {
//            $feeNotesQuery->whereHas('client.user', function ($query) use ($searchTerm) {
//                $query->where('first_name', 'like', "%$searchTerm%")
//                    ->orWhere('last_name', 'like', "%$searchTerm%");
//            })->orWhereHas('company', function ($query) use ($searchTerm) {
//                $query->where('name', 'like', "%$searchTerm%");
//            })->orWhereHas('task', function ($query) use ($searchTerm) {
//                $query->where('title', 'like', "%$searchTerm%");
//            });
//        }
//
//        // Sorting
//        $feeNotesQuery->orderBy($sortBy, $orderBy);
//
//        // Pagination
//        $feeNotes = $feeNotesQuery->get()->map(function ($feeNote) {
//            $totalPayments = $feeNote->payments->sum('amount'); // Assuming 'amount' is the field in payments
//            $feeNote->payment_status = $totalPayments >= $feeNote->amount ? 'Paid' : ($totalPayments > 0 ? 'Partial' : 'Due');
//            return $feeNote;
//        });
//
//        // Pagination logic
//        $feeNotes = new LengthAwarePaginator($feeNotes->forPage($page, $itemsPerPage), $feeNotes->count(), $itemsPerPage, $page);
//
//        // Return paginated fee notes along with the total count
//        return response()->json([
//            'fee_notes' => $feeNotes->items(),
//            'total' => $feeNotes->total(),
//            'message' => 'success',
//        ]);
//    }

    /**
     * Determine the payment status based on the total payments and fee note total amount.
     *
     * @param float $totalPayments
     * @param float $totalAmount
     * @return string
     */
    private function determinePaymentStatus($totalPayments, $totalAmount)
    {
        if ($totalPayments >= $totalAmount) {
            return 'paid';
        } elseif ($totalPayments > 0 && $totalPayments < $totalAmount) {
            return 'partial';
        } else {
            return 'due';
        }
    }

    public function summary_client(Request $request)
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'q' => ['nullable', 'string'],       // Search query
            'sortBy' => ['nullable', 'string'], // Sort field
            'orderBy' => ['nullable', 'in:asc,desc'], // Order direction
            'itemsPerPage' => ['nullable', 'integer', 'min:1'], // Items per page
            'page' => ['nullable', 'integer', 'min:1'], // Current page
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve validated input
        $searchTerm = $request->input('q');
        $sortBy = $request->input('sortBy', 'created_at'); // Default sorting
        $orderBy = $request->input('orderBy', 'asc');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);

        // Initialize the query with relationships
        $feeNotesQuery = FeeNote::with(['task', 'client.user:id,first_name,last_name', 'company', 'payments'])
            ->whereNotNull('client_id');

        // Apply search filters
        if ($searchTerm) {
            $feeNotesQuery->whereHas('client.user', function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', "%$searchTerm%")
                    ->orWhere('last_name', 'like', "%$searchTerm%");
            })->orWhereHas('company', function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%$searchTerm%");
            })->orWhereHas('task', function ($query) use ($searchTerm) {
                $query->where('title', 'like', "%$searchTerm%");
            });
        }

        // Apply sorting
        $feeNotesQuery->orderBy($sortBy, $orderBy);

        // Fetch all fee notes to group by client
        $feeNotes = $feeNotesQuery->get();

        // Group by client
        $groupedData = $feeNotes->groupBy('client_id')->map(function ($feeNotes, $clientId) {
            $client = $feeNotes->first()->client;

            if ($client) {
                $totalSum = $feeNotes->sum('amount');
                $totalPaid = $feeNotes->sum(function ($feeNote) {
                    return collect($feeNote->payments)->sum('amount');
                });
                $totalDue = $totalSum - $totalPaid;

                return [
                    'client_id' => $clientId,
                    'client_name' => $client->user->first_name . ' ' . $client->user->last_name,
                    'fee_notes' => $feeNotes->map(function ($feeNote) {
                        return [
                            'id' => $feeNote->id,
                            'amount' => $feeNote->amount,
                            'created_at' => $feeNote->created_at,
                            'task_name' => $feeNote->task->name ?? null,
                        ];
                    }),
                    'summary' => [
                        'total_sum' => $totalSum,
                        'total_paid' => $totalPaid,
                        'total_due' => $totalDue,
                    ],
                ];
            }

            return null;
        })->filter()->values();

        // Apply pagination to grouped data
        $totalClients = $groupedData->count();
        $paginatedData = $groupedData->forPage($page, $itemsPerPage);

        // Return response
        return response()->json([
            'fee_notes' => $paginatedData->values(),   // Current page of grouped data
            'total' => $totalClients,                 // Total number of grouped clients
            'pagination' => [
                'current_page' => $page,
                'last_page' => ceil($totalClients / $itemsPerPage),
                'per_page' => $itemsPerPage,
                'total' => $totalClients,
            ],
            'message' => 'success',
        ]);
    }

    public function summary_company(Request $request)
    {
        // Validate request parameters
        $validator = Validator::make($request->all(), [
            'q' => ['nullable', 'string'],       // Search query
            'sortBy' => ['nullable', 'string'], // Sort field
            'orderBy' => ['nullable', 'in:asc,desc'], // Order direction
            'itemsPerPage' => ['nullable', 'integer', 'min:1'], // Items per page
            'page' => ['nullable', 'integer', 'min:1'], // Current page
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve validated input
        $searchTerm = $request->input('q');
        $sortBy = $request->input('sortBy', 'created_at'); // Default sorting
        $orderBy = $request->input('orderBy', 'asc');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);

        // Initialize the query with relationships
        $feeNotesQuery = FeeNote::with(['task', 'client:id,name,kra_pin,email,phone,reg_number', 'company', 'payments'])
            ->whereNotNull('company_id');

        // Apply search filters
        if ($searchTerm) {
            $feeNotesQuery->whereHas('company', function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%$searchTerm%");
            })->orWhereHas('task', function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%$searchTerm%");
            });
        }

        // Apply sorting
        $feeNotesQuery->orderBy($sortBy, $orderBy);

        // Fetch all fee notes to group by client
        $feeNotes = $feeNotesQuery->get();

        // Group by client
        $groupedData = $feeNotes->groupBy('company_id')->map(function ($feeNotes, $companyId) {
            $company = $feeNotes->first()->company;

            if ($company) {
                $totalSum = $feeNotes->sum('amount');
                $totalPaid = $feeNotes->sum(function ($feeNote) {
                    return collect($feeNote->payments)->sum('amount');
                });
                $totalDue = $totalSum - $totalPaid;

                return [
                    'company_id' => $companyId,
                    'company_name' => $company->name,
                    'fee_notes' => $feeNotes->map(function ($feeNote) {
                        return [
                            'id' => $feeNote->id,
                            'amount' => $feeNote->amount,
                            'created_at' => $feeNote->created_at,
                            'task_name' => $feeNote->task->name ?? null,
                        ];
                    }),
                    'summary' => [
                        'total_sum' => $totalSum,
                        'total_paid' => $totalPaid,
                        'total_due' => $totalDue,
                    ],
                ];
            }

            return null;
        })->filter()->values();

        // Apply pagination to grouped data
        $totalClients = $groupedData->count();
        $paginatedData = $groupedData->forPage($page, $itemsPerPage);

        // Return response
        return response()->json([
            'fee_notes' => $paginatedData->values(),   // Current page of grouped data
            'total' => $totalClients,                 // Total number of grouped clients
            'pagination' => [
                'current_page' => $page,
                'last_page' => ceil($totalClients / $itemsPerPage),
                'per_page' => $itemsPerPage,
                'total' => $totalClients,
            ],
            'message' => 'success',
        ]);
    }

    public function show_summary_client(Request $request, $clientId)
    {
        // Validate the request (optional, depending on your use case)
        $validator = Validator::make($request->all(), [
            'q' => ['nullable', 'string'],       // Search query
            'sortBy' => ['nullable', 'string'],  // Sort field
            'orderBy' => ['nullable', 'in:asc,desc'],  // Order by asc/desc
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $searchTerm = $request->input('q');
        $sortBy = $request->input('sortBy', 'created_at'); // Default sorting by created_at
        $orderBy = $request->input('orderBy', 'asc');

        // Query fee notes for the specific client ID with relationships
        $feeNotesQuery = FeeNote::with(['task', 'client.user:id,first_name,last_name,email,phone', 'company', 'payments'])
            ->where('client_id', $clientId);

        // Apply search functionality (optional)
        if ($searchTerm) {
            $feeNotesQuery->whereHas('task', function ($query) use ($searchTerm) {
                $query->where('title', 'like', "%$searchTerm%");
            })->orWhereHas('company', function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%$searchTerm%");
            });
        }

        // Apply sorting
        $feeNotesQuery->orderBy($sortBy, $orderBy);

        $feeNotes = $feeNotesQuery->get();

        // Calculate total amounts
        $totalSum = $feeNotes->sum('amount');
        $totalPaid = $feeNotes->sum(function ($feeNote) {
            return $feeNote->payments->sum('amount');
        });
        $totalDue = $totalSum - $totalPaid;

        // Prepare response for the client
        $client = $feeNotes->first()->client ?? null;

        if (!$client) {
            return response()->json(['error' => 'Client not found'], 404);
        }

        $response = [
            'client_id' => $clientId,
            'client_name' => $client->user->first_name . ' ' . $client->user->last_name,
            'kra_pin' => $client->kra_pin,
            'phone' => $client->user->phone,
            'email' => $client->user->email,
            'fee_notes' => $feeNotes->map(function ($feeNote) {
                return [
                    'id' => $feeNote->id,
                    'amount' => $feeNote->amount,
                    'created_at' => $feeNote->created_at,
                    'fee_name' => $feeNote->task->name,
                    'paid' => $feeNote->payments->sum('amount'),
                    'balance' => ($feeNote->amount)-($feeNote->payments->sum('amount')),
                ];
            }),
            'summary' => [
                'total_sum' => $totalSum,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
            ],
        ];

        return response()->json($response, 200);
    }

    public function show_summary_company(Request $request, $companyId)
    {
        // Validate the request (optional, depending on your use case)
        $validator = Validator::make($request->all(), [
            'q' => ['nullable', 'string'],       // Search query
            'sortBy' => ['nullable', 'string'],  // Sort field
            'orderBy' => ['nullable', 'in:asc,desc'],  // Order by asc/desc
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $searchTerm = $request->input('q');
        $sortBy = $request->input('sortBy', 'created_at'); // Default sorting by created_at
        $orderBy = $request->input('orderBy', 'asc');

        // Query fee notes for the specific client ID with relationships
        $feeNotesQuery = FeeNote::with(['task', 'company:id,name,kra_pin,phone,reg_number,email', 'payments'])
            ->where('company_id', $companyId);

        // Apply search functionality (optional)
        if ($searchTerm) {
            $feeNotesQuery->whereHas('task', function ($query) use ($searchTerm) {
                $query->where('name', 'like', "%$searchTerm%");
            });
        }

        // Apply sorting
        $feeNotesQuery->orderBy($sortBy, $orderBy);

        $feeNotes = $feeNotesQuery->get();

        // Calculate total amounts
        $totalSum = $feeNotes->sum('amount');
        $totalPaid = $feeNotes->sum(function ($feeNote) {
            return $feeNote->payments->sum('amount');
        });
        $totalDue = $totalSum - $totalPaid;

        // Prepare response for the client
        $company = $feeNotes->first()->company ?? null;

        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $response = [
            'company_id' => $companyId,
            'company_name' => $company->name,
            'kra_pin' => $company->kra_pin,
            'phone' => $company->phone,
            'reg_number' => $company->reg_number,
            'email' => $company->email,
            'fee_notes' => $feeNotes->map(function ($feeNote) {
                return [
                    'id' => $feeNote->id,
                    'amount' => $feeNote->amount,
                    'created_at' => $feeNote->created_at,
                    'fee_name' => $feeNote->task->name,
                ];
            }),
            'summary' => [
                'total_sum' => $totalSum,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
            ],
        ];

        return response()->json($response, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Client::select('id', 'user_id')->with('user:id,first_name,last_name')->get();
        $tasks = Task::select('id', 'name')->get();
        $companies = Company::select('id', 'name')->get();
        $businesses = Business::select('id', 'name')->get();

        return response()->json([
            'clients' => $clients,
            'tasks' => $tasks,
            'companies' => $companies,
            'businesses' => $businesses,
            'message' => 'success'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'client_id' => 'required|exists:clients,id',
            'company_id' => 'nullable|exists:companies,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|boolean',
        ]);

        $feeNote = FeeNote::create($request->all());

        return response()->json(['message' => 'Fee note created successfully!', 'feeNote' => $feeNote], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FeeNote $feeNote)
    {
        return response()->json($feeNote->load(['task', 'client', 'company']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FeeNote $feeNote)
    {
        $clients = Client::select('id', 'user_id')->with('user:id,first_name,last_name')->get();
        $tasks = Task::select('id', 'name')->get();
        $companies = Company::select('id', 'name')->get();
        $businesses = Business::select('id', 'name')->get();

        return response()->json([
            'fee_note' => $feeNote->load(['task', 'client.user', 'company']),
            'clients' => $clients,
            'tasks' => $tasks,
            'companies' => $companies,
            'businesses' => $businesses,
            'message' => 'success'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FeeNote $feeNote)
    {
        // Validate incoming request data
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'client_id' => 'required|exists:clients,id',
            'company_id' => 'nullable|exists:companies,id',
            'amount' => 'required|numeric|min:0',
            'status' => 'required|boolean',
        ]);

        // Update the fee note with the validated data
        $feeNote->update($request->all());

        return response()->json(['message' => 'Fee note updated successfully!', 'feeNote' => $feeNote]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FeeNote $feeNote)
    {
        $feeNote->delete();

        return response()->json(['message' => 'Fee note deleted successfully!']);
    }

    /**
     * Get a summary of fee notes for a specific user.
     *
     * @param  Request  $request
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary(Request $request, $userId)
    {
        // Validate the user ID
        $validator = Validator::make(['userId' => $userId], [
            'userId' => ['required', 'integer', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Calculate the total amounts for the user's fee notes
        $summary = FeeNote::whereHas('client', function ($query) use ($userId) {
            $query->where('user_id', $userId); // Adjust based on your relation
        })->selectRaw('SUM(amount) as total_sum, SUM(paid_amount) as total_paid')
            ->first();

        $totalDue = $summary->total_sum - $summary->total_paid;

        return response()->json([
            'total_sum' => $summary->total_sum,
            'total_paid' => $summary->total_paid,
            'total_due' => $totalDue,
            'message' => 'Summary retrieved successfully',
        ]);
    }

    /**
     * Show the details of fee notes for a specific user.
     *
     * @param  Request  $request
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function showDetails(Request $request, $userId)
    {
        // Validate the user ID
        $validator = Validator::make(['userId' => $userId], [
            'userId' => ['required', 'integer', 'exists:users,id'], // Assuming you have a users table
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve fee notes with their respective amounts and creation dates
        $feeNotes = FeeNote::whereHas('client', function ($query) use ($userId) {
            $query->where('user_id', $userId); // Adjust based on your relation
        })->get(['id', 'amount', 'created_at']);

        return response()->json([
            'fee_notes' => $feeNotes,
            'message' => 'Fee notes retrieved successfully',
        ]);
    }
}
