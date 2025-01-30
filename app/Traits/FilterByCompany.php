<?php
namespace App\Traits;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait FilterByCompany
{
    protected static function boot()
    {
        parent::boot();

//        self::creating(function ($model) {
//            $model->user_id = auth()->id();
//        });

        self::addGlobalScope('company', function (Builder $builder) {
//            if (auth()->check()) {
//                $builder->whereHas('company.client', function ($query) {
//                    $query->where('user_id', auth()->id());
//                });
//            }
            $user = auth()->user();

            if ($user) {
                // Check if the user has the ADMIN role
                if ($user->hasRole(UserRole::ADMIN)) {
                    // If the user is an admin, no filter is applied
                    return;
                } elseif ($user->hasRole(UserRole::CLIENT)) {
                    // If the user is a client, apply the company filter
                    $builder->whereHas('company.client', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    });
                }
            }
        });
    }
}
