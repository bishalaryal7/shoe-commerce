<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MerchantProduct extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(MerchantStore::class, 'merchant_store_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MerchantVariant::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
