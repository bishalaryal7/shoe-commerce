<?php

namespace App\Http\Requests\Staff;

class GrantStaffRequest extends StaffRequest
{
    public function rules(): array
    {
        return ['email' => 'required|string|email:rfc|max:254'];
    }
}
