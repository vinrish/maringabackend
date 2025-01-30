<?php

namespace App\Jobs;

use App\Models\Task;
use App\Notifications\TaskCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CompleteTaskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $taskId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $taskId)
    {
        $this->taskId = $taskId;
    }

    public function uniqueId()
    {
        return $this->taskId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $task = Task::find($this->taskId);

        if (!$task) {
            Log::warning('Task not found', ['task_id' => $this->taskId]);
            return;
        }

        if ($task->status === 1) {
            Log::info('Task is already complete', ['task_id' => $this->taskId]);
            return;
        }

//        $task->update(['status' => 1]);

        try {
            app('App\Services\TaskService')->createFeeNoteForTask($task);
            Log::info('Fee note created for completed task', ['task_id' => $this->taskId]);
        } catch (\Exception $e) {
            Log::error('Failed to create fee note for task', [
                'task_id' => $this->taskId,
                'error' => $e->getMessage(),
            ]);
        }

        if ($task->assignee) {
            $task->assignee->notify(new TaskCompletedNotification($task));
            Log::info('Notification sent to assignee', ['task_id' => $this->taskId]);
        }

        Log::info('Task marked as complete', ['task_id' => $this->taskId]);
    }
}
