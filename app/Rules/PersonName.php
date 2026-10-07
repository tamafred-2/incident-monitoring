<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PersonName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Unicode letters and accents also cover names written in non-Latin scripts.
        if (! is_string($value)
            || preg_match('/\A[\p{L}\p{M}\p{Zs}\p{Pd}.,\x{0027}\x{2019}]+\z/u', $value) !== 1
            || preg_match('/\p{L}/u', $value) !== 1) {
            $fail('The :attribute must contain letters and name punctuation only; numbers are not allowed.');
        }
    }
}
