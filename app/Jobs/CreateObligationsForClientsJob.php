<?php

namespace App\Jobs;

use App\Enums\ObligationFrequency;
use App\Models\Client;
use App\Models\Company;
use App\Models\Service;
use App\Services\ObligationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateObligationsForClientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $obligationService;

    /**
     * Create a new job instance.
     */
    public function __construct(ObligationService $obligationService)
    {
        $this->obligationService = $obligationService;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $clients = Company::with('client')->get();
        $serviceId = 15; // Your specified service ID
        $employeeIds = [2]; // The employee ID to assign
        $serviceAmount = Service::where('id', $serviceId)->value('price');
        $frequency = ObligationFrequency::YEARLY;

        foreach ($clients as $client) {
            try {
                $obligationData = [
                    'name' => 'VAT for ' . $client->name,
                    'description' => 'This is a default obligation for company ' . $client->name,
                    'fee' => $serviceAmount,
                    'type' => '0',
                    'privacy' => false,
                    'start_date' => '2020-04-15',
                    'due_date' => now()->addDays(30)->format('Y-m-d'),
                    'frequency' => $frequency->value,
                    'status' => '',
                    'is_recurring' => false,
                    'client_id' => null,
                    'company_id' => $client->id,
                    'service_ids' => [$serviceId],
                    'employee_ids' => $employeeIds,
                ];

                $this->obligationService->createObligationWithTask($obligationData);

                \Log::info("Created obligation for client: {$client->name}");
            } catch (\Exception $e) {
                \Log::error("Failed to create obligation for client: {$client->name}. Error: {$e->getMessage()}");
            }
        }
    }
}
