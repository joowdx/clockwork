<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttachmentClassification: string implements HasLabel
{
    case ACCOMPLISHMENT = 'accomplishment';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ACCOMPLISHMENT => 'Accomplishment',
            default => null,
        };
    }
}
