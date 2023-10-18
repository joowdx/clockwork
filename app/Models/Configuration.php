<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasUlids;

    protected $fillable = [
        'key',
        'value',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function value(): Attribute
    {
        return new Attribute(
            get: function (string|null $value) {
                if (in_array($this->attributes['key'], ['alert_user', 'alert_guest'])) {
                    return json_decode($value ?? '');
                }

                return $value;
            },
            set: function (object|array|string|null $value) {
                if (in_array($this->attributes['key'], ['alert_user', 'alert_guest'])) {
                    $this->attributes['value'] = json_encode($value);

                    return;
                }

                $this->attributes['value'] = $value;
            },
        );
    }
}
