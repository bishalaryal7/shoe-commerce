<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['wholesale_price'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }
}
