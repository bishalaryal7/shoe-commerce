<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

abstract class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }
}
