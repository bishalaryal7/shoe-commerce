<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function show(Request $request, Product $product, Cart $cart)
    {
        abort_unless($product->active, 404);
        $wholesale = $cart->wholesale($request);
        $serialize = function ($item) use ($wholesale) {
            return [...$item->toArray(), 'price' => $wholesale ? $item->wholesale_price : $item->retail_price];
        };

        return Inertia::render('shop/Product', ['product' => $serialize($product->load('variants')), 'wholesale' => $wholesale,
            'related' => Product::with('variants')->where('active', true)->where('category', $product->category)->whereKeyNot($product->id)->limit(3)->get()->map($serialize)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150', 'category' => 'required|in:Everyday,Running,Outdoor', 'description' => 'required|string|max:5000',
            'retail_price' => 'required|integer|min:1|max:100000000', 'wholesale_price' => 'required|integer|min:1|max:100000000',
            'color' => 'required|in:cream,orange,green,white,blue,black', 'sizes' => 'required|array|min:1|max:10',
            'sizes.*' => 'required|distinct|in:36,37,38,39,40,41,42,43,44,45', 'stock' => 'required|integer|min:0|max:1000000',
        ]);
        DB::transaction(function () use ($data) {
            $slug = Str::slug($data['name']).'-'.substr((string) Str::uuid(), 0, 8);
            $product = Product::create(['name' => $data['name'], 'slug' => $slug, 'category' => $data['category'], 'description' => $data['description'],
                'image' => '/images/shoe-'.$data['color'].'.svg', 'retail_price' => $data['retail_price'], 'wholesale_price' => $data['wholesale_price'], 'active' => true]);
            foreach ($data['sizes'] as $size) {
                $variant = $product->variants()->create(['sku' => strtoupper($slug).'-'.$size, 'size' => (string) $size, 'color' => $data['color'], 'stock' => $data['stock']]);
                InventoryMovement::create(['variant_id' => $variant->id, 'quantity' => $data['stock'], 'reason' => 'opening_balance']);
            }
        });

        return back()->with('success', 'Product created. Add a real photo before publishing your collection.');
    }

    public function image(Request $request, Product $product)
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $path = $request->file('image')->store('products', 'public');
        if (! $path) {
            throw ValidationException::withMessages(['image' => 'Photo upload could not be saved.']);
        }
        try {
            $product->update(['image' => Storage::disk('public')->url($path)]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        return back()->with('success', 'Product photo updated.');
    }
}
