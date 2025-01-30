<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class RemoveDuplicateServicesFromAllObligations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obligations:remove-duplicate-services-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate services from obligations of all companies, leaving only one instance of each service.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Started checking for duplicate services in obligations for all companies.');

        // Fetch all companies with their obligations and services
        $companies = Company::with('obligations.services')->get();

        if ($companies->isEmpty()) {
            $this->error('No companies found.');
            Log::warning('No companies found.');
            return CommandAlias::FAILURE;
        }

        foreach ($companies as $company) {
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
                            'company_id' => $company->id,
                            'obligation_id' => $obligation->id,
                            'service_id' => $serviceId,
                        ]);
                    }
                }

                // Add the current service IDs to the overall collection of service IDs
                $allServiceIds = $allServiceIds->merge($currentServiceIds);
            }
        }

        Log::info('Finished removing duplicate services from obligations for all companies.');
        $this->info('Duplicate services have been removed successfully for all companies.');
        return CommandAlias::SUCCESS;
    }
}
