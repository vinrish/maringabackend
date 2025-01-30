<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClientFolder extends Model
{

    protected $fillable = ['client_id', 'folder_path', 'folder_name', 'parent_folder_id'];

    public function parent()
    {
        return $this->belongsTo(ClientFolder::class, 'parent_folder_id');
    }

    public function children()
    {
        return $this->hasMany(ClientFolder::class, 'parent_folder_id');
    }

    public static function createClientFolder($client_id, $folder_uuid, $folder_name, $parent_folder_id = null)
    {
        $storageClientFolder = 'client_files/' . $folder_uuid;

        // Ensure the directory exists
        if (!Storage::disk('client_files')->exists($folder_uuid)) {
            Storage::disk('client_files')->makeDirectory($folder_uuid);
        }

        // Save the folder details
        return self::create([
            'client_id' => $client_id,
            'folder_path' => $folder_uuid,
            'folder_name' => $folder_name,
            'parent_folder_id' => $parent_folder_id,
        ]);
    }
}
//    protected $fillable = ['client_id', 'folder_path', 'folder_name'];
//
//    public static function createClientFolder($client_id, $folder_uuid, $folder_name)
//    {
//        $storageClientFolder = 'client_files/' . $folder_uuid;
//        $baseClientFolder = base_path('client_files/' . $folder_uuid);
//
//        // Create directory in the storage directory
//        if (!Storage::disk('client_files')->exists($folder_uuid)) {
//            Log::info('Creating client folder in storage: ' . $storageClientFolder);
//            Storage::disk('client_files')->makeDirectory($folder_uuid);
//        }
//
//        // Create directory in the base path
//        if (!file_exists($baseClientFolder)) {
//            Log::info('Creating client folder in base: ' . $baseClientFolder);
//            mkdir($baseClientFolder, 0755, true);
//        }
//
//        // Save the folder details
//        return self::create([
//            'client_id' => $client_id,
//            'folder_path' => $folder_uuid,
//            'folder_name' => $folder_name,
//        ]);
//    }
//}
