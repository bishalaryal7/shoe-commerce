<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class MerchantStore extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['logo_url', 'banner_url'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(WholesaleAccount::class, 'wholesale_account_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(MerchantProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MerchantOrder::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(MerchantConversation::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner_path ? Storage::disk('public')->url($this->banner_path) : null;
    }
}
