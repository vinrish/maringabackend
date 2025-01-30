<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'status',
        'allow_login',
        'avatar',
        'role_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role_id' => 'integer',
        'status' => 'integer',
        'allow_login' => 'integer',
    ];

    public function oauthAccessToken()
    {
        return $this->hasMany('\App\Models\OauthAccessToken');
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    public function assignRole(Role $role): \Illuminate\Database\Eloquent\Model
    {
        return $this->roles()->save($role);
    }

    public function clients(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Client::class, 'user_id', 'id');
    }

//    public function hasRole($role)
//    {
//        if (is_string($role)) {
//            return $this->roles->contains('name', $role);
//        }
//        return !!$role->intersect($this->roles)->count();
//    }

    /**
     * Check if the user has a specific role.
     *
     * @param UserRole $role
     * @return bool
     */
    public function hasRole(UserRole|string|array $role): bool
    {
        $query = $this->roles();  // Get the roles relation

        // If the role is a string, check by role name
        if (is_string($role)) {
            return $query->where('roles.name', $role)->exists();
        }

        // If the role is an array, check if any of the roles match
        if (is_array($role)) {
            return $query->whereIn('roles.id', $role)->exists();
        }

        // If the role is an instance of UserRole, check by role ID
        if ($role instanceof UserRole) {
            return $query->where('roles.id', $role->value)->exists();
        }

        return false;
    }
//    public function hasRole(UserRole $role): bool
//    {
//        return $this->roles()->where('role_id', $role->value)->exists();
//    }
}
