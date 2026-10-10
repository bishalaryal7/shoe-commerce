<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WholesaleAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function payload(string $token): array
    {
        return ['name' => 'Shoe Buyer', 'email' => 'buyer@example.com', 'phone' => '12345678', 'address' => '123 Example Street', 'token' => $token, 'subtotal' => 1, 'unit_price' => 1];
    }

    public function test_public_catalog_does_not_expose_wholesale_prices(): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->component('shop/Index')->has('products.data', 6)->missing('products.data.0.wholesale_price')->where('products.data.0.price', 8900));
    }

    public function test_customers_cannot_read_or_modify_admin_data(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->patch('/admin/variants/1', ['stock' => 999])->assertForbidden();
        $this->patch('/admin/products/1', ['name' => 'Tampered'])->assertForbidden();
        $account = WholesaleAccount::create(['user_id' => $user->id, 'company' => 'Store', 'phone' => '12345']);
        $this->patch('/admin/wholesale/'.$account->id, ['status' => 'approved'])->assertForbidden();
        $token = (string) Str::uuid();
        $this->withSession(['cart' => [1 => 1], 'checkout_token' => $token])->post('/checkout', $this->payload($token));
        $this->patch('/admin/orders/'.Order::first()->id, ['status' => 'cancelled'])->assertForbidden();
    }

    public function test_order_uses_server_prices_and_duplicate_submission_reserves_stock_once(): void
    {
        $variant = Product::first()->variants()->first();
        $token = (string) Str::uuid();
        $this->withSession(['cart' => [$variant->id => 2], 'checkout_token' => $token])->post('/checkout', $this->payload($token))->assertRedirect();
        $order = Order::first();
        $this->assertSame(17800, $order->subtotal);
        $this->assertSame(8900, $order->items->first()->unit_price);
        $this->assertSame(18, $variant->fresh()->stock);
        $this->post('/checkout', $this->payload($token))->assertRedirect();
        $this->assertSame(1, Order::count());
        $this->assertSame(18, $variant->fresh()->stock);
    }

    public function test_stock_shortage_rolls_back_the_entire_order(): void
    {
        $variants = Product::first()->variants()->limit(2)->get();
        $variants[1]->update(['stock' => 0]);
        $token = (string) Str::uuid();
        $this->withSession(['cart' => [$variants[0]->id => 1, $variants[1]->id => 1], 'checkout_token' => $token])->post('/checkout', $this->payload($token))->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::count());
        $this->assertSame(20, $variants[0]->fresh()->stock);
    }

    public function test_cart_refuses_quantity_above_available_stock(): void
    {
        $variant = Product::first()->variants()->first();
        $this->post('/cart', ['variant_id' => $variant->id, 'quantity' => 21])->assertSessionHasErrors('quantity');
    }

    public function test_only_approved_businesses_receive_wholesale_prices(): void
    {
        $user = User::factory()->create();
        $account = WholesaleAccount::create(['user_id' => $user->id, 'company' => 'Test Store', 'phone' => '12345']);
        $this->actingAs($user)->get('/')->assertInertia(fn (Assert $p) => $p->where('wholesale', false)->where('products.data.0.price', 8900));
        $account->update(['status' => 'approved']);
        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('wholesale', true)->where('products.data.0.price', 5900));
    }

    public function test_wholesale_minimum_is_enforced_on_the_server(): void
    {
        $user = User::factory()->create();
        WholesaleAccount::create(['user_id' => $user->id, 'company' => 'Test Store', 'phone' => '12345', 'status' => 'approved']);
        $variant = Product::first()->variants()->first();
        $token = (string) Str::uuid();
        $this->actingAs($user)->withSession(['cart' => [$variant->id => 1], 'checkout_token' => $token])->post('/checkout', $this->payload($token))->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::count());
        $this->withSession(['cart' => [$variant->id => 12]])->post('/checkout', $this->payload($token))->assertRedirect();
        $this->assertSame(70800, Order::first()->subtotal);
        $this->assertSame('wholesale', Order::first()->channel);
    }

    public function test_cancellation_restores_stock_once_and_prevents_reopening(): void
    {
        $variant = Product::first()->variants()->first();
        $token = (string) Str::uuid();
        $this->withSession(['cart' => [$variant->id => 2], 'checkout_token' => $token])->post('/checkout', $this->payload($token));
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();
        $order = Order::first();
        $this->actingAs($user)->patch('/admin/orders/'.$order->id, ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(20, $variant->fresh()->stock);
        $this->patch('/admin/orders/'.$order->id, ['status' => 'cancelled'])->assertStatus(422);
        $this->patch('/admin/orders/'.$order->id, ['status' => 'confirmed'])->assertStatus(422);
        $this->assertSame(20, $variant->fresh()->stock);
    }

    public function test_request_for_another_session_order_is_forbidden(): void
    {
        $this->get('/orders/received/'.Str::uuid())->assertForbidden();
    }

    public function test_customer_cannot_approve_their_own_application(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/wholesale', ['company' => 'Test Store', 'phone' => '12345', 'status' => 'approved', 'user_id' => 999])->assertRedirect();
        $this->assertDatabaseHas('wholesale_accounts', ['user_id' => $user->id, 'status' => 'pending']);
    }

    public function test_orders_require_the_session_checkout_token(): void
    {
        $this->post('/checkout', $this->payload((string) Str::uuid()))->assertStatus(419);
        $this->assertSame(0, Order::count());
    }

    public function test_a_new_bag_starts_a_new_checkout_after_a_previous_order(): void
    {
        $token = (string) Str::uuid();
        $this->withSession(['cart' => [1 => 1], 'checkout_token' => $token])->post('/checkout', $this->payload($token));
        $this->post('/cart', ['variant_id' => 1, 'quantity' => 1])->assertSessionMissing('checkout_token');
        $this->get('/checkout')->assertOk();
        $newToken = session('checkout_token');
        $this->assertNotSame($token, $newToken);
        $this->post('/checkout',$this->payload($newToken))->assertRedirect();
        $this->assertSame(2,Order::count());
    }
}
