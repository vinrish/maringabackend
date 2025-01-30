<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CompanyPolicy
{
    /**
     * Generic method to check permissions.
     *
     * @param  \App\Models\User  $user
     * @param  string  $action
     * @param  string  $subject
     * @return bool
     */
    private function checkPermission(User $user, string $action, string $subject): bool
    {
        $permission = Permission::where('action', $action)->where('subject', $subject)->first();

        if (!$permission) {
            Log::info("Permission not found: action=$action, subject=$subject");
            return false;
        }

        $roles = $permission->roles->pluck('id')->toArray();
        $hasRole = $user->hasRole($roles);

        Log::info("Checking permission: action=$action, subject=$subject, roles=" . implode(',', $roles) . ", hasRole=" . ($hasRole ? 'true' : 'false'));

        return $hasRole;
    }

    /**
     * Determine whether the user can view any companies.
     */
    public function viewAny(User $user): bool
    {
        Log::info("User ID: {$user->id}");
//        return $user->roles()->whereIn('roles.id', [1, 2])->exists();
        return $this->checkPermission($user, 'view', 'company');
    }

    /**
     * Determine whether the user can view a specific company.
     */
    public function view(User $user, Company $company): bool
    {
        return $this->checkPermission($user, 'view', 'company') || $user->id === $company->client->user_id;
    }

    /**
     * Determine whether the user can create a company.
     */
    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'create', 'company');
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        return $this->checkPermission($user, 'edit', 'company') || $user->id === $company->client->user_id;
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        return $this->checkPermission($user, 'delete', 'company');
    }
}
