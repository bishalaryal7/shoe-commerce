<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'action' => ['required', 'in:exchange,refund'],
            'reason' => ['required', 'in:wrong_size,defective,wrong_item,changed_mind,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
