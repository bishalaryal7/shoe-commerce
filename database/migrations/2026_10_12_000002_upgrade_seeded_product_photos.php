<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $colors = ['cream', 'orange', 'green', 'white', 'blue', 'black'];

    public function up(): void
    {
        foreach ($this->colors as $color) {
            DB::table('products')
                ->where('image', '/images/shoe-'.$color.'.svg')
                ->update(['image' => '/images/shoe-'.$color.'-studio.webp']);
        }
    }

    public function down(): void
    {
        foreach ($this->colors as $color) {
            DB::table('products')
                ->where('image', '/images/shoe-'.$color.'-studio.webp')
                ->update(['image' => '/images/shoe-'.$color.'.svg']);
        }
    }
};
