<?php

namespace App\Http\Requests\Shop;

use App\Rules\ContactPhone;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => ContactPhone::normalize($this->input('phone'))]);
        }
    }

    public function rules(): array
    {
        return ['name' => 'required|string|min:2|max:150', 'email' => 'required|email:rfc|max:254', 'phone' => ['required', 'string', 'max:40', new ContactPhone], 'address' => 'required|string|min:10|max:1000', 'token' => 'required|uuid', 'payment_method' => 'sometimes|in:request,khalti,paypay'];
    }

    public function attributes(): array
    {
        return ['name' => __('validation.attributes.name'), 'address' => __('validation.attributes.address')];
    }
}
