<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantOrder extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['checkout_token'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(MerchantStore::class, 'merchant_store_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MerchantOrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
