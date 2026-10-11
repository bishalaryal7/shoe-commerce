<?php

namespace App\Http\Requests\Staff;

class PromotionRequest extends StaffRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => is_string($this->input('code')) ? strtoupper(trim($this->input('code'))) : $this->input('code')]);
    }

    public function rules(): array
    {
        return ['code' => ['required', 'alpha_dash', 'min:3', 'max:40', 'unique:promotions,code'], 'type' => ['required', 'in:percent,fixed'], 'amount' => ['required', 'integer', 'min:1', 'max:'.($this->input('type') === 'percent' ? '100' : '100000000')], 'minimum_subtotal' => ['sometimes', 'integer', 'min:0', 'max:1000000000'], 'usage_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'], 'channel' => ['required', 'in:all,retail,wholesale'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'active' => ['sometimes', 'boolean']];
    }
}
