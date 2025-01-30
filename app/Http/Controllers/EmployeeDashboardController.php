<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeDashboardController extends Controller
{
    /**
     * Get the currently authenticated employee's details.
     *
     * @return \App\Models\Employee|null
     */
    protected function getEmployee()
    {
        $user = Auth::user();
        return Employee::where('user_id', $user->id)->first();
    }

    /**
     * Fetch tasks assigned to the employee.
     *
     * @param int $employeeId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAssignedTasks($employeeId)
    {
        return Task::whereHas('employees', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })->get();
    }

    /**
     * Count completed tasks for the employee.
     *
     * @param int $employeeId
     * @return int
     */
    protected function countCompletedTasks($employeeId)
    {
        return Task::whereHas('employees', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })->where('status', 'completed')->count();
    }

    /**
     * Count due tasks for the employee.
     *
     * @param int $employeeId
     * @return int
     */
    protected function countDueTasks($employeeId)
    {
        return Task::whereHas('employees', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->count();
    }

    /**
     * Count upcoming tasks for the employee.
     *
     * @param int $employeeId
     * @return int
     */
    protected function countUpcomingTasks($employeeId)
    {
        return Task::whereHas('employees', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->count();
    }

    /**
     * Display the dashboard for the current employee.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return response()->json([
                'message' => 'Employee data not found for the current user.',
            ], 404);
        }

        $tasks = $this->getAssignedTasks($employee->id);
        $completedTasksCount = $this->countCompletedTasks($employee->id);
        $dueTasksCount = $this->countDueTasks($employee->id);
        $upcomingTasksCount = $this->countUpcomingTasks($employee->id);

        return response()->json([
            'employee' => $employee,
            'tasks' => $tasks,
            'completed_tasks_count' => $completedTasksCount,
            'due_tasks_count' => $dueTasksCount,
            'upcoming_tasks_count' => $upcomingTasksCount,
        ], 200);
    }
}
