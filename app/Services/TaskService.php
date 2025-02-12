<?php

namespace App\Services;

use App\Jobs\CompleteTaskJob;
use App\Models\FeeNote;
use App\Models\Obligation;
use App\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskService
{
    public function createTaskWithEmployees(array $data)
    {
        return DB::transaction(function () use ($data) {
            $task = Task::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'],
                'status' => false,
                'obligation_id' => $data['obligation_id'] ?? null,
                'client_id' => $data['client_id'] ?? null,
                'price' => $data['price'] ?? 0,
            ]);

            // Sync employees to the task
            $task->employees()->syncWithoutDetaching($data['employee_ids']);

            // If the task is NOT part of an obligation (non-recurring), mark it as complete
            if (!$task->obligation_id) {
                $this->markTaskAsComplete($task);
            }

            return $task;
        });
    }

    public function markTaskAsComplete(Task $task)
    {
//        dispatch(new CompleteTaskJob($task->id));
        $this->createStandaloneFeeNote($task);
        $task->update(['status' => 1]);
//        Log::info("Task marked as completed", ['task_id' => $task->id]);
    }

//    public function completeTask(Task $task)
//    {
//        // Check if the task is already completed
//        if ($task->status === 'complete') {
//            Log::info('Task already marked as complete', ['task_id' => $task->id]);
//            return response()->json(['message' => 'Task is already complete.'], 400);
//        }
//
//        // Update task status to complete
//        $task->update(['status' => true]);
//
//        // Create fee note for the task
//        $this->createFeeNoteForTask($task);
//
//        Log::info('Task marked as complete', ['task_id' => $task->id]);
//
//        return response()->json(['message' => 'Task marked as complete and fee note created.'], 200);
//    }

//    public function createFeeNoteForTask(Task $task)
//    {
//        $obligation = $task->obligation;
//
//        if ($obligation) {
//            $totalAdjustedFee = $obligation->services()
//                ->withPivot('price')
//                ->get()
//                ->sum('pivot.price');
//            // Create a fee note with the obligation details
//            $feeNote = FeeNote::create([
//                'task_id' => $task->id,
//                'client_id' => $obligation->client_id,
//                'company_id' => $obligation->company_id,
//                'amount' => $totalAdjustedFee,
//                'status' => '0', // Assuming '0' is the initial status
//            ]);
//
//            Log::info('Fee note created for task', [
//                'task_id' => $task->id,
//                'fee_note_id' => $feeNote->id,
//                'amount' => $totalAdjustedFee,
//            ]);
//        }
//    }

