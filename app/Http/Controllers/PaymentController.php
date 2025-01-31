<?php

namespace App\Http\Controllers;

use App\Models\FeeNote;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Validate request parameters for search, sorting, and pagination
        $validator = Validator::make($request->all(), [
            'q' => ['nullable', 'string'],       // Search query
            'sortBy' => ['nullable', 'string'],  // Sort field
            'orderBy' => ['nullable', 'in:asc,desc'],  // Order by asc/desc
            'itemsPerPage' => ['nullable', 'integer', 'min:1'],  // Pagination size
            'page' => ['nullable', 'integer', 'min:1'],  // Page number
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        // Retrieve validated input
        $searchTerm = $request->input('q');
        $sortBy = $request->input('sortBy', 'created_at'); // Default sorting by created_at
        $orderBy = $request->input('orderBy', 'asc');
        $itemsPerPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);

        // Initialize the query with relationships
        $paymentsQuery = Payment::with(['feenote']);

        // Search functionality: allows searching by feenote details
        if ($searchTerm) {
            $paymentsQuery->whereHas('feenote', function ($query) use ($searchTerm) {
                $query->where('description', 'like', "%$searchTerm%")
                    ->orWhere('reference_number', 'like', "%$searchTerm%");
            });
        }

        // Sorting
        $paymentsQuery->orderBy($sortBy, $orderBy);

        // Pagination
        $payments = $paymentsQuery->paginate($itemsPerPage, ['*'], 'page', $page);

        // Return paginated payments along with the total count
        return response()->json([
            'payments' => $payments->items(),  // Current page data
            'total' => $payments->total(),     // Total number of payments
            'message' => 'success',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $feeNotes = FeeNote::select('id', 'description', 'reference_number')->get();

        return response()->json([
            'fee_notes' => $feeNotes,
            'message' => 'success'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'fee_note_id' => 'required|exists:fee_notes,id',
            'amount' => 'required|numeric|min:0',
            'transaction_reference' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
            'payment_method' => 'required|in:mpesa,cash,cheque,banktransfer',
//            'status' => 'required|boolean',
        ]);

        $paymentData = $request->all();

        // Auto-generate transaction reference if payment method is cash
        if ($paymentData['payment_method'] === 'cash') {
            $paymentData['transaction_reference'] = 'CASH-' . time();
        }

        $payment = Payment::create($paymentData);

        return response()->json(['message' => 'Payment created successfully!', 'payment' => $payment], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        return response()->json($payment->load(['feenote']));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        $feeNotes = FeeNote::select('id', 'description', 'reference_number')->get();

        return response()->json([
            'payment' => $payment->load('feenote'), // Load related fee note
            'fee_notes' => $feeNotes,
            'message' => 'success'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'transaction_reference' => 'nullable|string|max:255',
            'paid_at' => 'nullable|date',
            'payment_method' => 'required|in:mpesa,cash,cheque,banktransfer',
            'status' => 'required|boolean',
        ]);

        $payment->update($request->all());

        return response()->json(['message' => 'Payment updated successfully!', 'payment' => $payment]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        // Delete the payment
        $payment->delete();

        return response()->json(['message' => 'Payment deleted successfully!']);
    }
}
