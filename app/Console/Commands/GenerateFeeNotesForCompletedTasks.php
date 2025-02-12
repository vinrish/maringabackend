<?php

namespace App\Console\Commands;

use App\Models\FeeNote;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateFeeNotesForCompletedTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:generate-feenotes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check completed tasks and generate fee notes if none exist';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting fee note generation process');

        // Fetch tasks with an obligation that are marked as completed (status = 1)
        $tasks = Task::whereNotNull('obligation_id')
            ->where('status', 1)
            ->get();

        foreach ($tasks as $task) {
            $this->processTask($task);
        }

        $this->info('Fee note generation process completed.');
    }

    /**
     * Process a single task to check and generate a fee note if necessary.
     */
    private function processTask(Task $task)
    {
        $obligation = $task->obligation;

        if (!$obligation) {
            Log::warning('Obligation not found for task', ['task_id' => $task->id]);
            return;
        }

        // Retrieve the service associated with the task name
        $serviceWithPrice = DB::table('services')
            ->join('obligation_service', 'obligation_service.service_id', '=', 'services.id')
            ->where('obligation_service.obligation_id', $obligation->id)
            ->where('services.name', $task->name) // Match the service name to the task name
            ->select(
                'services.id as service_id',
                'services.name as service_name',
                'obligation_service.price as service_price'
            )
            ->first();

        if (!$serviceWithPrice) {
            Log::warning('No matching service found for task', [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'obligation_id' => $obligation->id,
            ]);
            return;
        }

        // Check if a fee note already exists for this task
        $existingFeeNote = FeeNote::where('task_id', $task->id)
            ->where('client_id', $obligation->client_id)
            ->where('company_id', $obligation->company_id)
            ->where('amount', $serviceWithPrice->service_price)
            ->first();

        if ($existingFeeNote) {
            Log::info('Fee note already exists for task', [
                'task_id' => $task->id,
                'service_id' => $serviceWithPrice->service_id,
            ]);
            return;
        }

        // Start a database transaction
        DB::beginTransaction();
        try {
            // Create a new fee note
            FeeNote::create([
                'task_id' => $task->id,
                'client_id' => $obligation->client_id ?? $task->client_id,
                'company_id' => $obligation->company_id,
                'amount' => $serviceWithPrice->service_price,
                'status' => '0',
            ]);

            Log::info('Fee note created for task', [
                'task_id' => $task->id,
                'service_id' => $serviceWithPrice->service_id,
                'service_name' => $serviceWithPrice->service_name,
                'amount' => $serviceWithPrice->service_price,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create fee note for task', [
                'task_id' => $task->id,
                'service_id' => $serviceWithPrice->service_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
