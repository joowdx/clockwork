<?php

namespace App\Helpers;

class NumberRangeCompressor
{
    public function __invoke(array $numbers, bool $implode = true): array|string|null
    {
        return self::compress($numbers, $implode);
    }

    public static function compress(array $numbers, bool $implode): array|string|null
    {
        if (empty($numbers)) {
            return null;
        }

        $numbers = array_unique($numbers);

        if (count($numbers) == 1) {
            return $numbers[0];
        }

        sort($numbers);

        $ranges = [];
        $range = [];
        $range[0] = $numbers[0];
        $range[1] = $numbers[0];

        for ($i = 1; $i < count($numbers); $i++) {
            if ($numbers[$i] - $range[1] == 1) {
                $range[1] = $numbers[$i];
            } else {
                $ranges[] = $range;
                $range = [];
                $range[0] = $numbers[$i];
                $range[1] = $numbers[$i];
            }
        }

        $ranges[] = $range;
        $compressed = [];

        foreach ($ranges as $range) {
            $compressed[] = $range[0] === $range[1] ? $range[0] : $range[0].'-'.$range[1];
        }

        return $implode ? implode(',', $compressed) : $compressed;
    }
}
