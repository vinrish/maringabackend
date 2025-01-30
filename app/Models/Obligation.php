<?php

namespace App\Models;

use App\Enums\ObligationFrequency;
use App\Enums\ObligationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Obligation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'fee',
        'amount',
        'type',
        'privacy',
        'start_date',
        'frequency',
        'next_run',
        'status',
        'is_recurring',
        'client_id',
        'company_id',
        'service_ids',
        'last_run'
    ];

    protected $casts = [
        'privacy' => 'boolean',
        'start_date' => 'date',
        'next_run' => 'date',
        'last_run' => 'date',
    ];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function services(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'obligation_service')
            ->withPivot('price')
            ->withTimestamps();
    }

    public function setTypeAttribute($value)
    {
        $this->attributes['type'] = is_int($value) ? $value : $value->value;
    }

    public function getTypeAttribute($value)
    {
        return ObligationType::fromInt($value);
    }

    public function setFrequencyAttribute($value)
    {
        $this->attributes['frequency'] = is_int($value) ? $value : $value->value;
    }

    public function getFrequencyAttribute($value)
    {
        return ObligationFrequency::fromInt($value);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'obligation_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_obligation');
    }
}
