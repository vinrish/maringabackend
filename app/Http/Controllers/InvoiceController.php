<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\FeeNote;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Invoice::query()
            ->with(['client.user', 'business', 'company', 'feeNotes']);

        // Search functionality
        if ($request->has('q')) {
            $searchTerm = $request->input('q');
            $query->whereHas('client.user', function ($userQuery) use ($searchTerm) {
                $userQuery->where('first_name', 'like', "%$searchTerm%")
                    ->orWhere('last_name', 'like', "%$searchTerm%")
                    ->orWhere('email', 'like', "%$searchTerm%");
            })->orWhereHas('company', function ($companyQuery) use ($searchTerm) {
                $companyQuery->where('name', 'like', "%$searchTerm%");
            })->orWhereHas('business', function ($businessQuery) use ($searchTerm) {
                $businessQuery->where('name', 'like', "%$searchTerm%");
            });
        }

        // Status filter
        if ($request->has('status')) {
            $status = (int) $request->input('status');
            $query->where('status', $status);
        }

        // Sorting
        if ($request->has('sortBy') && $request->has('orderBy')) {
            $sortBy = $request->input('sortBy');
            $orderBy = $request->input('orderBy', 'asc');
            $query->orderBy($sortBy, $orderBy);
        } else {
            $query->orderBy('created_at', 'desc'); // Default sorting
        }

        // Pagination
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $invoices = $query->paginate($itemsPerPage);

        // Structure the data for response
        $data = $invoices->map(function ($invoice) {
            $client = $invoice->client;
            $user = $client ? $client->user : null;

            $name = 'No Name';
            if ($client) {
                $name = $user ? $user->first_name . ' '. $user->middle_name . ' ' . $user->last_name : 'No Client';
            } elseif ($invoice->company_id && $invoice->company) {
                $name = $invoice->company->name;
            } elseif ($invoice->business_id && $invoice->business) {
                $name = $invoice->business->name;
            }

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_id' => $invoice->client_id,
                'client_name' => $name,
                'business_id' => $invoice->business_id,
                'company_id' => $invoice->company_id,
                'total_amount' => $invoice->total_amount,
                'amount_paid' => $invoice->amount_paid,
                'status' => $invoice->status->label(),
                'created_at' => $invoice->created_at,
                'updated_at' => $invoice->updated_at,
            ];
        });

        // Return paginated data
        return response()->json([
            'invoices' => $data->values(),
            'total' => $invoices->total(),
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
            'client_id' => ['nullable', 'exists:clients,id'],
            'business_id' => ['nullable', 'exists:businesses,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'fee_note_ids' => ['required', 'array'],
            'fee_note_ids.*' => ['exists:fee_notes,id'],
        ]);

        $usedFeeNotes = FeeNote::whereIn('id', $request->fee_note_ids)
            ->whereHas('invoices')
            ->pluck('id')
            ->toArray();

        if (!empty($usedFeeNotes)) {
            return response()->json([
                'message' => 'Some fee notes are already associated with other invoices.',
                'used_fee_note_ids' => $usedFeeNotes,
            ], 400);
        }

        $feeNotes = FeeNote::whereIn('id', $request->fee_note_ids)->get();
        $totalAmount = $feeNotes->sum('amount');

        $currentYear = now()->year;
        $latestInvoice = Invoice::whereYear('created_at', $currentYear)->latest()->first();
        $nextInvoiceNumber = $latestInvoice ? intval(substr($latestInvoice->invoice_number, -4)) + 1 : 1;
        $invoiceNumber = sprintf('INV-%d-%04d', $currentYear, $nextInvoiceNumber);

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'client_id' => $request->client_id,
            'business_id' => $request->business_id,
            'company_id' => $request->company_id,
            'total_amount' => $totalAmount,
            'status' => InvoiceStatus::Pending,
        ]);

        $invoice->feeNotes()->attach($feeNotes);

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status' => $invoice->status->label(),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $invoice = Invoice::with([
            'client.user',
            'client',
            'business',
            'company',
            'feeNotes.payments',
        ])->findOrFail($id);

        // Map the fee notes to include their payments
        $feeNotes = $invoice->feeNotes->map(function ($feeNote) {
            return [
                'id' => $feeNote->id,
                'amount' => $feeNote->amount,
                'status' => $feeNote->status,
                'fee_name' => $feeNote->task->name,
                'created_at' => $feeNote->created_at,
                'paid' => $feeNote->payments->sum('amount'),
                'balance' => ($feeNote->amount)-($feeNote->payments->sum('amount')),
            ];
        });

        // Consolidate all payments from the feeNotes
        $allPayments = $invoice->feeNotes->flatMap(function ($feeNote) {
            return $feeNote->payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'transaction_reference' => $payment->transaction_reference,
                    'date' => $payment->created_at,
                ];
            });
        });

        // Build the response structure
        $response = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'client' => $invoice->client ? [
                'id' => $invoice->client->id,
                'name' => $invoice->client->user->first_name . ' ' . $invoice->client->user->last_name . ' ' . $invoice->client->user->middle_name ,
                'email' => $invoice->client->user->email,
                'kra_pin' => $invoice->client->kra_pin,
            ] : null,
            'business' => $invoice->business ? [
                'id' => $invoice->business->id,
                'name' => $invoice->business->name,
                'kra_pin' => $invoice->business->kra_pin,
                'email' => $invoice->business->business_email,
            ] : null,
            'company' => $invoice->company ? [
                'id' => $invoice->company->id,
                'name' => $invoice->company->name,
                'kra_pin' => $invoice->company->kra_pin,
                'email' => $invoice->company->email,
            ] : null,
            'total_amount' => $invoice->total_amount,
            'amount_paid' => $invoice->amount_paid,
            'status' => $invoice->status->label(),
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
            'fee_notes' => $feeNotes,
            'payments' => $allPayments->values(), // Convert to collection and reset keys
        ];

        return response()->json([
            'message' => 'Invoice retrieved successfully',
            'data' => $response,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function generateInvoice(Request $request)
    {
        $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'business_id' => ['nullable', 'exists:businesses,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'fee_note_ids' => ['required', 'array'],
            'fee_note_ids.*' => ['exists:fee_notes,id'],
        ]);

        $feeNotes = FeeNote::whereIn('id', $request->fee_note_ids)->get();
        $totalAmount = $feeNotes->sum('amount');

        $invoice = Invoice::create([
            'client_id' => $request->client_id,
            'business_id' => $request->business_id,
            'company_id' => $request->company_id,
            'total_amount' => $totalAmount,
            'status' => InvoiceStatus::Pending,
        ]);

        $invoice->feeNotes()->attach($feeNotes);

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice_id' => $invoice->id,
            'status' => $invoice->status->label()
        ], 201);
    }

    public function makePayment(Request $request, $invoiceId)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'transaction_reference' => ['nullable']
        ]);

        $invoice = Invoice::with('feeNotes.payments')->findOrFail($invoiceId);

        $remainingAmount = $invoice->total_amount - $invoice->amount_paid;
        $paymentAmount = min($request->amount, $remainingAmount);

        if ($paymentAmount <= 0) {
            return response()->json(['error' => 'Invoice is already fully paid.'], 400);
        }

        DB::transaction(function () use ($invoice, $paymentAmount, $request) {
            $invoice->amount_paid += $paymentAmount;

            $feeNotes = $invoice->feeNotes;
            foreach ($feeNotes as $feeNote) {
                $due = $feeNote->amount - $feeNote->payments->sum('amount');

                if ($paymentAmount <= 0) {
                    break;
                }

                $paidAmount = min($paymentAmount, $due);

                // Only create a payment record if the paidAmount is greater than 0
                if ($paidAmount > 0) {
                    $feeNote->payments()->create([
                        'amount' => $paidAmount,
                        'payment_method' => $request->payment_method,
                        'transaction_reference' => $request->transaction_reference
                    ]);

                    $paymentAmount -= $paidAmount;
                }
            }

            // Update the invoice status using the enum
            if ($invoice->amount_paid == 0) {
                $invoice->status = InvoiceStatus::Pending;
            } elseif ($invoice->amount_paid < $invoice->total_amount) {
                $invoice->status = InvoiceStatus::Partial;
            } else {
                $invoice->status = InvoiceStatus::Complete;
            }

            $invoice->save();
        });

        return response()->json([
            'message' => 'Payment processed successfully',
            'invoice_status' => $invoice->status->label(), // Return readable status
        ], 200);
    }

