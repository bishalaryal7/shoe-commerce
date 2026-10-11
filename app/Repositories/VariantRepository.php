<?php

namespace App\Repositories;

use App\Models\Variant;

class VariantRepository
{
    public function forCart(array $ids)
    {
        return Variant::with('product')->whereIn('id', $ids)->orderBy('id')->get();
    }

    public function find(int $id): Variant
    {
        return Variant::with('product')->findOrFail($id);
    }
}
