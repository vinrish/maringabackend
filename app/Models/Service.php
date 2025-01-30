<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'price',
        'status',
    ];

//    protected $primaryKey = 'uuid';

    public $incrementing = false;

    protected $keyType = 'string';

    public function obligations()
    {
        return $this->belongsToMany(Obligation::class, 'obligation_service')
            ->withPivot('price')
            ->withTimestamps();
    }

    protected static function boot()
    {
        parent::boot();

        // Clear the cache when a service is created or updated
        static::saved(function () {
            Cache::forget('services'); // Clear the cached services
        });

        // Clear the cache when a service is deleted
        static::deleted(function () {
            Cache::forget('services'); // Clear the cached services
        });
    }
}
