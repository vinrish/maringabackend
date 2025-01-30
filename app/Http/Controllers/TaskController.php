<?php

namespace App\Http\Controllers;

use App\Jobs\CompleteTaskJob;
use App\Models\Employee;
use App\Models\Task;
use App\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Validate request parameters for pagination, sorting, and search
        $validator = Validator::make($request->all(), [
            'q' => ['nullable', 'string'],
            'sortBy' => ['nullable', 'string'],
            'orderBy' => ['nullable', 'in:asc,desc'],
            'itemsPerPage' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:complete,due,in_progress,upcoming'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve validated parameters
        $searchTerm = $request->input('q');
        $sortBy = $request->input('sortBy', 'created_at'); // Default sorting by created_at
        $orderBy = $request->input('orderBy', 'asc');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);
        $status = $request->input('status');

        // Initialize the query with relationships
        $tasksQuery = Task::with(['obligation', 'employees.user']);

        // Search functionality (e.g., search by task name or obligation fields)
        if ($searchTerm) {
            $tasksQuery->where('name', 'like', "%$searchTerm%")
                ->orWhereHas('obligation', function ($query) use ($searchTerm) {
                    $query->where('name', 'like', "%$searchTerm%");
                });
        }

        // Status filtering
        if ($status) {
            $tasksQuery->where(function ($query) use ($status) {
                if ($status === 'complete') {
                    $query->where('status', true); // Completed tasks
                } elseif ($status === 'due') {
                    $query->where('status', false) // Ensure task is not complete
                    ->whereHas('obligation', function ($query) {
                        $query->where('next_run', '<', Carbon::today());
                    });
                } elseif ($status === 'in_progress') {
                    $query->where('status', false) // Ensure task is not complete
                    ->whereHas('obligation', function ($query) {
                        $query->where('next_run', Carbon::today());
                    });
                } elseif ($status === 'upcoming') {
                    $query->where('status', false) // Ensure task is not complete
                    ->whereHas('obligation', function ($query) {
                        $query->where('next_run', '>', Carbon::today());
                    });
                }
            });
        }

        // Sorting functionality
        $tasksQuery->orderBy($sortBy, $orderBy);

        // Pagination
        $tasks = $tasksQuery->paginate($itemsPerPage, ['*'], 'page', $page);

        // Attach status labels to each task
        $tasks->getCollection()->transform(function ($task) {
            $task->status_label = $this->getTaskStatus($task);
            return $task;
        });

        // Return the paginated tasks along with the total number of tasks
        return response()->json([
            'tasks' => $tasks->items(),  // Current page tasks
            'total' => $tasks->total(),  // Total number of tasks
            'message' => 'success',
        ]);
    }

    private function getTaskStatus(Task $task)
    {
        $today = Carbon::today();
        $nextRun = $task->obligation->next_run;

        if ($task->status) {
            return 'complete';
        } elseif ($nextRun->isPast()) {
            return 'due';
        } elseif ($nextRun->isToday()) {
            return 'in_progress';
        } else {
            return 'upcoming';
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $employees = Employee::with('user:id,first_name,last_name,middle_name,phone')
            ->select('id', 'user_id') // Assuming user_id links to the User model
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'first_name' => $employee->user->first_name,
                    'last_name' => $employee->user->last_name,
                    'middle_name' => $employee->user->middle_name,
                    'phone' => $employee->user->phone,
                ];
            });

        return response()->json([
            'employees' => $employees,
            'message' => 'Form data retrieved successfully',
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'status' => 'required|boolean',
            'obligation_id' => 'required|exists:obligations,id',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        try {
            $task = $this->taskService->createTaskWithEmployees($validated);

            return response()->json(['message' => 'Task created successfully!', 'task' => $task], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create task', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create task.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        // Eager load relationships
        $task->load(['obligation', 'employees.user']);

        // Return task details
        return response()->json([
            'task' => $task,
            'message' => 'success',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Task $task)
    {
        // Eager load relationships
        $task->load(['obligation', 'employees.user']);

        // Return the task details for editing
        return response()->json([
            'task' => $task,
            'message' => 'success',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'status' => 'required|boolean',
            'obligation_id' => 'required|exists:obligations,id',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id',
            'files' => 'nullable|array',  // Files (optional)
            'files.*' => 'file|mimes:jpg,png,pdf,doc,docx|max:2048',  // Validate file types and size
        ]);

        try {
            $task = $this->taskService->updateTaskWithEmployees($task, $validated);

            return response()->json(['message' => 'Task updated successfully!', 'task' => $task], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update task', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update task.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        try {
            $task->delete(); // Soft delete the task (ensure soft delete is enabled in the Task model)

            return response()->json(['message' => 'Task deleted successfully!'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete task', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete task.', 'error' => $e->getMessage()], 500);
        }
    }

//    public function complete(Request $request, $id)
//    {
//        $task = Task::findOrFail($id);
//        return $this->taskService->completeTask($task);
//    }
    public function complete(Request $request)
    {
        $taskIds = $request->input('task_ids', []);

        if (empty($taskIds)) {
            return response()->json(['message' => 'No tasks selected.'], 400);
        }

        $incompleteTasks = Task::whereIn('id', $taskIds)->where('status', 0)->pluck('id');

        if ($incompleteTasks->isEmpty()) {
            return response()->json(['message' => 'All selected tasks are already complete.'], 400);
        }

        foreach ($incompleteTasks as $taskId) {
            // Dispatch the job for each incomplete task
            Log::info('Dispatching task completion jobs for tasks:', ['task_ids' => $incompleteTasks]);
            CompleteTaskJob::dispatch($taskId);
        }

        return response()->json(['message' => 'Tasks are being processed. You will be notified upon completion.'], 200);
    }

    public function reassign(Request $request, Task $task)
    {
        // Validate the request
        $validated = $request->validate([
            'employee_ids' => 'required|array', // Ensure employee_ids is an array
            'employee_ids.*' => 'exists:employees,id', // Validate that each ID exists in the employees table
        ]);

        try {
            // Update the task's associated employees
            $task->employees()->sync($validated['employee_ids']);

            return response()->json([
                'message' => 'Task reassigned successfully!',
                'task' => $task->load('employees.user'), // Reload task with employees for updated response
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a response
            Log::error('Failed to reassign task', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to reassign task.', 'error' => $e->getMessage()], 500);
        }
    }
}
