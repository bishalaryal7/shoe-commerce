<?php

namespace App\Http\Requests\Staff;

class OrderStatusRequest extends StaffRequest
{
    public function rules(): array
    {
        return ['status' => 'required|in:confirmed,fulfilled,cancelled'];
    }
}
