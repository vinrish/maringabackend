<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\ObligationService;

class GenerateTasksForObligations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:generate-obligations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate tasks for all obligations based on their next run date';

    protected ObligationService $obligationService;

    /**
     * Create a new command instance.
     *
     * @param ObligationService $obligationService
     */
    public function __construct(ObligationService $obligationService)
    {
        parent::__construct();
        $this->obligationService = $obligationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting task generation for all obligations');

        try {
            // Delegate task generation to the service
            $this->obligationService->generateTasksForAllObligations();

            $this->info('Tasks generation completed successfully.');
            Log::info('Task generation process completed successfully.');
        } catch (\Exception $e) {
            Log::error('Error occurred during task generation', ['error' => $e->getMessage()]);
            $this->error('An error occurred during the task generation process.');
        }
    }
}
