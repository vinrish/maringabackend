<?php

namespace App\Jobs;

use App\Services\RecurringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class ProcessRecurringObligationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected RecurringService $recurringService;

    /**
     * Create a new job instance.
     */
    public function __construct(RecurringService $recurringService)
    {
        $this->recurringService = $recurringService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting to process recurring obligations.');

        try {
            $obligations = $this->recurringService->processRecurringObligations();

            Log::info("Found {$obligations->count()} obligations to process.");

            foreach ($obligations as $obligation) {
                Log::info("Processing obligation: {$obligation->name}, ID: {$obligation->id}");
            }

            Log::info('Successfully processed recurring obligations.');
        } catch (\Exception $e) {
            Log::error('Failed to process recurring obligations: ' . $e->getMessage());
        }
    }
}
