<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'fee_note_id',
        'amount',
        'transaction_reference',
        'payment_method',
        'paid_at',
        'status',
    ];

    public function feenote()
    {
        return $this->belongsTo(FeeNote::class, 'fee_note_id');
    }

//    public function paymentMethod()
//    {
//        return $this->belongsTo(PaymentMethod::class);
//    }

    // Define the enum for payment methods
    public static function getPaymentMethods()
    {
        return ['mpesa', 'cash', 'cheque', 'banktransfer'];
    }
}
