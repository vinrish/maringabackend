<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class CleanTaskNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:clean-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove the word "Task" from task names in the tasks table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tasks = Task::where('name', 'LIKE', '%-%')->get();

        foreach ($tasks as $task) {
            $originalName = $task->name;
            $newName = str_replace('-', '', $originalName);
            $task->update(['name' => trim($newName)]);
            $this->info("Updated: '$originalName' to '$newName'");
        }

        $this->info('Task names cleaned successfully!');
    }
}
