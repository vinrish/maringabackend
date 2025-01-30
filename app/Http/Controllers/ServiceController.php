<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cacheKey = 'services_' . $request->input('page', 1) . '_' . $request->input('itemsPerPage', 10) . '_' . $request->input('q') . '_' . $request->input('sortBy') . '_' . $request->input('orderBy');

        // Cache for 1 hour (60 minutes * 60 seconds)
        $services = Cache::remember($cacheKey, 60 * 60, function () use ($request) {
                $query =Service::query()
                ->whereNull('deleted_at');

            // Search functionality (e.g., by service name)
            if ($request->has('q')) {
                $searchTerm = $request->input('q');
                $query->where('name', 'like', "%$searchTerm%");
            }

            // Sorting functionality
            if ($request->has('sortBy') && $request->has('orderBy')) {
                $sortBy = $request->input('sortBy');
                $orderBy = $request->input('orderBy', 'asc');
                $query->orderBy($sortBy, $orderBy);
            }

            // Pagination
            $itemsPerPage = $request->input('itemsPerPage', 10);
            return $query->paginate($itemsPerPage);
        });

        // Structure the response
        return response()->json([
            'services' => $services->items(),
            'total' => $services->total(),
            'message' => 'success',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|string',
            'status' => 'required|boolean',
        ]);

        DB::transaction(function () use ($request) {
            $uuid = \Illuminate\Support\Str::uuid();

            $service = new Service([
                'uuid' => $uuid,
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
                'price' => $request->price,
            ]);

            $service->save();
        }, 10);

        return response()->json([
            'message' => 'Successfully created service!'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        return response()->json([
            'service' => $service,
            'message' => 'success',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        return response()->json([
            'service' => $service,
            'message' => 'success',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        // Validate the incoming data
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'status' => 'required|boolean',
        ]);

        // Use DB transaction for updating the service
        DB::transaction(function () use ($request, $service) {
            $service->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'status' => $request->status,
            ]);
        }, 10);

        return response()->json([
            'message' => 'Successfully updated service!',
            'service' => $service,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        // Soft delete the service
        $service->delete();

        return response()->json([
            'message' => 'Successfully deleted service!',
        ]);
    }
}
