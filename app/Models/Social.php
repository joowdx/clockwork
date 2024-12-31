<?php

namespace App\Models;

use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Laravel\Socialite\Contracts\User as Socialite;

class Social extends SocialiteUser
{
    use HasUlids;

    protected $fillable = [
        'sociable_id',
        'sociable_type',
        'provider',
        'provider_id',
        'data',
    ];

    protected $casts = [
        'data' => 'object',
    ];

    public function user(): BelongsTo
    {
        return $this->sociable();
    }

    public function getUser(): Authenticatable
    {
        assert($this->user instanceof Authenticatable);

        return $this->user;
    }

    public function sociable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function findForProvider(string $provider, Socialite $oauthUser, string $model = User::class): ?self
    {
        return self::query()
            ->where('sociable_type', $model)
            ->where('provider', $provider)
            ->where('provider_id', $oauthUser->getId())
            ->first();
    }

    public static function createForProvider(string $provider, Socialite $oauthUser, Authenticatable $user): self
    {
        assert($user instanceof Employee || $user instanceof User);

        return self::query()
            ->create([
                'sociable_id' => $user->getKey(),
                'sociable_type' => get_class($user),
                'provider' => $provider,
                'provider_id' => $oauthUser->getId(),
                'data' => array_diff_key($oauthUser->attributes, ['id' => '']),
            ]);
    }
}
