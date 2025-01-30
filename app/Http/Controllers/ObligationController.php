<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Client;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Obligation;
use App\Models\Service;
use App\Services\ObligationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ObligationController extends Controller
{
    protected $obligationService;

    public function __construct(ObligationService $obligationService)
    {
        $this->obligationService = $obligationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the current date and calculate relevant date ranges
        $today = Carbon::today();
        $startOfWeek = $today->copy()->startOfWeek();
        $endOfWeek = $today->copy()->endOfWeek();
        $tomorrow = $today->copy()->addDay();

        // Validate request parameters for pagination, sorting, and search
        $validator = Validator::make($request->all(), [
            'q' => ['nullable', 'string'],
            'sortBy' => ['nullable', 'string'],
            'orderBy' => ['nullable', 'in:asc,desc'],
            'itemsPerPage' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve validated parameters
        $searchTerm = $request->input('q');
        $sortBy = $request->input('sortBy', 'next_run'); // Default sorting by next_run date
        $orderBy = $request->input('orderBy', 'asc');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);

        // Initialize the query
        $obligationsQuery = Obligation::query()
            ->with(['client.user:id,first_name,last_name', 'company'])
            ->whereNull('deleted_at');

        // Search functionality
        if ($searchTerm) {
            $obligationsQuery->whereHas('client.user', function ($subQuery) use ($searchTerm) {
                $subQuery->where('first_name', 'like', "%$searchTerm%")
                    ->orWhere('last_name', 'like', "%$searchTerm%");
            });
        }

        // Sorting functionality
        $obligationsQuery->orderBy($sortBy, $orderBy);

        // Pagination
        $obligations = $obligationsQuery->paginate($itemsPerPage, ['*'], 'page', $page);

        // Calculate counts
        $elapsedCount = Obligation::whereNull('deleted_at')
            ->whereDate('next_run', '<', $today)
            ->count();
        $dueTodayCount = Obligation::whereNull('deleted_at')
            ->whereDate('next_run', $today)
            ->count();
        $dueThisWeekCount = Obligation::whereNull('deleted_at')
            ->whereBetween('next_run', [$startOfWeek, $endOfWeek])
            ->count();
        $dueTomorrowCount = Obligation::whereNull('deleted_at')
            ->whereDate('next_run', $tomorrow)
            ->count();
        $completeCount = Obligation::whereNull('deleted_at')
            ->where('status', true)
            ->count();

        // Format obligations for response (attach frequency and type labels)
        $obligations->getCollection()->transform(function ($obligation) {
            // Ensure frequency is an integer and not an enum
            $frequencyEnum = $obligation->frequency; // Pass raw int value
            $obligationType = $obligation->type;
            $obligation->frequency_label = $frequencyEnum ? $frequencyEnum->label() : 'Unknown';  // Use label() for friendly display
            $obligation->type_label = $obligationType ? $obligationType->label() : 'Unknown';
            return $obligation;
        });

        // Return the counts and paginated obligations
        return response()->json([
            'counts' => [
                'elapsed' => $elapsedCount,
                'due_today' => $dueTodayCount,
                'due_this_week' => $dueThisWeekCount,
                'due_tomorrow' => $dueTomorrowCount,
                'complete' => $completeCount,
            ],
            'obligations' => $obligations->items(),  // Current page obligations
            'total' => $obligations->total(),        // Total number of obligations
            'message' => 'success',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $services = Service::select('id', 'name', 'price')->get();

        $employees = Employee::with('user:id,first_name,last_name,phone')
            ->select('id', 'user_id') // Assuming user_id links to the User model
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'first_name' => $employee->user->first_name,
                    'last_name' => $employee->user->last_name,
                    'phone' => $employee->user->phone,
                ];
            });

        $clients = Client::with('user:id,first_name,last_name,phone')
            ->select('id', 'user_id') // Assuming user_id links to the User model
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'first_name' => $client->user->first_name,
                    'last_name' => $client->user->last_name,
                    'phone' => $client->user->phone,
                ];
            });

        $companies = Company::select('id', 'name')->get();

        $businesses = Business::select('id', 'name',)->get();

        return response()->json([
            'services' => $services,
            'employees' => $employees,
            'businesses' => $businesses,
            'companies' => $companies,
            'clients' => $clients,
            'message' => 'Form data retrieved successfully',
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('Store Obligation request received', ['request' => $request->all()]);

        // Validation logic
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'fee' => 'required|numeric|min:0',
            'type' => 'required|integer|in:0,1,2',
            'privacy' => 'required|boolean',
            'start_date' => 'required|date',
            'frequency' => 'required|integer|in:0,1,2,3,4,5',
            'client_id' => 'nullable|exists:clients,id',
            'company_id' => 'nullable|exists:companies,id',
            'service_ids' => 'nullable|array',
            'service_ids.*.id' => 'exists:services,id',
            'service_ids.*.adjusted_price' => 'nullable|numeric|min:0',
            'adjusted_prices' => 'nullable|array',
            'adjusted_prices.*.id' => 'exists:services,id',
            'adjusted_prices.*.adjusted_price' => 'nullable|numeric|min:0',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        try {
            // Call the service to create the obligation
            $this->obligationService->createObligationWithTask($validated);

            return response()->json(['message' => 'Successfully created obligation!'], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create obligation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['message' => 'Failed to create obligation.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Obligation $obligation)
    {
        $obligation->load(['services', 'employees.user:id,first_name,last_name,phone', 'client.user:id,first_name,last_name']);

        return response()->json([
            'obligation' => $obligation,
            'message' => 'Obligation retrieved successfully',
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Obligation $obligation)
    {
        // Fetch services, employees, companies, and businesses for dropdowns
//        $obligationServiceIds = $obligation->services()->pluck('services.id')->toArray();

        $services = Service::select('services.id', 'name', 'price')
            ->get();
//            ->map(function ($service) use ($obligation) {
//                // Check for adjusted price in pivot table
//                $pivotData = $obligation->services()->where('service_id', $service->id)->first();
//                $adjustedPrice = $pivotData ? $pivotData->pivot->price : $service->price;
//
//                return [
//                    'id' => $service->id,
//                    'name' => $service->name,
//                    'original_price' => $service->price,
//                    'adjusted_price' => $adjustedPrice,
//                ];
//            });

        $employees = Employee::with('user:id,first_name,last_name,phone')
            ->select('id', 'user_id') // Assuming user_id links to the User model
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'first_name' => $employee->user->first_name,
                    'last_name' => $employee->user->last_name,
                    'phone' => $employee->user->phone,
                ];
            });

        $clients = Client::with('user:id,first_name,last_name,phone')
            ->select('id', 'user_id') // Assuming user_id links to the User model
            ->get()
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'first_name' => $client->user->first_name,
                    'last_name' => $client->user->last_name,
                    'phone' => $client->user->phone,
                ];
            });

        $companies = Company::select('id', 'name')->get();
        $businesses = Business::select('id', 'name')->get();

        // Return obligation details along with the dropdown data
        return response()->json([
            'services' => $services,
            'employees' => $employees,
            'businesses' => $businesses,
            'companies' => $companies,
            'clients' => $clients,
//            'service_ids' => $obligationServiceIds,
            'obligation' => $obligation->load(['services:id,name,price', 'employees.user']), // Include the obligation details for pre-filling the form
            'message' => 'Edit form data retrieved successfully',
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Obligation $obligation)
    {
        Log::info('Update Obligation request received', ['request' => $request->all()]);
//        $request->merge([
//            'employee_ids' => collect($request->input('employee_ids'))->pluck('value')->all(),
//        ]);

        // Validation logic
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fee' => 'required|numeric|min:0',
            'type' => 'required|integer|in:0,1',
            'privacy' => 'required|boolean',
            'start_date' => 'required|date',
            'frequency' => 'required|integer|in:0,1,2,3,4,5',
            'client_id' => 'nullable|exists:clients,id',
            'company_id' => 'nullable|exists:companies,id',
            'service_ids' => 'nullable|array',
            'service_ids.*.id' => 'exists:services,id',
            'service_ids.*.adjusted_price' => 'nullable|numeric|min:0',
            'adjusted_prices' => 'nullable|array',
            'adjusted_prices.*.id' => 'exists:services,id',
            'adjusted_prices.*.adjusted_price' => 'nullable|numeric|min:0',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        try {
            // Call the service to update the obligation
            $this->obligationService->updateObligationWithTask($obligation, $validated);

            return response()->json(['message' => 'Successfully updated obligation!'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update obligation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['message' => 'Failed to update obligation.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Obligation $obligation)
    {
        try {
            // Load associated tasks, fee notes, and payments
            $obligation->load(['tasks', 'feeNotes', 'payments']);

            // Soft delete the obligation
            $obligation->delete();

            // Soft delete associated tasks, fee notes, and payments
            foreach ($obligation->tasks as $task) {
                $task->delete();
            }
            foreach ($obligation->feeNotes as $feeNote) {
                $feeNote->delete();
            }
            foreach ($obligation->payments as $payment) {
                $payment->delete();
            }

            return response()->json(['message' => 'Successfully deleted obligation and associated records!'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete obligation and associated records', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['message' => 'Failed to delete obligation.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore($id)
    {
        try {
            // Restore the obligation
            $obligation = Obligation::withTrashed()->findOrFail($id);
            $obligation->restore();

            // Restore associated tasks, fee notes, and payments
            foreach ($obligation->tasks()->onlyTrashed()->get() as $task) {
                $task->restore();
            }
            foreach ($obligation->feeNotes()->onlyTrashed()->get() as $feeNote) {
                $feeNote->restore();
            }
            foreach ($obligation->payments()->onlyTrashed()->get() as $payment) {
                $payment->restore();
            }

            return response()->json(['message' => 'Successfully restored obligation and associated records!'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to restore obligation and associated records', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['message' => 'Failed to restore obligation.', 'error' => $e->getMessage()], 500);
        }
    }
}
