<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ConvertFaNumToEn implements ValidationRule {
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void{
        $newNumbers = range(0, 9);
        $persianDecimal = array(
            '&#1776;',
            '&#177۷;',
            '&#177۸;',
            '&#177۹;',
            '&#178۰;',
            '&#178۱;',
            '&#178۲;',
            '&#178۳;',
            '&#178۴;',
            '&#178۵;'
        );
        $arabicDecimal = array(
            '&#1632;',
            '&#1633;',
            '&#1634;',
            '&#1635;',
            '&#1636;',
            '&#1637;',
            '&#1638;',
            '&#1639;',
            '&#1640;',
            '&#1641;'
        );
        $arabic = array(
            '٠',
            '١',
            '٢',
            '٣',
            '٤',
            '٥',
            '٦',
            '٧',
            '٨',
            '٩'
        );
        $persian = array(
            '۰',
            '۱',
            '۲',
            '۳',
            '۴',
            '۵',
            '۶',
            '۷',
            '۸',
            '۹'
        );

        $string = str_replace($persianDecimal, $newNumbers, $value);
        $string = str_replace($arabicDecimal, $newNumbers, $string);
        $string = str_replace($arabic, $newNumbers, $string);
        request()->merge([$attribute => str_replace($persian, $newNumbers, $string)]);

        if (empty($string)) {
            $fail('مقدار ورودی نمی‌تواند خالی باشد.');
        }
    }
}
