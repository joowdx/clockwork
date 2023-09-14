<?php

namespace App\Models;

use App\Enums\UserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use Searchable;

    protected $fillable = [
        'name',
        'title',
        'username',
        'password',
        'type',
        'disabled',
        'offices',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'type' => UserType::class,
        'offices' => 'array',
    ];

    protected $appends = [
        'administrator',
        'profile_photo_url',
        'has_two_factor_authentication',
    ];

    public function toSearchableArray(): array
    {
        return [
            $this->getKeyName() => $this->getKey(),
            'name' => $this->name,
            'username' => $this->username,
        ];
    }

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(Employee::class)
            ->withDefault();
    }

    public function scanners(): BelongsToMany
    {
        return $this->belongsToMany(Scanner::class, 'assignments')
            ->using(Assignment::class)
            ->withTimestamps();
    }

    public function hasTwoFactorAuthentication(): Attribute
    {
        return new Attribute(
            fn () => $this->hasEnabledTwoFactorAuthentication()
        );
    }

    public function administrator(): Attribute
    {
        return new Attribute(
            fn () => $this->type === UserType::DEVELOPER || $this->type === UserType::ADMINISTRATOR
        );
    }

    public function scopeAdmin(Builder $query): void
    {
        $query->whereType(UserType::ADMINISTRATOR)
            ->orWhereType(UserType::DEVELOPER);
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo_path
                    ? str_replace('.app:8000', '', Storage::disk($this->profilePhotoDisk())->url($this->profile_photo_path))
                    : $this->defaultProfilePhotoUrl();
    }
}
