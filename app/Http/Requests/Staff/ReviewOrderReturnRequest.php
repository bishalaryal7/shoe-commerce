<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class ReviewOrderReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,declined,completed'],
            'staff_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
