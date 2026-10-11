<?php

namespace App\Services;

use App\Models\Promotion;
use Illuminate\Validation\ValidationException;

class PromotionPricing
{
    public function quote(?string $code, int $subtotal, string $channel): array
    {
        $promotion = Promotion::where('code', mb_strtoupper(trim((string) $code)))->first();
        $this->assertUsable($promotion, $subtotal, $channel);

        return ['promotion' => $promotion, 'discount' => $this->discount($promotion, $subtotal)];
    }

    public function lockAndPrice(?string $code, int $subtotal, string $channel): array
    {
        if (! $code) {
            return ['promotion' => null, 'discount' => 0];
        }

        $promotion = Promotion::where('code', mb_strtoupper(trim($code)))->lockForUpdate()->first();
        $this->assertUsable($promotion, $subtotal, $channel);

        $discount = $this->discount($promotion, $subtotal);
        $promotion->increment('uses_count');

        return ['promotion' => $promotion, 'discount' => $discount];
    }

    private function assertUsable(?Promotion $promotion, int $subtotal, string $channel): void
    {
        $valid = $promotion
            && $promotion->active
            && (! $promotion->starts_at || $promotion->starts_at->isPast())
            && (! $promotion->ends_at || $promotion->ends_at->isFuture())
            && (! $promotion->usage_limit || $promotion->uses_count < $promotion->usage_limit)
            && in_array($promotion->channel, ['all', $channel], true)
            && $subtotal >= $promotion->minimum_subtotal;
        if (! $valid) {
            throw ValidationException::withMessages(['promotion_code' => __('This discount code is unavailable or does not apply to this order.')]);
        }
    }

    private function discount(Promotion $promotion, int $subtotal): int
    {
        return $promotion->type === 'percent'
            ? intdiv($subtotal * min(100, $promotion->amount) + 50, 100)
            : min($subtotal, $promotion->amount);
    }
}
