<?php

namespace App\Services;

use App\Enums\ObligationFrequency;
use App\Models\{Obligation, Service, Task, FeeNote};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ObligationService1
{
    public function createObligationWithTask(array $data): Obligation
    {
        DB::beginTransaction();

        try {
            // Calculate next run date
            $nextRunDate = $this->calculateNextRunDate($data['start_date'], $data['frequency']);

            // Create the obligation
            $obligation = Obligation::create(array_merge($data, ['next_run' => $nextRunDate]));

            // Attach services and create tasks
            foreach ($data['services'] as $serviceData) {
                $service = Service::findOrFail($serviceData['service_id']);
                $price = $serviceData['adjusted_price'] ?? $service->price;

                $obligation->services()->attach($service->id, ['price' => $price]);

                $task = Task::create([
                    'name' => $service->name,
                    'description' => $service->description,
                    'due_date' => $nextRunDate,
                    'status' => 0,
                    'obligation_id' => $obligation->id,
                ]);

                // Assign employees to task
                if (!empty($data['employee_ids'])) {
                    $task->employees()->syncWithoutDetaching($data['employee_ids']);
                }
            }

            DB::commit();
            return $obligation;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create obligation', ['error' => $e->getMessage()]);
            throw $e;
        }
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

    public function createFeeNoteForTask(int $taskId): FeeNote
    {
        // Check if a FeeNote already exists for the task
        if (FeeNote::where('task_id', $taskId)->exists()) {
            throw new \Exception("FeeNote already exists for this task.");
        }

        $task = Task::findOrFail($taskId);
        $obligation = $task->obligation;
        $service = $obligation->services()->where('id', $task->obligation_id)->first();

        if (!$service) {
            throw new \Exception("Service for task not found.");
        }

        return FeeNote::create([
            'task_id' => $task->id,
            'client_id' => $obligation->client_id,
            'company_id' => $obligation->company_id,
            'amount' => $service->pivot->price,
            'status' => 0,
        ]);
//        $task = Task::findOrFail($taskId);
//        $obligation = $task->obligation;
//        $service = $obligation->services()->where('id', $task->obligation_id)->first();
//
//        if (!$service) {
//            throw new \Exception("Service for task not found.");
//        }
//
//        return FeeNote::create([
//            'task_id' => $task->id,
//            'client_id' => $obligation->client_id,
//            'company_id' => $obligation->company_id,
//            'amount' => $service->pivot->price,
//            'status' => 0,
//        ]);
    }

    public function completeTask(int $taskId)
    {
        $task = Task::findOrFail($taskId);

        if ($task->status === 1) {
            throw new \Exception("Task already completed.");
        }

        DB::transaction(function () use ($task) {
            $task->update(['status' => 1]);

            // Trigger FeeNote creation upon task completion
            $this->createFeeNoteForTask($task->id);

            // Update obligation's last_run to the current date
            $task->obligation->update(['last_run' => now()]);
        });
    }

    private function calculateNextRunDate(string $startDate, int $frequency): Carbon
    {
        $start = Carbon::parse($startDate);

        return match ($frequency) {
            0 => $start->addDay(),       // Daily
            1 => $start->addWeek(),      // Weekly
            2 => $start->addMonth(),     // Monthly
            3 => $start->addMonths(3),   // Quarterly
            4 => $start->addMonths(6),   // Semi-annually
            5 => $start->addYear(),      // Yearly
            default => throw new \Exception('Invalid frequency value')
        };
    }
}
