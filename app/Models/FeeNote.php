<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id',
        'client_id',
        'company_id',
        'amount',
        'status',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

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

    public function payments()
    {
        return $this->hasMany(Payment::class, 'fee_note_id');
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_fee_note');
    }
}
