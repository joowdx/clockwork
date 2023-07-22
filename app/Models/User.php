<?php

namespace App\Models;

use App\Traits\HasUniversallyUniqueIdentifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
        'name',
        'title',
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

    public function scanners(): BelongsToMany
    {
        return $this->belongsToMany(Scanner::class, 'assignments')
            ->using(Assignment::class)
            ->withTimestamps();
    }

    public function isAdministrator()
    {
        return $this->adminstrator;
    }

    public function scopeAdmin(Builder $query)
    {
        $query->whereAdministrator(true);
    }

    public function getProfilePhotoUrlAttribute()
    {
        return $this->profile_photo_path
                    ? str_replace('.app:8000', '', Storage::disk($this->profilePhotoDisk())->url($this->profile_photo_path))
                    : $this->defaultProfilePhotoUrl();
    }

    // public function employees(): HasManyThrough
    // {
    //     return $this->hasManyThrough(Employee::class, EmployeeScanner::class)->setQuery(
    //         self::select('employees.*')
    //             ->join('scanner_user', 'users.id', '=' , 'scanner_user.user_id')
    //             ->join('employee_scanner', 'employee_scanner.scanner_id', '=', 'scanner_user.scanner_id')
    //             ->join('employees', 'employee_scanner.employee_id', '=', 'employees.id')
    //             ->distinct('employees.id')
    //             ->getQuery()
    //     );
    // }
}
