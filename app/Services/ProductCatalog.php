<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                'image' => '/images/shoe-'.$data['color'].'-studio.webp', 'retail_price' => $data['retail_price'], 'wholesale_price' => $data['wholesale_price'], 'active' => true, 'name_ne' => $data['name_ne'] ?? null, 'description_ne' => $data['description_ne'] ?? null]);
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
        $previousImage = $product->image;
        try {
            DB::transaction(function () use ($product, $path, $previousImage) {
                $previousPath = parse_url($previousImage, PHP_URL_PATH);
                if (is_string($previousPath) && str_starts_with($previousPath, '/storage/products/')) {
                    $product->photos()->where('path', substr($previousPath, strlen('/storage/')))->delete();
                }
                $product->photos()->increment('position');
                $product->photos()->create(['path' => $path, 'position' => 0]);
                $this->products->update($product, ['image' => Storage::disk('public')->url($path)]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }
        $this->deletePreviousPhoto($previousImage, $path);
    }

    public function addPhoto(Product $product, UploadedFile $image): void
    {
        if ($product->photos()->count() >= 8) {
            throw ValidationException::withMessages(['image' => __('A product can have up to eight photos.')]);
        }
        $path = $image->store('products', 'public');
        if (! $path) {
            throw ValidationException::withMessages(['image' => __('Photo upload could not be saved.')]);
        }
        try {
            $product->photos()->create(['path' => $path, 'position' => $product->photos()->max('position') + 1]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }
    }

    public function removePhoto(Product $product, int $photoId): void
    {
        $photo = $product->photos()->findOrFail($photoId);
        abort_if($product->photos()->count() <= 1, 422, __('Keep at least one uploaded product photo.'));
        $wasPrimary = $product->image === Storage::disk('public')->url($photo->path);
        DB::transaction(function () use ($product, $photo, $wasPrimary) {
            $photo->delete();
            if ($wasPrimary) {
                $next = $product->photos()->firstOrFail();
                $product->update(['image' => Storage::disk('public')->url($next->path)]);
            }
        });
        Storage::disk('public')->delete($photo->path);
    }

    public function makePrimary(Product $product, int $photoId): void
    {
        $photo = $product->photos()->findOrFail($photoId);
        $product->update(['image' => Storage::disk('public')->url($photo->path)]);
    }

    public function movePhoto(Product $product, int $photoId, string $direction): void
    {
        $photos = $product->photos()->get();
        $index = $photos->search(fn ($photo) => $photo->id === $photoId);
        abort_if($index === false, 404);
        $neighborIndex = $index + ($direction === 'up' ? -1 : 1);
        if (! $photos->get($neighborIndex)) {
            return;
        }
        $ordered = $photos->all();
        [$ordered[$index], $ordered[$neighborIndex]] = [$ordered[$neighborIndex], $ordered[$index]];
        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $position => $photo) {
                $photo->update(['position' => $position]);
            }
        });
    }

    private function deletePreviousPhoto(string $url, string $newPath): void
    {
        $urlPath = parse_url($url, PHP_URL_PATH);
        if (! is_string($urlPath) || ! str_starts_with($urlPath, '/storage/products/')) {
            return;
        }

        $previousPath = substr($urlPath, strlen('/storage/'));
        if ($previousPath === $newPath || str_contains($previousPath, '..')) {
            return;
        }

        try {
            Storage::disk('public')->delete($previousPath);
        } catch (\Throwable) {
            Log::warning('A replaced product photo could not be removed.', ['path' => $previousPath]);
        }
    }
}
