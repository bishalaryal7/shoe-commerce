<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'summary', 'body', 'category', 'published', 'is_sample', 'source_name', 'source_url', 'source_published_at'];
    protected function casts(): array { return ['published' => 'boolean', 'is_sample' => 'boolean', 'source_published_at' => 'date']; }
}
