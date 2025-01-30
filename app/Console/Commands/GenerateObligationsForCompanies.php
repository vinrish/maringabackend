<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\ObligationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateObligationsForCompanies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obligations:generate-obligations-for-companies {companyIds?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $companyIds = $this->argument('companyIds');
        $companys = $companyIds ? Company::whereIn('id', $companyIds)->get() : Company::all();

        if ($companys->isEmpty()) {
            $this->warn('No companys found for the provided IDs.');
            return;
        }

        $this->info("Generating obligations for " . $companys->count() . " company(s).");

        foreach ($companys as $company) {
            if (!$company) {
                Log::warning("Skipping client ID: {$company->id} due to missing company or user information.");
                $this->warn("Skipping client ID: {$company->id} due to missing company or user information.");
                continue;
            }

            try {
                $obligationData = [
                    'name' => 'Standard Obligation',
                    'description' => 'Automatically generated obligation',
                    'fee' => 15000,
                    'type' => 1,
                    'privacy' => true,
                    'start_date' => Carbon::create(2020, 4, 1)->toDateString(),
                    'frequency' => 5,
                    'last_run' => Carbon::create(2020, 4, 1)->toDateString(),
                    'client_id' => null,
                    'company_id' => $company->id,
                    'service_ids' => [20],
                    'adjusted_prices' => [
                        ['id' => 20, 'adjusted_price' => 15000],
                    ],
                    'employee_ids' => [1],
                ];

                $this->obligationService->createObligationWithTask($obligationData);
                $this->info("Obligation created for client ID: {$company->id}");
            } catch (\Exception $e) {
                Log::error("Failed to create obligation for client ID: {$company->id}", ['error' => $e->getMessage()]);
                $this->error("Error creating obligation for client ID: {$company->id}");
            }
        }

        $this->info("Obligations generation completed.");
    }
}
