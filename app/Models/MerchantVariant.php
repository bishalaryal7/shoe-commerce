<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantVariant extends Model
{
    protected $guarded = ['id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(MerchantProduct::class, 'merchant_product_id');
    }
}