//    public function makePayment(Request $request, $invoiceId)
//    {
//        $request->validate([
//            'amount' => ['required', 'numeric', 'min:0.01'],
//        ]);
//
//        $invoice = Invoice::with('feeNotes.payments')->findOrFail($invoiceId);
//
//        $remainingAmount = $invoice->total_amount - $invoice->amount_paid;
//        $paymentAmount = min($request->amount, $remainingAmount);
//
//        if ($paymentAmount <= 0) {
//            return response()->json(['error' => 'Invoice is already fully paid.'], 400);
//        }
//
//        DB::transaction(function () use ($invoice, $paymentAmount) {
//            $invoice->amount_paid += $paymentAmount;
//
//            $feeNotes = $invoice->feeNotes;
//            foreach ($feeNotes as $feeNote) {
//                $due = $feeNote->amount - $feeNote->payments->sum('amount');
//
//                if ($paymentAmount <= 0) {
//                    break;
//                }
//
//                $paidAmount = min($paymentAmount, $due);
//                $feeNote->payments()->create([
//                    'amount' => $paidAmount,
//                    'payment_method' => 'invoice',
//                ]);
//
//                $paymentAmount -= $paidAmount;
//            }
//
//            // Update the invoice status using the enum
//            if ($invoice->amount_paid == 0) {
//                $invoice->status = InvoiceStatus::Pending;
//            } elseif ($invoice->amount_paid < $invoice->total_amount) {
//                $invoice->status = InvoiceStatus::Partial;
//            } else {
//                $invoice->status = InvoiceStatus::Complete;
//            }
//
//            $invoice->save();
//        });
//
//        return response()->json([
//            'message' => 'Payment processed successfully',
//            'invoice_status' => $invoice->status->label(), // Return readable status
//        ], 200);
//    }
}
