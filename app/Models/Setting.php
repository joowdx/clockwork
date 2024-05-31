<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    const CACHE = 604800;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'key';

    protected $fillable = ['key', 'value'];

    public static function fetch()
    {
        return cache()->remember('settings.all', static::CACHE, fn () => static::all()->pluck('value', 'key'));
    }

    public static function get(string $key): mixed
    {
        return cache()->remember("settings.{$key}", static::CACHE, fn () => static::find($key)?->value);
    }

    public static function set(array $data): void
    {
        static::upsert($data, ['key'], ['value']);

        cache()->forget('settings.all');

        foreach ($data as $key => $value) {
            cache()->forget("settings.{$key}");
        }
    }

    public static function default(string $key): mixed
    {
        return match ($key) {
            'seal' => 'blank.png',
            'name' => config('app.name'),
            default => null,
        };
    }
}
