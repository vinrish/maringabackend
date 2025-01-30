<?php

namespace App\Services;

use App\Enums\ObligationFrequency;
use App\Models\Client;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Obligation;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ObligationService
{
//    protected string $adminId;
//
//    public function __construct()
//    {
//        // Set the admin ID to assign obligations
//        $this->adminId = Employee::where('position', 'admin')->first()->id ?? null;
//    }
//    public function createObligationWithTask(array $data)
//    {
//        DB::transaction(function () use ($data) {
//            Log::info('Starting transaction for creating obligation');
//
//            $frequency = ObligationFrequency::fromInt((int) $data['frequency']);
//
//            $obligation = Obligation::create([
//                'name' => 'Client Obligation',
//                'description' => $data['description'],
//                'fee' => (float) $data['fee'],
//                'type' => (int) $data['type'],
//                'privacy' => (bool) $data['privacy'],
//                'start_date' => $data['start_date'],
//                'frequency' => $frequency->value,
//                'client_id' => $data['client_id'],
//                'company_id' => $data['company_id'],
//                'assigned_to' => $this->adminId, // Assigned to admin by default
//            ]);
//
//            // Sync services if available
//            if (!empty($data['service_ids'])) {
//                $servicesWithPrices = [];
//
//                foreach ($data['service_ids'] as $serviceId) {
//                    $price = 0; // Default price
//                    // Check for adjusted prices
//                    if (!empty($data['adjusted_prices'])) {
//                        foreach ($data['adjusted_prices'] as $adjustedPrice) {
//                            if ($adjustedPrice['id'] == $serviceId) {
//                                $price = $adjustedPrice['adjusted_price'];
//                                break;
//                            }
//                        }
//                    } else {
//                        // Fallback to the service's original price
//                        $price = Service::find($serviceId)->price;
//                    }
//
//                    $servicesWithPrices[$serviceId] = ['price' => $price];
//                }
//
//                $obligation->services()->sync($servicesWithPrices);
////                $obligation->services()->syncWithoutDetaching($data['service_ids']);
//            }
//
//            // Calculate next run date
//            $nextRunDate = $this->calculateNextRunDate($obligation);
//
//            // Update obligation with next run date
//            $obligation->next_run = $nextRunDate;
//            $obligation->save();
//
//            // Create related task
//            $this->createTaskForObligation($obligation, $data['employee_ids']);
//        });
//    }

    public function createObligationWithTask(array $data)
    {
        DB::transaction(function () use ($data) {
            Log::info('Starting transaction for creating obligation');

            $frequency = ObligationFrequency::fromInt((int) $data['frequency']);
            $client = Client::find($data['client_id']);
            $company = Company::find($data['company_id']);

            // Entity name for obligation (client or company)
            $entityName = $company
                ? $company->name
                : ($client ? $client->user->first_name . ' ' . $client->user->middle_name . ' ' . $client->user->last_name : 'Unknown Client');

            // Create the obligation
            $obligation = Obligation::create([
                'name' => $entityName . ' - ' . $data['name'],
                'description' => $data['description'],
                'fee' => (float) $data['fee'],
                'type' => (int) $data['type'],
                'privacy' => (bool) $data['privacy'],
                'start_date' => $data['start_date'],
                'frequency' => $frequency->value,
                'client_id' => $data['client_id'],
                'company_id' => $data['company_id'],
                'last_run' => isset($data['last_run']) ? Carbon::parse($data['last_run']) : now(),
            ]);

            // Prepare service data with prices
            $servicesWithPrices = $this->getServicePrices($data['service_ids']);
            $obligation->services()->sync($servicesWithPrices);

            // Calculate and set the next run date
            $obligation->next_run = $this->calculateNextRunDate($obligation);
            $obligation->save();

            // Use the admin ID as the default employee for tasks
            $adminId = $this->getAdminId();

            // Create a separate task for each service with admin as default employee
            foreach ($data['service_ids'] as $serviceData) {
                if (!isset($serviceData['id'])) {
                    Log::warning('Service data does not have an ID', $serviceData);
                    continue;
                }

                $serviceId = $serviceData['id'];
                $this->createTaskForService($obligation, $serviceId, [$adminId]);
            }
        });
    }

// Method to fetch adjusted or default prices for services
    private function getServicePrices(array $serviceIds): array
    {
        $servicesWithPrices = [];

        foreach ($serviceIds as $serviceData) {
            if (!isset($serviceData['id'])) {
                Log::warning('Missing service ID in request data', $serviceData);
                continue;
            }

            $serviceId = $serviceData['id'];
            $service = Service::find($serviceId);

            if (!$service) {
                Log::warning("Invalid service ID: {$serviceId}");
                continue;
            }

            // Use adjusted price if provided; otherwise, use the service's original price
            $adjustedPrice = $serviceData['adjusted_price'] ?? $service->price;

            $servicesWithPrices[$serviceId] = ['price' => $adjustedPrice];
        }

        return $servicesWithPrices;
    }

// Helper method to retrieve the admin ID
    private function getAdminId(): string
    {
        return Employee::where('position', 'admin')->first()->id;
    }

    public function createTaskForService(Obligation $obligation, int $serviceId, array $employeeIds)
    {
        $service = Service::find($serviceId);
        // Generate task details
        $taskName = $service->name;
        $dueDate = $obligation->next_run;

        // Log the task creation
        Log::info('Creating task for service', [
            'task_name' => $taskName,
            'due_date' => $dueDate,
            'obligation_id' => $obligation->id,
            'service_id' => $serviceId,
            'employee_ids' => $employeeIds,
        ]);

        // Create the task
        app(TaskService::class)->createTaskWithEmployees([
            'name' => $taskName,
            'description' => $obligation->description,
            'due_date' => $dueDate,
            'status' => false,
            'obligation_id' => $obligation->id,
            'employee_ids' => $employeeIds,
        ]);
    }
    public function updateObligationWithTask(Obligation $obligation, array $data)
    {
        DB::transaction(function () use ($obligation, $data) {
            Log::info('Starting transaction for updating obligation', ['obligation_id' => $obligation->id]);

            // Update obligation details
            $frequency = ObligationFrequency::fromInt((int) $data['frequency']);

            $obligation->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'fee' => (float) $data['fee'],
                'type' => (int) $data['type'],
                'privacy' => (bool) $data['privacy'],
                'frequency' => $frequency->value,
                'start_date' => $data['start_date'],
                'client_id' => $data['client_id'],
                'company_id' => $data['company_id'],
            ]);

            // Sync services with adjusted prices if provided
            if (!empty($data['service_ids'])) {
                $servicesWithPrices = $this->getServicePrices($data['service_ids']);
                $obligation->services()->sync($servicesWithPrices);
            }

            // Sync employees associated with the obligation
            if (!empty($data['employee_ids'])) {
                $obligation->employees()->sync($data['employee_ids']);
            } else {
                Log::warning('No employee IDs provided during obligation update', ['obligation_id' => $obligation->id]);
            }

            // Recreate or update tasks for services under the obligation
            foreach ($data['service_ids'] as $serviceData) {
                if (!isset($serviceData['id'])) {
                    Log::warning('Service data does not have an ID', $serviceData);
                    continue;
                }

                $serviceId = $serviceData['id'];

                // Create or update a task for the service
                $this->createOrUpdateTaskForService($obligation, $serviceId, $data['employee_ids']);
            }
        });
    }

    public function calculateNextRunDate(Obligation $obligation): ?Carbon
    {
//        $startDate = Carbon::parse($obligation->start_date)->format('Y-m-d H:i:s');
        $startDate = Carbon::parse($obligation->start_date);

        return match ($obligation->frequency) {
            ObligationFrequency::DAILY => $startDate->addDay(),
            ObligationFrequency::WEEKLY => $startDate->addWeek(),
            ObligationFrequency::MONTHLY => $startDate->addMonth(),
            ObligationFrequency::QUARTERLY => $startDate->addMonths(3),
            ObligationFrequency::SEMI_ANNUALLY => $startDate->addMonths(6),
            ObligationFrequency::YEARLY => $startDate->addYear(),
            default => null,
        };
    }

    public function createOrUpdateTaskForService(Obligation $obligation, int $serviceId, array $employeeIds)
    {
        $service = Service::find($serviceId);

        if (!$service) {
            Log::warning("Service not found for ID: {$serviceId}");
            return;
        }

        // Check if a task already exists for the given obligation and service name
        $existingTask = $obligation->tasks()
            ->where('name', $service->name)
            ->first();

        if ($existingTask) {
            // Update the existing task
            $existingTask->update([
                'name' => $service->name,
                'description' => $obligation->description,
                'due_date' => $obligation->next_run,
            ]);

            // Sync employees for the task
            $existingTask->employees()->sync($employeeIds);

            Log::info('Updated task for service', [
                'task_id' => $existingTask->id,
                'service_id' => $serviceId,
            ]);
        } else {
            // Create a new task if it does not exist
            $this->createTaskForService($obligation, $serviceId, $employeeIds);
        }
    }

    public function createTaskForObligation(Obligation $obligation, array $employeeIds)
    {
        app(TaskService::class)->createTaskWithEmployees([
            'name' => $obligation->name,
            'description' => $obligation->description,
            'due_date' => $obligation->next_run,
            'status' => false,
            'obligation_id' => $obligation->id,
            'employee_ids' => $employeeIds,
        ]);
    }

    public function generateTasksForAllObligations()
    {
        DB::transaction(function () {
            Log::info('Starting task generation for all obligations');

            // Fetch all obligations
            $obligations = Obligation::all();

            foreach ($obligations as $obligation) {
                // Calculate next run date
                $nextRunDate = $this->calculateNextRunDate($obligation);

                if (!$nextRunDate) {
                    Log::warning('Unable to calculate next run date for obligation', [
                        'obligation_id' => $obligation->id,
                    ]);
                    continue;
                }

                $obligation->next_run = $nextRunDate;
                $obligation->save();

                // Fetch all associated services
                $services = $obligation->services;

                foreach ($services as $service) {
                    // Use default admin employee for tasks or fetch specific employees if needed
                    $employeeIds = $obligation->employees()->select('employees.id')->pluck('id')->toArray();
                    if (empty($employeeIds)) {
                        $employeeIds = [1];
                    }

                    // Create or update task for the service
                    $this->createOrUpdateTaskForService($obligation, $service->id, $employeeIds);
                }
            }

            Log::info('Task generation for all obligations completed successfully');
        });
    }
}
