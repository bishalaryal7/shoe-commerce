<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEmail extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'sent_at' => 'datetime'];
    }
}
