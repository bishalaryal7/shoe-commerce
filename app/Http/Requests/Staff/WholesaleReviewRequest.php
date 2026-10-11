<?php

namespace App\Http\Requests\Staff;

class WholesaleReviewRequest extends StaffRequest
{
    public function rules(): array
    {
        return ['status' => 'required|in:approved,rejected'];
    }
}
