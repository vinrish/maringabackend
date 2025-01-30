<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Command\Command as CommandAlias;

class UpdateObligationsWithServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'obligations:update-services {serviceIds*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update obligations for companies by adding additional services if not already added';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serviceIds = $this->argument('serviceIds');

        Log::info('Started updating obligations with specific services.', ['service_ids' => $serviceIds]);

        // Validate the provided service IDs
        $validServices = Service::whereIn('id', $serviceIds)->get();

        if ($validServices->isEmpty()) {
            $this->error('No valid services found for the provided IDs.');
            Log::warning('Invalid service IDs provided', ['service_ids' => $serviceIds]);
            return CommandAlias::FAILURE;
        }

        // Fetch all companies with their obligations and services
        $companies = Company::with('obligations.services')->get();

        foreach ($companies as $company) {
            // Check if any obligation for this company already has one of the specified services
            $hasServices = $company->obligations->flatMap(function ($obligation) {
                return $obligation->services->pluck('id');
            })->intersect($serviceIds)->isNotEmpty();

            if ($hasServices) {
                Log::info('Skipping company as one of its obligations already has the specified services.', [
                    'company_id' => $company->id,
                    'service_ids' => $serviceIds,
                ]);
                continue; // Skip updating obligations for this company
            }

            foreach ($company->obligations as $obligation) {
                // Fetch services already linked to the obligation
                $currentServiceIds = $obligation->services->pluck('id')->toArray();

                // Determine missing services for this obligation
                $missingServices = $validServices->whereNotIn('id', $currentServiceIds);

                if ($missingServices->isNotEmpty()) {
                    // Add missing services to the obligation with default prices
                    $servicesWithDefaultPrices = $missingServices->mapWithKeys(function ($service) {
                        return [$service->id => ['price' => $service->price]];
                    });

                    $obligation->services()->attach($servicesWithDefaultPrices);

                    Log::info('Added specific services to obligation', [
                        'obligation_id' => $obligation->id,
                        'added_service_ids' => $missingServices->pluck('id')->toArray(),
                    ]);

                    // Create tasks for each newly added service
                    foreach ($missingServices as $service) {
                        Log::info('Attempting to create a task for service', [
                            'service_id' => $service->id,
                            'obligation_id' => $obligation->id,
                        ]);

                        app('App\Services\ObligationService')
                            ->createOrUpdateTaskForService($obligation, $service->id, [2]);
                    }
                }
            }
        }

        Log::info('Finished updating obligations with specific services.');
        $this->info('Obligations updated successfully.');
        return CommandAlias::SUCCESS;
    }
}
