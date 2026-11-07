<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanionTask extends Model
{
    protected $fillable = ['owner', 'text', 'due_date', 'done'];
    protected $hidden = ['owner', 'created_at', 'updated_at'];
    protected function casts(): array { return ['done' => 'boolean', 'due_date' => 'date:Y-m-d']; }
}
