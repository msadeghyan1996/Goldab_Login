<?php

namespace App\Domain\Auth\Services;

class NationalIdValidator
{
    public function isValid(string $value): bool
    {
        if (! preg_match('/^\d{10}$/', $value)) {
            return false;
        }

        if (preg_match('/^(\d)\1{9}$/', $value) === 1) {
            return false;
        }

        $checkDigit = (int) $value[9];

        $sum = 0;

        for ($position = 0; $position < 9; $position++) {
            $sum += (int) $value[$position] * (10 - $position);
        }

        $remainder = $sum % 11;

        if ($remainder < 2) {
            return $checkDigit === $remainder;
        }

        return $checkDigit === (11 - $remainder);
    }
}
