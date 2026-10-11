<?php

namespace App\Repositories;

use App\Models\InventoryMovement;
use App\Models\Variant;
use App\Services\StockAlerts;

class InventoryRepository
{
    public function __construct(private StockAlerts $alerts) {}

    public function adjust(int $variantId, int $stock, int $actor): void
    {
        $variant = Variant::lockForUpdate()->findOrFail($variantId);
        $previousStock = $variant->stock;
        $delta = $stock - $previousStock;
        $variant->update(['stock' => $stock]);
        if ($delta !== 0) {
            InventoryMovement::create(['variant_id' => $variant->id, 'actor_id' => $actor, 'quantity' => $delta, 'reason' => 'staff_adjustment']);
        }
        if ($previousStock === 0 && $stock > 0) {
            $this->alerts->restocked($variant);
        }
    }
}
