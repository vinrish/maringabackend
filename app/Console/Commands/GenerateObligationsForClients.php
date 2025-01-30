<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\ObligationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateObligationsForClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obligations:generate {clientIds?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate obligations for specified clients or all clients if none specified';

    protected ObligationService $obligationService;

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
        $clientIds = $this->argument('clientIds');
        $clients = $clientIds ? Client::whereIn('id', $clientIds)->get() : Client::all();

        if ($clients->isEmpty()) {
            $this->warn('No clients found for the provided IDs.');
            return;
        }

        $this->info("Generating obligations for " . $clients->count() . " client(s).");

        foreach ($clients as $client) {
            if (!$client->user) {
                Log::warning("Skipping client ID: {$client->id} due to missing company or user information.");
                $this->warn("Skipping client ID: {$client->id} due to missing company or user information.");
                continue;
            }

            try {
                $obligationData = [
                    'name' => 'VAT',
                    'description' => 'Automatically generated obligation',
                    'fee' => 7500,
                    'type' => 0,
                    'privacy' => true,
                    'start_date' => Carbon::create(2020, 4, 1)->toDateString(),
                    'frequency' => 5,
                    'last_run' => Carbon::create(2020, 4, 1)->toDateString(),
                    'client_id' => $client->id,
                    'company_id' => null,
                    'service_ids' => [19],
                    'adjusted_prices' => [
                        ['id' => 19, 'adjusted_price' => 7500],
                    ],
                    'employee_ids' => [1],
                ];

                $this->obligationService->createObligationWithTask($obligationData);
                $this->info("Obligation created for client ID: {$client->id}");
            } catch (\Exception $e) {
                Log::error("Failed to create obligation for client ID: {$client->id}", ['error' => $e->getMessage()]);
                $this->error("Error creating obligation for client ID: {$client->id}");
            }
        }

        $this->info("Obligations generation completed.");
    }
}
