<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasUlids;
    use Notifiable;
    use Searchable;

    protected $fillable = [
        'name',
        'title',
        'username',
        'password',
        'role',
        'disabled',
        'offices',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'role' => UserRole::class,
        'offices' => 'array',
    ];

    protected $appends = [
        'administrator',
        'profile_photo_url',
    ];

    public function toSearchableArray(): array
    {
        return [
            $this->getKeyName() => $this->getKey(),
            'name' => $this->name,
            'username' => $this->username,
        ];
    }

    public function employee()
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

    public function administrator(): Attribute
    {
        return new Attribute(
            fn () => $this->role === UserRole::DEVELOPER || $this->role === UserRole::ADMINISTRATOR
        );
    }

    public function scopeAdmin(Builder $query): void
    {
        $query->whereRole(UserRole::ADMINISTRATOR)
            ->orWhereRole(UserRole::DEVELOPER);
    }

    public function signature(): HasOne
    {
        return $this->hasOne(Signature::class);
    }

    public function specimens(): HasManyThrough
    {
        return $this->hasManyThrough(Specimen::class, Signature::class);
    }

    public function randomSpecimen(): Specimen
    {
        if ($this->relationLoaded('specimen')) {
            return $this->specimens->shuffle()->first();
        }

        return $this->specimens()->inRandomOrder()->first();
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo_path
                    ? str_replace('.app:8000', '', Storage::disk($this->profilePhotoDisk())->url($this->profile_photo_path))
                    : $this->defaultProfilePhotoUrl();
    }
}
