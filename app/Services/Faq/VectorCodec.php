<?php

namespace App\Services\Faq;

class VectorCodec
{
    /* Pack a float array into a compact binary string (4 bytes/float) */
    public static function encode(array $vector): string
    {
        return pack('g*', ...$vector); // 'g' = 32-bit float, little-endian
    }

    public static function decode(string $binary): array
    {
        return array_values(unpack('g*', $binary));
    }

    public static function norm(array $vector): float
    {
        return sqrt(array_sum(array_map(fn ($v) => $v * $v, $vector)));
    }
}