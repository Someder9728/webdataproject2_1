<?php

namespace App\Support;

class Format
{
    public static function money(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '-';
        }

        $number = (float) $value;
        $decimals = floor($number) === $number ? 0 : 2;

        return '฿'.number_format($number, $decimals);
    }

    public static function method(?string $type): string
    {
        return match ($type) {
            'TRANSFER' => 'โอนเงิน',
            'CASH' => 'เงินสด',
            default => $type ?: '-',
        };
    }

    public static function num(mixed $value): string
    {
        if (! is_numeric($value)) {
            return '-';
        }

        $number = (float) $value;

        return number_format($number, floor($number) === $number ? 0 : 2);
    }
}
