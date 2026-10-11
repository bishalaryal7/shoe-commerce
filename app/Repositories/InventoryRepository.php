<?php

namespace App\Repositories;

use App\Models\InventoryMovement;
use App\Models\Variant;

class InventoryRepository
{
    public function adjust(int $variantId, int $stock, int $actor): void
    {
        $variant = Variant::lockForUpdate()->findOrFail($variantId);
        $delta = $stock - $variant->stock;
        $variant->update(['stock' => $stock]);
        if ($delta !== 0) {
            InventoryMovement::create(['variant_id' => $variant->id, 'actor_id' => $actor, 'quantity' => $delta, 'reason' => 'staff_adjustment']);
        }
    }
}
