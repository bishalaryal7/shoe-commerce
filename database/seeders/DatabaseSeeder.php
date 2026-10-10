<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['City Low', 'city-low', 'Everyday', 'A clean everyday silhouette. Soft lining, a cushioned footbed, and a durable rubber sole.', 'cream', 8900, 5900],
            ['Pace Runner', 'pace-runner', 'Running', 'Lightweight mesh and responsive cushioning for your daily miles.', 'orange', 11900, 7900],
            ['Trail Form', 'trail-form', 'Outdoor', 'Grippy tread and a reinforced upper for wherever the weekend takes you.', 'green', 13900, 9500],
            ['Court Classic', 'court-classic', 'Everyday', 'Court-inspired comfort with a timeless profile and a padded collar.', 'white', 9900, 6500],
            ['Daily Knit', 'daily-knit', 'Everyday', 'A breathable knit upper and flexible sole. Easy from morning to evening.', 'blue', 7900, 4900],
            ['Motion Pro', 'motion-pro', 'Running', 'Supportive cushioning and a breathable upper for an energetic everyday stride.', 'black', 12900, 8500],
        ];
        foreach ($products as [$name,$slug,$category,$description,$color,$retail,$wholesale]) {
            $product = Product::firstOrCreate(['slug' => $slug], ['name' => $name, 'category' => $category, 'description' => $description,
                'image' => '/images/shoe-'.$color.'.svg', 'retail_price' => $retail, 'wholesale_price' => $wholesale]);
            foreach (range(36, 45) as $size) {
                $variant = $product->variants()->firstOrCreate(['sku' => strtoupper($slug).'-'.$size], ['size' => (string) $size, 'color' => $color, 'stock' => 20]);
                if ($variant->wasRecentlyCreated) {
                    InventoryMovement::create(['variant_id' => $variant->id, 'quantity' => 20, 'reason' => 'opening_balance']);
                }
            }
        }
    }
}
