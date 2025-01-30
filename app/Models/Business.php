<?php

namespace App\Models;

use App\Traits\FilterByClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory, FilterByClient;

    protected $fillable = [
        'uuid',
        'name',
        'logo',
        'business_email',
        'business_phone',
        'business_address',
        'registration_date',
        'business_no',
        'client_id',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function client() {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'company_id', 'id');
    }
//
//    public function payrolls()
//    {
//        return $this->hasMany(Payroll::class);
//    }
//
    public function directors()
    {
        return $this->hasMany(Director::class, 'company_id', 'id'); // Corrected relationship
    }

    public function feenotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FeeNote::class);
    }
}
