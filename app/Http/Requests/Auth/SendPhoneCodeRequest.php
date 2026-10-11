<?php

namespace App\Http\Requests\Auth;

use App\Rules\ContactPhone;
use Illuminate\Foundation\Http\FormRequest;

class SendPhoneCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public static function normalize(string $phone): string
    {
        $phone = preg_replace('/[ ()-]/', '', ContactPhone::normalize($phone));

        return preg_match('/^9[78][0-9]{8}$/', $phone) ? '+977'.$phone : $phone;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->phone)) {
            $this->merge(['phone' => self::normalize($this->phone)]);
        }
    }

    public function rules(): array
    {
        return ['phone' => ['required', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/']];
    }

    public function messages(): array
    {
        return ['phone.regex' => __('Enter your mobile number with its country code, for example +977 9800000000.')];
    }
}