//    public function createFeeNoteForTask(Task $task)
//    {
//        $obligation = $task->obligation;
//
//        if (!$obligation) {
//            Log::warning('Obligation not found for task', ['task_id' => $task->id]);
//            return;
//        }
//
//        // Fetch the services linked to the task using the obligation_service pivot table
//        $services = $task->obligation->services()->wherePivot('task_id', $task->id)->get();
//
//        if ($services->isEmpty()) {
//            Log::warning('No services found for task in obligation', ['task_id' => $task->id]);
//            return;
//        }
//
//        // Iterate over all services associated with the task and obligation
//        foreach ($services as $service) {
//            // Fetch the price from the pivot table (adjusted price)
//            $adjustedPrice = $service->pivot->price;
//
//            if (!$adjustedPrice) {
//                Log::warning('Service price missing for task', [
//                    'task_id' => $task->id,
//                    'service_id' => $service->id,
//                ]);
//                continue;
//            }
//
//            // Check if a FeeNote already exists for this task, client, company, and service combination
//            $existingFeeNote = FeeNote::where('task_id', $task->id)
//                ->where('client_id', $obligation->client_id)
//                ->where('company_id', $obligation->company_id)
//                ->where('business_id', $obligation->client->business_id)
//                ->where('service_id', $service->id)
//                ->exists();
//
//            if ($existingFeeNote) {
//                Log::info('Fee note already exists for task and service', [
//                    'task_id' => $task->id,
//                    'service_id' => $service->id,
//                    'amount' => $adjustedPrice,
//                ]);
//                continue;
//            }
//
//            // Create a new FeeNote with the adjusted price
//            FeeNote::create([
//                'task_id' => $task->id,
//                'client_id' => $obligation->client_id,
//                'company_id' => $obligation->company_id,
//                'business_id' => $obligation->client->business_id,
//                'service_id' => $service->id,  // Include the service ID
//                'amount' => $adjustedPrice,  // Use the adjusted price
//                'status' => '0', // Assuming '0' is the initial status
//            ]);
//
//            Log::info('Fee note created for task and service', [
//                'task_id' => $task->id,
//                'service_id' => $service->id,
//                'service_name' => $service->name,
//                'amount' => $adjustedPrice,
//            ]);
//        }
//    }

    public function createFeeNoteForTask(Task $task)
    {
        Log::info('Checking obligation for task', ['task_id' => $task->id, 'obligation_id' => $task->obligation_id]);

        $obligation = $task->obligation;

        if (!$obligation) {
            Log::warning('Obligation not found for task', ['task_id' => $task->id]);
            return;
        }

        // Retrieve the obligation associated with the task
        // Retrieve the service associated with the task name
        $serviceWithPrice = DB::table('services')
            ->join('obligation_service', 'obligation_service.service_id', '=', 'services.id')
            ->where('obligation_service.obligation_id', $obligation->id)
            ->where('services.name', $task->name) // Match the service name to the task name
            ->select(
                'services.id as service_id',
                'services.name as service_name',
                'obligation_service.price as service_price'
            )
            ->first();

        if (!$serviceWithPrice) {
            Log::warning('No matching service found for task', [
                'task_id' => $task->id,
                'task_name' => $task->name,
                'obligation_id' => $obligation->id,
            ]);
            return;
        }

        // Start a database transaction
        DB::beginTransaction();
        try {
            // Check if a fee note already exists for this task and service
            $existingFeeNote = FeeNote::where('task_id', $task->id)
                ->where('client_id', $obligation->client_id)
                ->where('company_id', $obligation->company_id)
                ->where('amount', $serviceWithPrice->service_price)
                ->first();

            if ($existingFeeNote) {
                Log::info('Fee note already exists for task and service', [
                    'task_id' => $task->id,
                    'service_id' => $serviceWithPrice->service_id,
                ]);
                DB::rollBack(); // Rollback as we do not proceed if a fee note already exists
                return;
            }

            // Create a new fee note for this task and service
            FeeNote::create([
                'task_id' => $task->id,
                'client_id' => $obligation->client_id ?? $task->client_id,
                'company_id' => $obligation->company_id,
                'amount' => $serviceWithPrice->service_price,
                'status' => '0',
            ]);

            Log::info('Fee note created for task and service', [
                'task_id' => $task->id,
                'service_id' => $serviceWithPrice->service_id,
                'service_name' => $serviceWithPrice->service_name,
                'amount' => $serviceWithPrice->service_price,
            ]);

            // Update task status to 1 (completed)
            $task->update(['status' => 1]);

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback transaction in case of failure
            DB::rollBack();
            Log::error('Failed to create fee note for task', [
                'task_id' => $task->id,
                'service_id' => $serviceWithPrice->service_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createStandaloneFeeNote(Task $task)
    {
        Log::info('Creating standalone fee note for task', ['task_id' => $task->id]);

        $existingFeeNote = FeeNote::where('task_id', $task->id)->first();
        if ($existingFeeNote) {
            Log::info('Standalone fee note already exists', ['task_id' => $task->id]);
            return;
        }

        DB::beginTransaction();
        try {
            $feeNote = FeeNote::create([
                'task_id' => $task->id,
                'client_id' => $task->client_id,
                'company_id' => $task->company_id,
                'amount' => $task->price ?? 0, // Ensure price exists
                'status' => '0',
            ]);

            Log::info('Standalone fee note created', [
                'task_id' => $task->id,
                'fee_note_id' => $feeNote->id
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create standalone fee note', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
        }
    }


//    public function createFeeNoteForTask(Task $task)
//    {
//        // Retrieve the obligation associated with the task
//        $obligation = $task->obligation;
//
//        if ($obligation) {
//            // Retrieve the service associated with the task name
//            $serviceWithPrice = DB::table('services')
//                ->join('obligation_service', 'obligation_service.service_id', '=', 'services.id')
//                ->where('obligation_service.obligation_id', $obligation->id)
//                ->where('services.name', $task->name) // Match the service name to the task name
//                ->select(
//                    'services.id as service_id',
//                    'services.name as service_name',
//                    'obligation_service.price as service_price'
//                )
//                ->first();
//
//            if (!$serviceWithPrice) {
//                Log::warning('No matching service found for task', [
//                    'task_id' => $task->id,
//                    'task_name' => $task->name,
//                    'obligation_id' => $obligation->id,
//                ]);
//                return;
//            }
//
//            // Check if a fee note already exists for this task and service
//            $existingFeeNote = FeeNote::where('task_id', $task->id)
//                ->where('client_id', $obligation->client_id)
//                ->where('company_id', $obligation->company_id)
//                ->where('amount', $serviceWithPrice->service_price)
//                ->first();
//
//            if ($existingFeeNote) {
//                Log::info('Fee note already exists for task and service', [
//                    'task_id' => $task->id,
//                    'service_id' => $serviceWithPrice->service_id,
//                ]);
//                return; // Skip creating a duplicate
//            }
//
//            // Create a new fee note for this task and service
//            try {
//                DB::transaction(function () use ($task, $serviceWithPrice, $obligation) {
//                    FeeNote::create([
//                        'task_id' => $task->id,
//                        'client_id' => $obligation->client_id,
//                        'company_id' => $obligation->company_id,
//                        'amount' => $serviceWithPrice->service_price,
//                        'status' => '0',
//                    ]);
//
//                    Log::info('Fee note created for task and service', [
//                        'task_id' => $task->id,
//                        'service_id' => $serviceWithPrice->service_id,
//                        'service_name' => $serviceWithPrice->service_name,
//                        'amount' => $serviceWithPrice->service_price,
//                    ]);
//                });
//            } catch (\Exception $e) {
//                Log::error('Failed to create fee note for task', [
//                    'task_id' => $task->id,
//                    'service_id' => $serviceWithPrice->service_id,
//                    'error' => $e->getMessage(),
//                ]);
//            }
//        } else {
//            Log::warning('Obligation not found for task', ['task_id' => $task->id]);
//        }
//    }
    private function storeFilesForTask(Task $task, array $files)
    {
        if (empty($files)) {
            return; // No files to store
        }

        $clientFolder = $task->obligation->client->folder_path ?? null;

        foreach ($files as $file) {
            // Validate the file (you can customize this based on your requirements)
            if ($file instanceof UploadedFile) {
                // Generate a unique filename
                $fileName = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());

                // Define the file path
                $filePath = 'client_files/' . $clientFolder . '/' . $fileName;

                // Store the file in the specified directory
                $file->storeAs($filePath, $fileName, 'client_files'); // Ensure 'client_files' disk is configured

                Log::info('File stored for task', [
                    'task_id' => $task->id,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                ]);
            } else {
                Log::warning('Invalid file type received', [
                    'task_id' => $task->id,
                    'file' => $file,
                ]);
            }
        }
    }

    protected function calculateFeeAmount($obligationId)
    {
        // Logic to calculate fee based on obligation
        // For example, you might fetch the obligation and return its fee
        $obligation = Obligation::find($obligationId);
        return $obligation ? $obligation->fee : 0; // Default to 0 if not found
    }

    public function updateTaskWithEmployees(Task $task, array $data)
    {
        return DB::transaction(function () use ($task, $data) {
            $task->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'due_date' => $data['due_date'],
                'status' => $data['status'],
                'obligation_id' => $data['obligation_id'],
            ]);

            // Sync employees to the task
            $task->employees()->sync($data['employee_ids']);

            // Store files for the task
            $this->storeFilesForTask($task, $data['files'] ?? []);

            return $task;
        });
    }
}
