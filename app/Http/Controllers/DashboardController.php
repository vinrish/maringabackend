<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Client;
use App\Models\Company;
use App\Models\FeeNote;
use App\Models\Obligation;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $statistics = $this->statistics();
        $platformAnalytics = $this->platformAnalytics();
        $paymentsOverview = $this->paymentsOverview();
        $taskOverview = $this->taskOverview();

        $data = [
            'statistics' => $statistics,
            'platform_analytics' => $platformAnalytics,
            'payments_overview' => $paymentsOverview,
            'task_overview' => $taskOverview,
        ];

        return response()->json($data);
    }

    public function statistics(): array
    {
        return [
            'total_clients' => Client::count(),
            'total_businesses' => Business::count(),
            'total_companies' => Company::count(),
            'total_services' => Service::count(),
            'total_obligations' => Obligation::count(),
            'total_tasks' => Task::count(),
        ];
    }

    public function platformAnalytics(): array
    {
        $activeClients = Client::query()
            ->whereHas('user', function ($query) {
                $query->where('status', '1');
            })->count();

        $inactiveClients = Client::query()
            ->whereHas('user', function ($query) {
                $query->where('status', '0');
            })->count();

        $newClients = Client::query()
            ->where('created_at', '>', now()->subDays(30))
            ->count();

        $newBusinesses = Business::query()
            ->where('created_at', '>', now()->subDays(30))
            ->count();

        $newCompanies = Company::query()
            ->where('created_at', '>', now()->subDays(30))
            ->count();

        return [
            'active_clients' => $activeClients,
            'inactive_clients' => $inactiveClients,
            'new_clients' => $newClients,
            'new_businesses' => $newBusinesses,
            'new_companies' => $newCompanies,
        ];
    }

    public function paymentsOverview(): array
    {
        $totalNeeded = FeeNote::sum('amount');

        $totalPaid = Payment::sum('amount');

        $totalUnpaid = $totalNeeded - $totalPaid;

        $paidPercentage = $totalNeeded > 0 ? round(($totalPaid / $totalNeeded) * 100, 2) : 0;
        $unpaidPercentage = 100 - $paidPercentage;

        return [
            'total_needed' => $totalNeeded,
            'total_paid' => $totalPaid,
            'total_unpaid' => $totalUnpaid,
            'paid_percentage' => $paidPercentage,
            'unpaid_percentage' => $unpaidPercentage,
        ];
    }

    public function taskOverview(): array
    {
        $totalTasks = Task::count();

        $completedTasks = Task::where('status', 1)->count();

        $dueTasks = Task::where('status', 0)->count();

        $completedPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0;
        $duePercentage = 100 - $completedPercentage;

        return [
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'due_tasks' => $dueTasks,
            'completed_percentage' => $completedPercentage,
            'due_percentage' => $duePercentage,
        ];
    }
}
