<?php

namespace App\Http\Requests\Shop;

use App\Rules\ContactPhone;
use Illuminate\Foundation\Http\FormRequest;

class WholesaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => ContactPhone::normalize($this->input('phone'))]);
        }
    }

    public function rules(): array
    {
        return ['company' => 'required|string|min:2|max:150', 'phone' => ['required', 'string', 'max:40', new ContactPhone], 'message' => 'nullable|string|max:2000'];
    }
}
