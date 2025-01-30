<?php

namespace App\Models;

use App\Traits\FilterByClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, FilterByClient, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'logo',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'county',
        'postal_code',
        'website',
        'kra_pin',
        'kra_obligations',
        'fiscal_year',
        'revenue',
        'employees',
        'industry',
        'notes',
        'reg_date',
        'reg_number',
        'client_id',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function client() {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
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

    public function obligations()
    {
        return $this->hasMany(Obligation::class, 'company_id', 'id');
    }
}
