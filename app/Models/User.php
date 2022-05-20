<?php

namespace App\Models;

use App\Traits\HasUniversallyUniqueIdentifier;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Scout\Searchable;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasUniversallyUniqueIdentifier;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'assignee',
        'position',
        'username',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'name',
        'profile_photo_url',
    ];

    public function toSearchableArray(): array
    {
        return [
            'assignee' => $this->assignee,
            'username' => $this->username,
        ];
    }

    public function name(): Attribute
    {
        return new Attribute(fn () => $this->username);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function latest(): HasOne
    {
        return $this->hasOne(TimeLog::class)->latestOfMany('time');
    }

    public function latestImport(): HasOne
    {
        return $this->hasOne(TimeLogsImport::class)->latestOfMany();
    }

    public function scanner(): BelongsToMany
    {
        return $this->belongsToMany(Scanner::class);
    }
}
