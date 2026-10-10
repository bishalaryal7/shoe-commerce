<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function staff(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();
        $this->actingAs($user);
    }

    private function data(): array
    {
        return ['name' => 'New Shoe', 'category' => 'Everyday', 'description' => 'A new comfortable shoe.', 'retail_price' => 950000, 'wholesale_price' => 700000, 'color' => 'black', 'sizes' => ['40', '41'], 'stock' => 10];
    }

    public function test_staff_can_create_products_with_unique_variant_inventory(): void
    {
        $this->staff();
        $this->post('/admin/products', $this->data())->assertRedirect();
        $product = Product::where('name', 'New Shoe')->firstOrFail();
        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(20, (int) $product->variants()->sum('stock'));
        $this->assertSame(950000, $product->retail_price);
        $this->get('/products/'.$product->slug)->assertInertia(fn (Assert $p) => $p->where('product.name', 'New Shoe')->missing('product.wholesale_price'));
    }

    public function test_duplicate_sizes_are_rejected_without_partial_product_creation(): void
    {
        $this->staff();
        $this->post('/admin/products', [...$this->data(), 'sizes' => ['40', '40']])->assertSessionHasErrors('sizes.0');
        $this->assertSame(6, Product::count());
    }

    public function test_customer_cannot_create_products_or_upload_images(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/admin/products', $this->data())->assertForbidden();
        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->image('shoe.png')])->assertForbidden();
    }

    public function test_photos_are_stored_using_generated_paths(): void
    {
        Storage::fake('public');
        $this->staff();
        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->image('shoe.png')])->assertRedirect();
        $image = Product::findOrFail(1)->image;
        $this->assertStringStartsWith('/storage/products/', $image);
        Storage::disk('public')->assertExists(substr($image, strlen('/storage/')));
    }

    public function test_scriptable_and_oversized_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $this->staff();
        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('image');
        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->image('large.png')->size(6000)])->assertSessionHasErrors('image');
        $this->assertSame('/images/shoe-cream.svg', Product::findOrFail(1)->image);
    }

    public function test_unlisted_products_cannot_be_viewed_or_added_to_the_bag(): void
    {
        $product = Product::first();
        $product->update(['active' => false]);
        $this->get('/products/'.$product->slug)->assertNotFound();
        $this->post('/cart', ['variant_id' => $product->variants()->first()->id, 'quantity' => 1])->assertNotFound();
    }
}
