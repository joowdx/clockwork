<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaperSize: string implements HasLabel
{
    case A4 = 'a4';
    case LETTER = 'letter';
    case FOLIO = 'folio';
    case LEGAL = 'legal';

    public function getLabel(): ?string
    {
        return match ($this->value) {
            'a4' => 'A4 (210mm x 297mm)',
            'letter' => 'Letter (216mm x 279mm)',
            'folio' => 'Folio (216mm x 330mm)',
            'legal' => 'Legal (216mm x 356mm)',
        };
    }

    public function getDimension(string $unit = 'mm')
    {
        throw_unless(in_array($unit, ['in', 'mm']), 'InvalidArgumentException', "Unsupported unit '$unit'.");

        return match ($unit) {
            'mm' => match ($this->value) {
                'a4' => [210, 297],
                'letter' => [216, 279],
                'folio' => [216, 330],
                'legal' => [216, 356],
            },
            'in' => match ($this->value) {
                'a4' => [8.27, 11.69],
                'letter' => [8.5, 11],
                'folio' => [8.5, 13],
                'legal' => [8.5, 14],
            }
        };
    }
}
