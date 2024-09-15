<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestStatus: string implements HasColor, HasLabel
{
    case REQUEST = 'requested';
    case CANCEL = 'cancelled';
    case RETURN = 'returned';

    case DEFLECT = 'deflected';
    case ESCALATE = 'escalated';

    case REJECT = 'rejected';
    case APPROVE = 'approved';

    // case RESCIND = 'rescinded';
    // case REVOKE = 'revoked';
    // case TERMINATE = 'terminated';

    public function getLabel(bool $past = true): ?string
    {
        return str($this->{$past ? 'value' : 'name'})->title();
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::CANCEL => 'purple-500',
            self::APPROVE => 'green-500',
            self::RETURN, self::DEFLECT => 'yellow-500',
            self::REJECT => 'red-500',
            // self::REVOKE, self::TERMINATE, self::RESCIND => 'red-500',
            default => null,
        };
    }
}
