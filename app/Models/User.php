<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use UnitEnum;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasUlids, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'position',
        'username',
        'email',
        'password',
        'roles',
        'permissions',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'roles' => 'json',
        'permissions' => 'json',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function root(): Attribute
    {
        return Attribute::make(fn () => $this->hasRole(UserRole::ROOT));
    }

    public function developer(): Attribute
    {
        return Attribute::make(fn () => $this->hasRole(UserRole::DEVELOPER));
    }

    public function superuser(): Attribute
    {
        return Attribute::make(fn () => $this->hasAnyRole(UserRole::ROOT, UserRole::SUPERUSER));
    }

    public function signature(): MorphOne
    {
        return $this->morphOne(Signature::class, 'signaturable');
    }

    public function scanners(): MorphToMany
    {
        return $this->morphedByMany(Scanner::class, 'assignable', Assignment::class)
            ->withPivot('active')
            ->wherePivot('active', true);
    }

    public function offices(): MorphToMany
    {
        return $this->morphedByMany(Office::class, 'assignable', Assignment::class)
            ->withPivot('active')
            ->wherePivot('active', true);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function canAccessPanel(Panel $panel, ?string $id = null): bool
    {
        if (($id ?? $panel->getId()) === 'app') {
            return true;
        }

        if ($this->hasRole(UserRole::ROOT)) {
            return true;
        }

        return match (($id ?? $panel->getId())) {
            'superuser' => $this->hasRole(UserRole::SUPERUSER),
            'developer' => $this->hasRole(UserRole::DEVELOPER),
            'executive' => $this->hasRole(UserRole::EXECUTIVE),
            'bureaucrat' => $this->hasRole(UserRole::BUREAUCRAT),
            'manager' => $this->hasRole(UserRole::MANAGER),
            'director' => $this->hasRole(UserRole::DIRECTOR),
            'leader' => $this->hasRole(UserRole::LEADER),
            'secretary' => $this->hasRole(UserRole::SECRETARY),
            'security' => $this->hasRole(UserRole::SECURITY),
            default => false,
        };
    }

    public function hasRole(UserRole|string $role): bool
    {
        return in_array($role instanceof UserRole ? $role->value : $role, $this->roles ?? []);
    }

    public function hasAnyRole(UserRole|string ...$roles): bool
    {
        $roles = array_map(fn ($role) => $role instanceof UserRole ? $role->value : $role, $roles);

        return count(array_intersect($roles, $this->roles ?? [])) > 0;
    }

    public function hasAllRoles(UserRole|string ...$roles): bool
    {
        $roles = array_map(fn ($role) => $role instanceof UserRole ? $role->value : $role, $roles);

        return count(array_diff($roles, $this->roles ?? [])) === 0;
    }

    public function hasPermission(UnitEnum|string $permission): bool
    {
        return in_array($permission instanceof UnitEnum ? $permission->value : $permission, $this->permissions ?? []);
    }

    public function hasAnyPermission(UnitEnum|string ...$permissions): bool
    {
        $permissions = array_map(fn ($permission) => $permission instanceof UnitEnum ? $permission->value : $permission, $permissions);

        return count(array_intersect($permissions, $this->permissions ?? [])) > 0;
    }

    public function hasAllPermissions(UnitEnum|string ...$permissions): bool
    {
        $permissions = array_map(fn ($permission) => $permission instanceof UnitEnum ? $permission->value : $permission, $permissions);

        return count(array_diff($permissions, $this->permissions ?? [])) === 0;
    }
}
