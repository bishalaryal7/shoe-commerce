<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantConversation extends Model
{
    protected $guarded = ['id'];

    public function messages(): HasMany
    {
        return $this->hasMany(MerchantMessage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
