<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class DetachTodayAddedObligationServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obligations:detach-today-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detach obligation services added today from obligations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Started detaching obligation services added today.');

        try {
            // Get today's date in 'Y-m-d' format
            $today = now()->toDateString();

            // Fetch all obligation_service records added today
            $todayAddedServices = DB::table('obligation_service')
                ->whereDate('created_at', $today)
                ->get();

            if ($todayAddedServices->isEmpty()) {
                $this->info('No obligation services added today were found.');
                return CommandAlias::SUCCESS;
            }

            // Detach services from obligations
            foreach ($todayAddedServices as $service) {
                DB::table('obligation_service')
                    ->where('id', $service->id)
                    ->delete();

                Log::info('Detached obligation service', [
                    'obligation_id' => $service->obligation_id,
                    'service_id' => $service->service_id,
                ]);
            }

            $this->info('Successfully detached obligation services added today.');
            Log::info('Finished detaching obligation services added today.');

            return CommandAlias::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Failed to detach obligation services added today', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error('An error occurred while detaching obligation services. Check logs for details.');
            return CommandAlias::FAILURE;
        }
    }
}
