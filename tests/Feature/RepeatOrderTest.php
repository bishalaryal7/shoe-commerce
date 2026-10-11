<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RepeatOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function completedOrder(User $user, int $quantity = 2, string $status = 'fulfilled', string $paymentStatus = 'paid'): Order
    {
        $product = Product::firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $order = Order::create([
            'reference' => (string) Str::uuid(), 'checkout_token' => (string) Str::uuid(),
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'phone' => '9800000000', 'address' => 'Kathmandu', 'channel' => 'retail',
            'currency' => 'NPR', 'subtotal' => 8900 * $quantity, 'total' => 8900 * $quantity,
            'status' => $status, 'payment_status' => $paymentStatus,
        ]);
        $order->items()->create([
            'variant_id' => $variant->id, 'name' => $product->name, 'sku' => $variant->sku,
            'size' => $variant->size, 'color' => $variant->color,
            'quantity' => $quantity, 'unit_price' => 8900,
        ]);

        return $order;
    }

    public function test_only_the_owner_can_repeat_a_fulfilled_paid_order(): void
    {
        $owner = User::factory()->create();
        $order = $this->completedOrder($owner);
        $this->post('/orders/'.$order->reference.'/reorder')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->post('/orders/'.$order->reference.'/reorder')->assertNotFound();

        $this->actingAs($owner)->post('/orders/'.$order->reference.'/reorder')->assertRedirect('/cart');
        $variant = Product::firstOrFail()->variants()->firstOrFail();
        $this->assertSame(2, session('cart')[$variant->id]);
        $this->assertNull(session('checkout_token'));
    }

    public function test_reorder_respects_remaining_stock_and_reports_skipped_pairs(): void
    {
        $user = User::factory()->create();
        $order = $this->completedOrder($user, 5);
        $variant = Product::firstOrFail()->variants()->firstOrFail();
        $variant->update(['stock' => 3]);
        $token = (string) Str::uuid();

        $this->actingAs($user)->withSession(['cart' => [$variant->id => 2], 'checkout_token' => $token])
            ->post('/orders/'.$order->reference.'/reorder')
            ->assertRedirect('/cart')
            ->assertSessionHas('success', 'One pair was added to your bag. 4 pairs from your previous order were already in your bag or unavailable.')
            ->assertSessionMissing('checkout_token');
        $this->assertSame(3, session('cart')[$variant->id]);
    }

    public function test_reorder_requires_completed_payment_and_available_items(): void
    {
        $user = User::factory()->create();
        $order = $this->completedOrder($user, 1, 'confirmed', 'paid');
        $this->actingAs($user)->post('/orders/'.$order->reference.'/reorder')->assertStatus(422);
        $order->update(['status' => 'fulfilled', 'payment_status' => 'paid']);
        $variant = Product::firstOrFail()->variants()->firstOrFail();
        $variant->update(['stock' => 0]);
        $this->post('/orders/'.$order->reference.'/reorder')->assertSessionHasErrors('cart');
        $this->assertSame([], session('cart', []));
    }

    public function test_order_history_exposes_repeat_order_action_for_eligible_orders(): void
    {
        $user = User::factory()->create();
        $this->completedOrder($user);
        $this->actingAs($user)->get('/orders')->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 1)->where('orders.data.0.status', 'fulfilled')->where('orders.data.0.payment_status', 'paid'));
    }

    public function test_repeat_order_feedback_is_translated_in_nepali(): void
    {
        $user = User::factory()->create();
        $order = $this->completedOrder($user, 1);
        $this->actingAs($user)->withSession(['locale' => 'ne'])->post('/orders/'.$order->reference.'/reorder')
            ->assertSessionHas('success', 'एउटा जोर जुत्ता झोलामा थपियो।');
    }
}
