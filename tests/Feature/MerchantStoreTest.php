<?php

namespace Tests\Feature;

use App\Models\MerchantOrder;
use App\Models\MerchantProduct;
use App\Models\MerchantStore;
use App\Models\Product;
use App\Models\User;
use App\Models\WholesaleAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MerchantStoreTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $company = 'Himalaya Goods', string $status = 'approved'): User
    {
        $user = User::factory()->create();
        WholesaleAccount::create(['user_id' => $user->id, 'company' => $company, 'phone' => '9800000000', 'status' => $status]);

        return $user;
    }

    private function store(User $owner, string $name = 'Mountain Market'): MerchantStore
    {
        $this->actingAs($owner)->post('/merchant', ['name' => $name, 'tagline' => 'Independent styles'])->assertRedirect('/merchant');

        return MerchantStore::where('wholesale_account_id', WholesaleAccount::where('user_id', $owner->id)->value('id'))->firstOrFail();
    }

    private function product(MerchantStore $store, string $name = 'Trail Jacket'): MerchantProduct
    {
        $this->post('/merchant/products', [
            'name' => $name,
            'category' => 'Clothing',
            'description' => 'Warm everyday outdoor layer.',
            'price' => '2500.00',
            'size' => 'M',
            'stock' => 3,
            'image' => UploadedFile::fake()->image('jacket.jpg'),
        ])->assertRedirect();

        return $store->products()->where('name', $name)->firstOrFail();
    }

    public function test_only_approved_wholesalers_can_create_a_store_and_manage_their_own_catalog(): void
    {
        Storage::fake('public');
        $pending = $this->owner('Pending Goods', 'pending');
        $this->actingAs($pending)->post('/merchant', ['name' => 'Pending Shop'])->assertNotFound();

        $firstOwner = $this->owner();
        $first = $this->store($firstOwner);
        $product = $this->product($first);
        Storage::disk('public')->assertExists($product->image_path);
        $this->get('/stores/'.$first->slug.'/products/'.$product->slug)->assertOk();

        $secondOwner = $this->owner('Other Goods');
        $second = $this->store($secondOwner, 'Second Shop');
        $this->patch('/merchant/products/'.$product->id, ['name' => 'Stolen', 'category' => 'Shoes', 'description' => 'Another product.', 'price' => 1, 'active' => true])->assertNotFound();
        $this->post('/merchant/products/'.$product->id.'/variants', ['size' => 'L', 'stock' => 10])->assertNotFound();
        $this->post('/stores/'.$second->slug.'/cart', ['variant_id' => $product->variants()->firstOrFail()->id, 'quantity' => 1])->assertNotFound();
        $this->get('/stores/'.$second->slug.'/products/'.$product->slug)->assertNotFound();
        $this->assertSame('Trail Jacket', $product->fresh()->name);
    }

    public function test_checkout_requires_a_signed_in_customer(): void
    {
        $store = $this->store($this->owner());
        auth()->logout();

        $this->get('/stores/'.$store->slug.'/checkout')->assertRedirect('/login');
        $this->post('/stores/'.$store->slug.'/checkout', [])->assertRedirect('/login');
    }

    public function test_seller_product_validation_rejects_unsafe_uploads_and_invalid_prices(): void
    {
        Storage::fake('public');
        $store = $this->store($this->owner());
        $this->post('/merchant/products', [
            'name' => 'Unsafe Item',
            'category' => 'Clothing',
            'description' => 'A product with invalid details.',
            'price' => '1e4',
            'size' => 'M',
            'stock' => 2,
            'image' => UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml'),
        ])->assertSessionHasErrors(['price', 'image']);
        $this->assertSame(0, $store->products()->count());
    }

    public function test_customer_checkout_requires_login_and_reserves_stock_without_cross_store_access(): void
    {
        Storage::fake('public');
        $owner = $this->owner();
        $store = $this->store($owner);
        $product = $this->product($store);
        $variant = $product->variants()->firstOrFail();

        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post('/stores/'.$store->slug.'/cart', ['variant_id' => $variant->id, 'quantity' => 2])->assertRedirect('/stores/'.$store->slug.'/cart');
        $this->get('/stores/'.$store->slug.'/checkout')->assertOk();
        $token = session('merchant_checkout.'.$store->id);
        $details = ['name' => 'Test Buyer', 'email' => 'buyer@example.com', 'phone' => '9801234567', 'address' => 'Long street address, Kathmandu', 'checkout_token' => $token];
        $this->post('/stores/'.$store->slug.'/checkout', $details)->assertRedirect();
        $order = MerchantOrder::firstOrFail();
        $this->assertSame(500000, (int) $order->total);
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertSame($buyer->id, $order->user_id);
        $this->get('/partner-orders')->assertOk()->assertInertia(fn (Assert $page) => $page->component('merchant/Orders')->has('orders.data', 1));

        $this->post('/stores/'.$store->slug.'/checkout', $details)->assertRedirect('/stores/'.$store->slug.'/orders/'.$order->reference);
        $this->assertSame(1, MerchantOrder::count());
        $this->actingAs(User::factory()->create())->get('/stores/'.$store->slug.'/orders/'.$order->reference)->assertNotFound();
        $this->get('/partner-orders')->assertOk()->assertInertia(fn (Assert $page) => $page->component('merchant/Orders')->has('orders.data', 0));

        $this->actingAs($owner)->patch('/merchant/orders/'.$order->id, ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(3, $variant->fresh()->stock);
        $this->patch('/merchant/orders/'.$order->id, ['status' => 'cancelled'])->assertStatus(422);
        $this->assertSame(3, $variant->fresh()->stock);

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $buyer->id]);
    }

    public function test_customer_and_seller_messages_are_scoped_to_their_store(): void
    {
        $firstOwner = $this->owner();
        $first = $this->store($firstOwner);
        $secondOwner = $this->owner('Other Goods');
        $second = $this->store($secondOwner, 'Second Shop');
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->post('/stores/'.$first->slug.'/messages', ['subject' => 'Size question', 'body' => 'Is this shoe true to size?'])->assertRedirect();
        $thread = $first->conversations()->firstOrFail();

        $this->actingAs($secondOwner)->post('/merchant/conversations/'.$thread->id, ['body' => 'Wrong shop'])->assertNotFound();
        $this->actingAs(User::factory()->create())->post('/stores/'.$first->slug.'/messages/'.$thread->id, ['body' => 'Wrong buyer'])->assertNotFound();
        $this->actingAs($firstOwner)->post('/merchant/conversations/'.$thread->id, ['body' => 'Yes, choose your usual size.'])->assertRedirect();
        $this->assertSame(2, $thread->messages()->count());
        $this->actingAs($buyer)->get('/stores/'.$first->slug.'/messages')->assertInertia(fn (Assert $page) => $page->component('merchant/Messages')->has('conversations', 1));
        $this->get('/stores/'.$second->slug.'/messages')->assertInertia(fn (Assert $page) => $page->component('merchant/Messages')->has('conversations', 0));
    }

    public function test_product_gallery_keeps_each_photo_scoped_to_its_product(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->image('primary.jpg')])->assertRedirect();
        $this->post('/admin/products/1/photos', ['image' => UploadedFile::fake()->image('side.jpg')])->assertRedirect();
        $product = Product::findOrFail(1);
        $this->assertSame(2, $product->photos()->count());
        $side = $product->photos()->get()->last();
        $this->patch('/admin/products/1/photos/'.$side->id.'/move', ['direction' => 'up'])->assertRedirect();
        $this->assertSame($side->id, $product->photos()->firstOrFail()->id);
        $this->patch('/admin/products/2/photos/'.$side->id.'/primary')->assertNotFound();
        $this->patch('/admin/products/1/photos/'.$side->id.'/primary')->assertRedirect();
        $this->assertSame(Storage::disk('public')->url($side->path), $product->fresh()->image);
        $this->get('/products/'.$product->slug)->assertInertia(fn (Assert $page) => $page->component('shop/Product')->has('product.photos', 2));
        $this->delete('/admin/products/1/photos/'.$side->id)->assertRedirect();
        Storage::disk('public')->assertMissing($side->path);
    }

    public function test_replacing_a_chosen_gallery_primary_keeps_other_photos(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->image('front.jpg')])->assertRedirect();
        $this->post('/admin/products/1/photos', ['image' => UploadedFile::fake()->image('side.jpg')])->assertRedirect();
        $product = Product::findOrFail(1);
        $front = $product->photos()->firstOrFail();
        $side = $product->photos()->get()->last();
        $this->patch('/admin/products/1/photos/'.$side->id.'/primary')->assertRedirect();

        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->image('replacement.jpg')])->assertRedirect();

        $this->assertDatabaseHas('product_photos', ['id' => $front->id]);
        $this->assertDatabaseMissing('product_photos', ['id' => $side->id]);
        Storage::disk('public')->assertExists($front->path);
        Storage::disk('public')->assertMissing($side->path);
        $this->assertSame(2, $product->photos()->count());
    }
}
