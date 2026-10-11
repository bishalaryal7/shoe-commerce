<?php

namespace App\Http\Requests\Staff;

class StockRequest extends StaffRequest
{
    public function rules(): array
    {
        return ['stock' => 'required|integer|min:0|max:1000000'];
    }
}
