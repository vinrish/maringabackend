<?php

namespace App\Traits;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;

trait FilterByClient
{
    protected static function bootFilterByClient()
    {
        static::addGlobalScope('clientFilter', function (Builder $builder) {
            $user = auth()->user();

            if (!$user) {
                return; // No filtering for unauthenticated users
            }

            if ($user->hasRole(UserRole::ADMIN)) {
                // Admin has access to all data, no filtering required
                return;
            }

            if ($user->hasRole(UserRole::CLIENT)) {
                $clientIds = $user->clients->pluck('id')->toArray();

                $builder->where(function ($query) use ($clientIds) {
                    $query->whereIn('client_id', $clientIds)
                        ->orWhereHas('directors', function ($directorQuery) use ($clientIds) {
                            $directorQuery->whereIn('client_id', $clientIds);
                        });
                });
            } else {
                // Deny access for unauthorized roles
                $builder->whereRaw('1 = 0'); // Always false condition
            }
        });
    }
}
