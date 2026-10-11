<?php

namespace App\Http\Requests\Staff;

class ShippingRequest extends StaffRequest
{
    public function rules(): array
    {
        return ['status' => ['required', 'in:shipped,fulfilled'], 'tracking_carrier' => ['required_if:status,shipped', 'nullable', 'string', 'max:100'], 'tracking_number' => ['required_if:status,shipped', 'nullable', 'string', 'max:120']];
    }
}
