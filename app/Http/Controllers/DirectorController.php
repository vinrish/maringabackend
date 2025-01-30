<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Client;
use App\Models\Company;
use App\Models\Director;
use Illuminate\Http\Request;

class DirectorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $companyId = $request->query('company_id');
        $businessId = $request->query('business_id');

        $query = Director::query()
            ->with(['client.user', 'company', 'business']);

        // Search functionality
        if ($request->has('q')) {
            $searchTerm = $request->input('q');
            $query->whereHas('client.user', function ($subQuery) use ($searchTerm) {
                $subQuery->where('first_name', 'like', "%$searchTerm%")
                    ->orWhere('last_name', 'like', "%$searchTerm%")
                    ->orWhere('email', 'like', "%$searchTerm%");
            })
                ->orWhereHas('company', function ($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'like', "%$searchTerm%");
                })
                ->orWhereHas('business', function ($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'like', "%$searchTerm%");
                });
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        } elseif ($businessId) {
            $query->where('business_id', $businessId);
        }

        // Sorting functionality
        if ($request->has('sortBy') && $request->has('orderBy')) {
            $sortBy = $request->input('sortBy');
            $orderBy = $request->input('orderBy', 'asc');
            $query->orderBy($sortBy, $orderBy);
        }

        // Pagination
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $directors = $query->paginate($itemsPerPage);

        // Structure the response
        return response()->json([
            'directors' => $directors->items(),
            'total' => $directors->total(),
            'message' => 'success'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Fetch all clients, companies, and businesses
        $clients = Client::with('user:id,first_name,last_name')->get();
        $companies = Company::all();
        $businesses = Business::all();

        // Structure the data for dropdowns
        $clientOptions = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->user->first_name . ' ' . $client->user->last_name
            ];
        });

        return response()->json([
            'clients' => $clientOptions,   // Clients for dropdown
            'companies' => $companies,     // Companies for dropdown
            'businesses' => $businesses,   // Businesses for dropdown
            'message' => 'Create form data'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'company_id' => 'nullable|exists:companies,id',
            'business_id' => 'nullable|exists:businesses,id',
        ]);

        // Ensure either company_id or business_id is provided, but not both
        if (is_null($request->company_id) && is_null($request->business_id)) {
            return response()->json(['message' => 'Either company_id or business_id is required'], 400);
        }

        if (!is_null($request->company_id) && !is_null($request->business_id)) {
            return response()->json(['message' => 'You can only assign a director to either a company or a business, not both'], 400);
        }

        if (!is_null($request->company_id)) {
            $company = Company::findOrFail($request->company_id);

            // Check if this client is already a director of the company
            if ($company->directors()->where('client_id', $request->client_id)->exists()) {
                return response()->json(['message' => 'Client is already a director for this company'], 400);
            }

            // Create the director for the company
            $director = Director::create([
                'client_id' => $request->client_id,
                'company_id' => $request->company_id,
            ]);

            return response()->json([
                'message' => 'Director added to company successfully',
                'director' => $director,
            ]);
        }

        if (!is_null($request->business_id)) {
            $business = Business::findOrFail($request->business_id);

            // Check if this client is already a director of the business
            if ($business->directors()->where('client_id', $request->client_id)->exists()) {
                return response()->json(['message' => 'Client is already a director for this business'], 400);
            }

            // Create the director for the business
            $director = Director::create([
                'client_id' => $request->client_id,
                'business_id' => $request->business_id,
            ]);

            return response()->json([
                'message' => 'Director added to business successfully',
                'director' => $director,
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Director $director)
    {
        $director->load(['client.user', 'company', 'business']);

        return response()->json([
            'director' => $director,
            'message' => 'Director details retrieved successfully'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Director $director)
    {
        $director->load(['client.user', 'company', 'business']);
        $companies = Company::all();
        $businesses = Business::all();

        return response()->json([
            'director' => $director,
            'companies' => $companies,
            'businesses' => $businesses,
            'message' => 'Edit form data'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Director $director)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'company_id' => 'nullable|exists:companies,id',
            'business_id' => 'nullable|exists:businesses,id',
        ]);

        // Ensure either company_id or business_id is provided, but not both
        if (is_null($request->company_id) && is_null($request->business_id)) {
            return response()->json(['message' => 'Either company_id or business_id is required'], 400);
        }

        if (!is_null($request->company_id) && !is_null($request->business_id)) {
            return response()->json(['message' => 'You can only assign a director to either a company or a business, not both'], 400);
        }

        // Update logic for company
        if (!is_null($request->company_id)) {
            $company = Company::findOrFail($request->company_id);

            // Check if this client is already a director of the new company
            if ($company->directors()->where('client_id', $request->client_id)->exists()) {
                return response()->json(['message' => 'Client is already a director for this company'], 400);
            }

            $director->update([
                'client_id' => $request->client_id,
                'company_id' => $request->company_id,
                'business_id' => null // Unassign from business if updating to a company
            ]);
        }

        // Update logic for business
        if (!is_null($request->business_id)) {
            $business = Business::findOrFail($request->business_id);

            // Check if this client is already a director of the new business
            if ($business->directors()->where('client_id', $request->client_id)->exists()) {
                return response()->json(['message' => 'Client is already a director for this business'], 400);
            }

            $director->update([
                'client_id' => $request->client_id,
                'business_id' => $request->business_id,
                'company_id' => null // Unassign from company if updating to a business
            ]);
        }

        return response()->json([
            'message' => 'Director updated successfully',
            'director' => $director
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Director $director)
    {
        $director->delete();

        return response()->json([
            'message' => 'Director deleted successfully'
        ]);
    }
}
