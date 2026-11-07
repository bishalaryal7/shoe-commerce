<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanionNote extends Model
{
    protected $fillable = ['owner', 'content'];
}
