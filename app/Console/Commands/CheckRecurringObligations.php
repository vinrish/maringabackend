<?php

namespace App\Console\Commands;

use App\Enums\ObligationFrequency;
use App\Models\Obligation;
use App\Services\TaskService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckRecurringObligations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obligations:check-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check recurring obligations and create tasks if the next_run date is met';

    protected TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        parent::__construct();
        $this->taskService = $taskService;
    }


    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Running CheckRecurringObligations command');
        $now = Carbon::now();

        // Fetch obligations where the next_run date is due
        $obligations = Obligation::where('next_run', '<=', $now)->with('employees')->get();

        foreach ($obligations as $obligation) {
            // Get employee IDs attached to the obligation
            Log::info('Employee count', ['count' => $obligation->employees()->count()]);
            $employeeIds = $obligation->employees()->pluck('employees.id')->toArray();

            // Create a task for the obligation
            $this->taskService->createTaskWithEmployees([
                'name' => $obligation->name,
                'description' => $obligation->description,
                'due_date' => $obligation->next_run,
                'status' => false,
                'obligation_id' => $obligation->id,
                'employee_ids' => $employeeIds,
            ]);

            Log::info('Created task for recurring obligation', [
                'obligation_id' => $obligation->id,
                'task_due_date' => $obligation->next_run,
                'employee_ids' => $employeeIds,
            ]);

            // Update the next_run date for the obligation
            $nextRunDate = $this->calculateNextRunDate($obligation);
            Log::info($nextRunDate);
            $obligation->update(['next_run' => $nextRunDate, 'last_run' => $now]);

            Log::info($nextRunDate);
        }

        $this->info('Recurring obligations checked and tasks created if due.');
    }

    public function calculateNextRunDate(Obligation $obligation): ?Carbon
    {
//        $startDate = Carbon::parse($obligation->start_date)->format('Y-m-d H:i:s');
        $startDate = Carbon::parse($obligation->next_run);

        return match ($obligation->frequency) {
            ObligationFrequency::DAILY => $startDate->addDay(),
            ObligationFrequency::WEEKLY => $startDate->addWeek(),
            ObligationFrequency::MONTHLY => $startDate->addMonth(),
            ObligationFrequency::QUARTERLY => $startDate->addMonths(3),
            ObligationFrequency::SEMI_ANNUALLY => $startDate->addMonths(6),
            ObligationFrequency::YEARLY => $startDate->addYear(),
            default => null,
        };
    }
}
