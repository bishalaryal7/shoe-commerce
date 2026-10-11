<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class PhoneChallenge extends Model
{
    use Prunable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['provider_sid', 'session_hash', 'phone'];

    protected function casts(): array
    {
        return ['provider_sid' => 'encrypted', 'expires_at' => 'datetime', 'used_at' => 'datetime'];
    }

    public function prunable()
    {
        return static::where('expires_at', '<', now()->subDay());
    }
}
