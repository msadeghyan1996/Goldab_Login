<?php

namespace App\Rules;

use App\Domain\Auth\Services\NationalIdValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NationalId implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value || !(new NationalIdValidator)->isValid($value)) {
            $fail('national id is invalid');
        }
    }
}
