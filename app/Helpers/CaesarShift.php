<?php

namespace App\Helpers;

class CaesarShift
{
    const CHARS = [
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
        'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    const WEAK = [
        0, 26, 36,
    ];

    public static function cipher(string $message, string $passkey = 'caesar cipher', bool $decrypt = false): string
    {
        $total = count(self::CHARS);

        $offset = array_reduce(str_split($passkey), fn ($sum, $char) => $sum + ord($char), 0) % $total;

        $shift = in_array($offset, self::WEAK) ? $offset + 3 : $offset;

        return array_reduce(str_split($message), function ($ciphered, $char) use ($decrypt, $shift, $total) {
            $index = array_search($char, self::CHARS);

            $delta = (($decrypt ? $index - $shift : $index + $shift) + $total) % $total;

            return $ciphered.($index !== false ? self::CHARS[$delta] : $char);
        }, '');
    }
}
