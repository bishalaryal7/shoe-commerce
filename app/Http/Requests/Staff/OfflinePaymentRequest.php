<?php

namespace App\Http\Requests\Staff;

class OfflinePaymentRequest extends StaffRequest
{
    public function rules(): array
    {
        return ['reference' => 'required|string|min:3|max:150'];
    }
}
