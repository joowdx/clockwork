<?php

namespace App\Traits;

trait HasUniversallyUniqueIdentifier
{
    protected static function bootHasUniversallyUniqueIdentifier()
    {
        static::creating(fn ($model) => $model->{$model->getKeyName()} = str()->orderedUuid());
    }

    public function getKeyType()
    {
        return 'string';
    }

    public function getIncrementing()
    {
        return null;
    }
}
