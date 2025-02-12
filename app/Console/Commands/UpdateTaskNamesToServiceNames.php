<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateTaskNamesToServiceNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:update-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update task names to match the service name based on the obligation_service table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting task name update');

        // Get all tasks that are linked to an obligation
        $tasks = Task::whereNotNull('obligation_id')->get();

        foreach ($tasks as $task) {
            // Fetch the service name linked to this obligation
            $service = DB::table('obligation_service')
                ->join('services', 'obligation_service.service_id', '=', 'services.id')
                ->where('obligation_service.obligation_id', $task->obligation_id)
                ->select('services.name')
                ->first();

            if ($service) {
                Log::info("Updating Task ID {$task->id} from '{$task->name}' to '{$service->name}'");

                // Update task name to match the service name
                $task->update(['name' => $service->name]);
            }
        }

        $this->info('Task names have been successfully updated.');
    }
}
