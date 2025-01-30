<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'kra_pin',
        'id_no',
        'post_address',
        'post_code',
        'city',
        'county',
        'country',
        'notes',
        'folder_path',
    ];

    public $incrementing = true;
    protected $keyType = 'string';

//    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
//    {
//        return $this->hasMany(File::class, 'client_uuid', 'uuid');
//    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function companies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Company::class, 'client_id', 'id');
    }

    public function feenotes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FeeNote::class);
    }

    public function obligations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Obligation::class);
    }

    public function directors()
    {
        return $this->hasMany(Director::class, 'client_id', 'id');
    }
}
