<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RouteAction: string implements HasLabel
{
    case APPROVAL = 'approval';
    case ENDORSEMENT = 'endorsement';
    case CERTIFICATION = 'certification';
    case VERIFICATION = 'verification';
    case VALIDATION = 'validation';
    case REVIEW = 'review';

    public function getLabel(bool $past = false): string
    {
        return match ($this) {
            self::APPROVAL => $past ? 'Approved' : 'Approval',
            self::ENDORSEMENT => $past ? 'Endorsed' : 'Endorsement',
            self::CERTIFICATION => $past ? 'Certified' : 'Certification',
            self::VERIFICATION => $past ? 'Verified' : 'Verification',
            self::VALIDATION => $past ? 'Validated' : 'Validation',
            self::REVIEW => $past ? 'Reviewed' : 'Review',
        };
    }
}
