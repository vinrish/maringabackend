<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RemoveDuplicateServicesFromObligations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obligations:remove-duplicate-services {companyId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate services from obligations of a company, leaving only one instance of each service.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->argument('companyId');

        Log::info('Started checking for duplicate services in obligations.', ['company_id' => $companyId]);

        // Fetch the company with its obligations and services
        $company = Company::with('obligations.services')->find($companyId);

        if (!$company) {
            $this->error('Company not found.');
            Log::warning('Company not found.', ['company_id' => $companyId]);
            return CommandAlias::FAILURE;
        }

        $allServiceIds = collect();

        foreach ($company->obligations as $obligation) {
            // Collect all service IDs for this company's obligations
            $currentServiceIds = $obligation->services->pluck('id');

            // Find duplicate services
            $duplicates = $currentServiceIds->intersect($allServiceIds);

            if ($duplicates->isNotEmpty()) {
                foreach ($duplicates as $serviceId) {
                    // Detach duplicate service from this obligation
                    $obligation->services()->detach($serviceId);

                    Log::info('Detached duplicate service from obligation.', [
                        'obligation_id' => $obligation->id,
                        'service_id' => $serviceId,
                    ]);
                }
            }

            // Add the current service IDs to the overall collection of service IDs
            $allServiceIds = $allServiceIds->merge($currentServiceIds);
        }

        Log::info('Finished removing duplicate services from obligations.', ['company_id' => $companyId]);
        $this->info('Duplicate services have been removed successfully.');
        return CommandAlias::SUCCESS;
    }
}
