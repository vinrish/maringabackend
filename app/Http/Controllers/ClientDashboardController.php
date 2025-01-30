<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Client;
use App\Models\Company;
use App\Models\FeeNote;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientDashboardController extends Controller
{
    /**
     * Return the currently logged-in client's details.
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object
     */
    protected function getClient()
    {
        $user = Auth::user();
        return Client::with('user', 'directors.company', 'feenotes.payments')
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Fetch invoices for a specific client.
     *
     * @param int $clientId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getInvoices($clientId)
    {
        return Invoice::where('client_id', $clientId)->get();
    }

    /**
     * Fetch fee notes for a specific client.
     *
     * @param int $clientId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getFeeNotes($clientId)
    {
        return FeeNote::where('client_id', $clientId)->get();
    }

    /**
     * Fetch company details associated with a specific client.
     *
     * @param \App\Models\Client $client
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getCompanyDetails($client)
    {
        if (!$client instanceof Client) {
            throw new \InvalidArgumentException('Invalid client object provided.');
        }

        // Fetch companies from directors
        $companiesFromDirectors = $client->directors()
            ->whereHas('company')
            ->with('company:id,name,phone,reg_date,reg_number')
            ->get()
            ->pluck('company')
            ->unique();

        // Fetch companies directly related to the client
        $directCompanies = Company::where('client_id', $client->id)
            ->select('id', 'name', 'phone', 'reg_date', 'reg_number')
            ->get();

        // Merge and ensure uniqueness of companies
        $companies = $companiesFromDirectors->merge($directCompanies)->unique('id');

        return $companies;
    }

    /**
     * Fetch company details associated with a specific client.
     *
     * @param \App\Models\Client $client
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getBusinessDetails($client)
    {
        if (!$client instanceof Client) {
            throw new \InvalidArgumentException('Invalid client object provided.');
        }

        // Fetch companies from directors
        $businessesFromDirectors = $client->directors()
            ->whereHas('business')
            ->with('business')
            ->get()
            ->pluck('business')
            ->unique();

        // Fetch companies directly related to the client
        $directBusinesses = Business::where('client_id', $client->id)
            ->get();

        // Merge and ensure uniqueness of companies
        $businesses = $businessesFromDirectors->merge($directBusinesses)->unique('id');

        return $businesses;
    }

    /**
     * Display the dashboard for the current client.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $client = $this->getClient();

        if (!$client instanceof Client) {
            return response()->json([
                'message' => 'Client data not found for the current user.',
            ], 404);
        }

        $totalFeeNoteAmount = $client->feenotes->sum('amount');
        $totalPaidAmount = $client->feenotes->flatMap(function ($feenote) {
            return $feenote->payments;
        })->sum('amount');
        $totalOutstandingAmount = $totalFeeNoteAmount - $totalPaidAmount;

        $invoices = $this->getInvoices($client->id);
        $feeNotes = $this->getFeeNotes($client->id);
        $companyDetails = $this->getCompanyDetails($client);
        $businessDetails = $this->getBusinessDetails($client);

        return response()->json([
            'client' => $client,
            'total_outstanding_amount' => $totalOutstandingAmount,
            'invoices' => $invoices,
            'fee_notes' => $feeNotes,
            'company_details' => $companyDetails,
            'businesses' => $businessDetails
        ], 200);
    }
}
