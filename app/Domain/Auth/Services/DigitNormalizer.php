<?php

namespace App\Domain\Auth\Services;

class DigitNormalizer
{
    private const ASCII_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const PERSIAN_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    private const ARABIC_DIGITS = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    public function normalize(string $value): string
    {
        return str_replace(
            [...self::PERSIAN_DIGITS, ...self::ARABIC_DIGITS],
            [...self::ASCII_DIGITS, ...self::ASCII_DIGITS],
            $value,
        );
    }
}
