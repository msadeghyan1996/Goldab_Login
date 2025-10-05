<?php

namespace App\Helpers;
class Helper {


    /**
     * Normalize Iranian Mobile Number
     *
     * Converts various input formats (e.g., +98912..., 0098912..., 912..., etc.)
     * into a standard local format: 09xxxxxxxxx
     *
     * @param string $number
     *
     * @return string|null
     */
    public static function normalizeMobile (string $number) : ?string {
        if ( empty($number) ) {
            return null;
        }

        // Convert Persian digits to English
        $number = self::persianToEnglishNum(trim($number));

        // Decode URL-encoded prefix (in case of %2B)
        $number = urldecode($number);

        // Remove all non-digit characters just in case (spaces, dashes, etc.)
        $number = preg_replace('/\D+/', '', $number);

        // Remove Iranian country codes
        $number = preg_replace('/^(0098|098|98)/', '', $number);

        // Remove leading zero if present (e.g., 0912 -> 912)
        $number = preg_replace('/^0/', '', $number);

        // Validate: must start with 9 and have 10 digits total
        if ( !preg_match('/^9\d{9}$/', $number) ) {
            return null;
        }

        // Return in standard format
        return '0' . $number;
    }

    /**
     * Convert Persian and Arabic digits to English digits
     *
     * @param float|int|string $number
     *
     * @return string
     */
    public static function persianToEnglishNum (float|int|string $number) : string {
        // Convert to string first (in case input is numeric)
        $number = (string) $number;

        // Persian and Arabic digits
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٫', '٬'];
        $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٫', '٬'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', ','];

        // Replace both Persian & Arabic digits
        return str_replace(array_merge($persian, $arabic), array_merge($english, $english), $number);
    }

    /**
     * Generate a secure 6-digit OTP
     *
     * @param int $length
     *
     * @return string
     * @throws \Random\RandomException
     */
    public static function generateCode (int $length = 6) : string {
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('', $length, '9');

        return (string) random_int($min, $max);
    }
}
