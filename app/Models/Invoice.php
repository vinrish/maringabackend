<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'client_id',
        'company_id',
        'business_id',
        'total_amount',
        'amount_paid',
        'status',
    ];
    protected $casts = [
        'status' => InvoiceStatus::class,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function feeNotes()
    {
        return $this->belongsToMany(FeeNote::class, 'invoice_fee_note');
    }
}
