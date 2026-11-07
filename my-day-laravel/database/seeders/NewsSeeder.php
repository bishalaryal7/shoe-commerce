<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Article::where('is_sample', true)->exists()) Article::factory()->count(6)->create();
    }
}
