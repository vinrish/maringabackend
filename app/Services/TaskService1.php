<?php

namespace App\Services;

use App\Models\FeeNote;
use App\Models\Obligation;
use App\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskService2
{
    public function createTaskWithEmployees(array $data)
    {
        return DB::transaction(function () use ($data) {
            $task = Task::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'due_date' => $data['due_date'],
                'status' => $data['status'],
                'obligation_id' => $data['obligation_id'],
            ]);

            // Sync employees to the task
            $task->employees()->syncWithoutDetaching($data['employee_ids']);

            // Store files for the task
            $this->storeFilesForTask($task, $data['files'] ?? []);

            return $task;
        });
    }

    public function createFeeNoteForTask(Task $task)
    {
        $obligation = $task->obligation;

        if ($obligation) {
            // Retrieve services and their prices for the obligation
            $servicesWithPrices = DB::table('services')
                ->join('obligation_service', 'obligation_service.service_id', '=', 'services.id')
                ->where('obligation_service.obligation_id', $obligation->id)
                ->select(
                    'services.id as service_id',
                    'services.name as service_name',
                    'obligation_service.price as service_price'
                )
                ->get();

            if ($servicesWithPrices->isEmpty()) {
                Log::warning('No services found for obligation', ['obligation_id' => $obligation->id]);
                return;
            }

            foreach ($servicesWithPrices as $service) {
                // Check if a fee note already exists for this task and service
                $existingFeeNote = FeeNote::where('task_id', $task->id)
                    ->where('client_id', $obligation->client_id)
                    ->where('company_id', $obligation->company_id)
                    ->where('amount', $service->service_price)
                    ->first();

                if ($existingFeeNote) {
                    Log::info('Fee note already exists for task and service', [
                        'task_id' => $task->id,
                        'service_id' => $service->service_id,
                    ]);
                    continue; // Skip creating a duplicate
                }

                // Create a new fee note for this service
                $feeNote = FeeNote::create([
                    'task_id' => $task->id,
                    'client_id' => $obligation->client_id,
                    'company_id' => $obligation->company_id,
                    'amount' => $service->service_price,
                    'status' => '0', // Assuming '0' is the initial status
                ]);

                Log::info('Fee note created for task and service', [
                    'task_id' => $task->id,
                    'service_id' => $service->service_id,
                    'service_name' => $service->service_name,
                    'fee_note_id' => $feeNote->id,
                    'amount' => $service->service_price,
                ]);
            }
        } else {
            Log::warning('Obligation not found for task', ['task_id' => $task->id]);
        }
    }

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
