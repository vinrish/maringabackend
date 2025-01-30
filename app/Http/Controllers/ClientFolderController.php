<?php

namespace App\Http\Controllers;

use App\Models\ClientFile;
use App\Models\ClientFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClientFolderController extends Controller
{

    public function index()
    {
        $clientFolders = ClientFolder::all();

        if ($clientFolders->isEmpty()) {
            return response()->json(['message' => 'No client folders found'], 404);
        }

        $foldersData = $clientFolders->map(function ($folder) {
            $files = Storage::disk('client_files')->files($folder->folder_path);
            return [
                'id' => $folder->id,
                'client_id' => $folder->client_id,
                'folder_name' => $folder->folder_name,
                'folder_path' => $folder->folder_path,
                'files' => array_map(fn($file) => basename($file), $files),
            ];
        });

        return response()->json(['client_folders' => $foldersData]);
    }

    public function store(Request $request, $client_id)
    {
        $request->validate([
            'file' => 'required|file',
            'file_name' => 'required|string|max:255',
        ]);

        // Ensure the client folder exists
        $clientFolder = ClientFolder::firstOrCreate(
            ['client_id' => $client_id],
            ['folder_path' => (string)\Str::uuid(), 'folder_name' => "Client $client_id"]
        );

        // Store the file
        $file = $request->file('file');
        $path = $file->storeAs($clientFolder->folder_path, $request->file_name, 'client_files');

        // Save file details in the database
        $clientFile = ClientFile::create([
            'client_folder_id' => $clientFolder->id,
            'file_name' => $request->file_name,
            'file_path' => $path,
        ]);

        return response()->json(['message' => 'File uploaded successfully', 'file' => $clientFile], 201);
    }

    public function createFolder(Request $request, $client_id)
    {
        $request->validate(['folder_name' => 'required|string|max:255']);

        // Create folder
        $folder_uuid = (string)\Str::uuid();
        $clientFolder = ClientFolder::createClientFolder($client_id, $folder_uuid, $request->folder_name);

        return response()->json(['message' => 'Folder created successfully', 'folder' => $clientFolder], 201);
    }

    public function listFiles($client_folder_id)
    {
        $clientFolder = ClientFolder::findOrFail($client_folder_id);

        $files = Storage::disk('client_files')->files($clientFolder->folder_path);

        return response()->json(['files' => $files]);
    }

    public function createSubFolder(Request $request, $client_id, $parent_folder_id)
    {
        $request->validate(['folder_name' => 'required|string|max:255']);

        // Check if parent folder exists
        $parentFolder = ClientFolder::findOrFail($parent_folder_id);

        $folder_uuid = (string)\Str::uuid();
        $clientFolder = ClientFolder::createClientFolder(
            $client_id,
            $folder_uuid,
            $request->folder_name,
            $parent_folder_id
        );

        return response()->json(['message' => 'Subfolder created successfully', 'folder' => $clientFolder], 201);
    }

    public function viewFolderContent($folder_id)
    {
        $folder = ClientFolder::with(['children', 'parent'])->findOrFail($folder_id);

        $files = Storage::disk('client_files')->files($folder->folder_path);

        return response()->json([
            'folder' => [
                'id' => $folder->id,
                'client_id' => $folder->client_id,
                'name' => $folder->folder_name,
                'path' => $folder->folder_path,
                'parent_folder_id' => $folder->parent_folder_id,
            ],
            'subfolders' => $folder->children->map(function ($child) {
                return [
                    'id' => $child->id,
                    'name' => $child->folder_name,
                    'path' => $child->folder_path,
                ];
            }),
            'files' => array_map(fn($file) => basename($file), $files),
        ]);
    }

//    public function index()
//    {
//        $clientFolders = ClientFolder::get();
//
//        if ($clientFolders->isEmpty()) {
//            return response()->json(['message' => 'No client folders found'], 404);
//        }
//
//        $foldersData = [];
//        foreach ($clientFolders as $folder) {
//            $files = Storage::disk('client_files')->files($folder->folder_path);
//            $foldersData[] = [
//                'folder_name' => $folder->folder_name,
//                'folder_path' => $folder->folder_path,
//                'files' => $files,
//            ];
//        }
//
//        return response()->json([
//            'client_folders' => $foldersData
//        ]);
//    }
//    public function store(Request $request, $client_id)
//    {
//        $clientFolder = ClientFolder::where('client_id', $client_id)->first();
//
//        if (!$clientFolder) {
//            return response()->json(['message' => 'Client folder not found'], 404);
//        }
//
//        $request->validate([
//            'file' => 'required|file',
//            'file_name' => 'required|string|max:255', // Validation for file_name
//        ]);
//
//        // Store the file with the specified file name
//        $file = $request->file('file');
//        $path = $file->storeAs($clientFolder->folder_path, $request->file_name, 'client_files');
//
//        // Save file details in the database
//        ClientFile::create([
//            'client_folder_id' => $clientFolder->id, // Reference the created folder
//            'file_name' => $request->file_name,
//            'file_path' => $path,
//        ]);
//
//        return response()->json(['message' => 'File uploaded successfully', 'path' => $path]);
//    }
//
//    public function createFolder(Request $request, $client_id)
//    {
//        $request->validate([
//            'folder_name' => 'required|string|max:255',
//        ]);
//
//        // Generate a unique folder UUID
//        $folder_uuid = (string) \Str::uuid();
//
//        // Create the client folder
//        $clientFolder = ClientFolder::createClientFolder($client_id, $folder_uuid, $request->folder_name);
//
//        return response()->json(['message' => 'Folder created successfully', 'folder' => $clientFolder], 201);
//    }
}
