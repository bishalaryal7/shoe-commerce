<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['checkout_token'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
