<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }
}
