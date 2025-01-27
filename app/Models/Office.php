<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'logo',
        'name',
        'code',
        'employee_id',
    ];

    public function code(): Attribute
    {
        return Attribute::make(
            fn ($code) => mb_strtoupper($code),
            fn ($code) => mb_strtoupper($code),
        );
    }

    public function icon(): Attribute
    {
        return Attribute::make(
            fn () => storage_path('app/public/'.$this->logo),
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function head(): BelongsTo
    {
        return $this->employee();
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'deployment')
            ->using(Deployment::class)
            ->withPivot(['current', 'active'])
            ->orderBy('full_name')
            ->wherePivot('active', true);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function users(): MorphToMany
    {
        return $this->morphToMany(User::class, 'assignable', Assignment::class)
            ->using(Assignment::class)
            ->withPivot('active')
            ->wherePivot('active', true);
    }

    public function assignees(): MorphMany
    {
        return $this->morphMany(Assignment::class, 'assignable')
            ->where('active', true);
    }
}
