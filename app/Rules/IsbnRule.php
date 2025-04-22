<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsbnRule implements ValidationRule
{
     /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Remove hyphens and spaces
        $isbn = str_replace(['-', ' '], '', $value);
        
        // Check if ISBN is valid
        if (!$this->isValidIsbn10($isbn) && !$this->isValidIsbn13($isbn)) {
            $fail('The :attribute must be a valid ISBN-10 or ISBN-13 number.');
        }
    }

    private function isValidIsbn10(string $isbn): bool
    {
        if (strlen($isbn) !== 10) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            if (!is_numeric($isbn[$i])) {
                return false;
            }
            $sum += (10 - $i) * intval($isbn[$i]);
        }

        $lastChar = strtoupper($isbn[9]);
        if ($lastChar === 'X') {
            $sum += 10;
        } elseif (is_numeric($lastChar)) {
            $sum += intval($lastChar);
        } else {
            return false;
        }

        return $sum % 11 === 0;
    }

    private function isValidIsbn13(string $isbn): bool
    {
        if (strlen($isbn) !== 13) {
            return false;
        }

        if (!ctype_digit($isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += ($i % 2 === 0) ? intval($isbn[$i]) : intval($isbn[$i]) * 3;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit === intval($isbn[12]);
    }
}
