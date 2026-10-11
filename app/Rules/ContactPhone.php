<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContactPhone implements ValidationRule
{
    public static function normalize(string $value): string
    {
        return trim(strtr($value, ['०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4', '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9']));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value)) {
            $value = self::normalize($value);
        }
        if (! is_string($value) || ! preg_match('/^\+?[0-9][0-9 ()-]*$/', $value) || strlen(preg_replace('/[^0-9]/', '', $value)) < 7 || strlen(preg_replace('/[^0-9]/', '', $value)) > 15) {
            $fail(__('validation.contact_phone'));
        }
    }
}
