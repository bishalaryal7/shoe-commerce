<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductCatalog
{
    public function __construct(private ProductRepository $products) {}

    public function create(array $data): void
    {
        DB::transaction(function () use ($data) {
            $slug = Str::slug($data['name']).'-'.substr((string) Str::uuid(), 0, 8);
            $product = $this->products->create(['name' => $data['name'], 'slug' => $slug, 'category' => $data['category'], 'description' => $data['description'],
                'image' => '/images/shoe-'.$data['color'].'.svg', 'retail_price' => $data['retail_price'], 'wholesale_price' => $data['wholesale_price'], 'active' => true, 'name_ne' => $data['name_ne'] ?? null, 'description_ne' => $data['description_ne'] ?? null]);
            foreach ($data['sizes'] as $size) {
                $variant = $product->variants()->create(['sku' => strtoupper($slug).'-'.$size, 'size' => (string) $size, 'color' => $data['color'], 'stock' => $data['stock']]);
                InventoryMovement::create(['variant_id' => $variant->id, 'quantity' => $data['stock'], 'reason' => 'opening_balance']);
            }
        });

    }

    public function replacePhoto(Product $product, UploadedFile $image): void
    {
        $path = $image->store('products', 'public');
        if (! $path) {
            throw ValidationException::withMessages(['image' => __('Photo upload could not be saved.')]);
        }
        try {
            $this->products->update($product, ['image' => Storage::disk('public')->url($path)]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

    }
}
