<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TimelogMode: int implements HasIcon, HasLabel
{
    case UNKNOWN = -1;
    case FINGERPRINT_0 = 0;
    case FINGERPRINT_1 = 1;
    case RFID_CARD_2 = 2;
    case RFID_CARD_4 = 4;
    case PASSWORD_3 = 3;
    case FACE_RECOGNITION_15 = 15;
    case FACE_RECOGNITION_16 = 16;

    public function getIcon(): ?string
    {
        return match ($this) {
            self::FINGERPRINT_0, self::FINGERPRINT_1 => 'heroicon-m-finger-print',
            self::RFID_CARD_2, self::RFID_CARD_4 => 'heroicon-m-identification',
            self::PASSWORD_3 => 'heroicon-m-key',
            self::FACE_RECOGNITION_15, self::FACE_RECOGNITION_16 => 'heroicon-m-face-smile',
            default => 'heroicon-m-no-symbol',
        };
    }

    public function getLabel(bool $strict = false): string
    {
        if ($strict) {
            return match ($this) {
                self::FINGERPRINT_0 => 'Fingerprint (0)',
                self::FINGERPRINT_1 => 'Fingerprint (1)',
                self::RFID_CARD_2 => 'RFID Card (2)',
                self::RFID_CARD_4 => 'RFID Card (4)',
                self::PASSWORD_3 => 'Password (3)',
                self::FACE_RECOGNITION_15 => 'Face Recognition (15)',
                self::FACE_RECOGNITION_16 => 'Face Recognition (16)',
                default => 'Unknown Mode',
            };
        }

        return match ($this) {
            self::FINGERPRINT_0, self::FINGERPRINT_1 => 'Fingerprint',
            self::RFID_CARD_2, self::RFID_CARD_4 => 'RFID Card',
            self::PASSWORD_3 => 'Password',
            self::FACE_RECOGNITION_15, self::FACE_RECOGNITION_16 => 'Face Recognition',
            default => 'Unknown Mode',
        };
    }

    public function getCode(): string
    {
        return match ($this) {
            self::FINGERPRINT_0, self::FINGERPRINT_1 => 'fp',
            self::RFID_CARD_2, self::RFID_CARD_4 => 'rc',
            self::PASSWORD_3 => 'pw',
            self::FACE_RECOGNITION_15, self::FACE_RECOGNITION_16 => 'fr',
            default => 'um',
        };
    }
}
