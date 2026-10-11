<?php

namespace Tests\Feature;

use App\Mail\BackInStock;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StoreFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function checkout(User $user, int $variantId, string $token, array $extra = [])
    {
        return $this->actingAs($user)->withSession(['cart' => [$variantId => 1], 'checkout_token' => $token])->post('/checkout', array_merge([
            'name' => $user->name, 'email' => $user->email, 'phone' => '9800000000', 'address' => 'Kathmandu, Nepal', 'token' => $token,
        ], $extra));
    }

    public function test_size_alerts_are_sent_only_when_staff_restocks_the_subscribed_variant(): void
    {
        Mail::fake();
        $product = Product::where('slug', 'city-low')->firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $variant->update(['stock' => 0]);
        $this->post('/products/'.$product->slug.'/stock-alerts', ['variant_id' => $variant->id, 'email' => 'SHOES@example.com'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('stock_alerts', ['variant_id' => $variant->id, 'email' => 'shoes@example.com', 'notified_at' => null]);

        $staff = User::factory()->create(['is_admin' => true]);
        $this->actingAs($staff)->patch('/admin/variants/'.$variant->id, ['stock' => 5])->assertRedirect();
        Mail::assertSent(BackInStock::class, fn (BackInStock $mail) => $mail->hasTo('shoes@example.com') && $mail->size === $variant->size);
        $this->assertNotNull(\DB::table('stock_alerts')->where('variant_id', $variant->id)->where('email', 'shoes@example.com')->value('notified_at'));
    }

    public function test_checkout_applies_a_server_calculated_promotion_once_and_rejects_invalid_codes(): void
    {
        $buyer = User::factory()->create();
        $variant = Product::where('slug', 'city-low')->firstOrFail()->variants()->firstOrFail();
        Promotion::create(['code' => 'WELCOME10', 'type' => 'percent', 'amount' => 10, 'minimum_subtotal' => 5000, 'channel' => 'retail', 'active' => true]);
        $this->actingAs($buyer)->withSession(['cart' => [$variant->id => 1]])->getJson('/checkout/promotion?code=WELCOME10')->assertOk()->assertJson(['discount' => 890, 'total' => 8010]);
        $this->assertSame(0, Promotion::firstOrFail()->uses_count);
        $token = (string) Str::uuid();
        $this->checkout($buyer, $variant->id, $token, ['promotion_code' => ' welcome10 '])->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame(890, $order->discount_amount);
        $this->assertSame('WELCOME10', $order->promotion_code);
        $this->assertSame(8010, $order->total);
        $this->assertSame(1, Promotion::firstOrFail()->uses_count);
        $this->checkout($buyer, $variant->id, $token, ['promotion_code' => 'WELCOME10'])->assertRedirect();
        $this->assertSame(1, Promotion::firstOrFail()->uses_count);

        $freshToken = (string) Str::uuid();
        $stockBefore = $variant->fresh()->stock;
        $this->checkout($buyer, $variant->id, $freshToken, ['promotion_code' => 'NOT-A-CODE'])->assertSessionHasErrors('promotion_code');
        $this->assertSame(1, Order::count());
        $this->assertSame($stockBefore, $variant->fresh()->stock);
    }

    public function test_shipping_requires_paid_orders_and_customer_tracking_shows_carrier_and_number(): void
    {
        $buyer = User::factory()->create();
        $variant = Product::firstOrFail()->variants()->firstOrFail();
        $token = (string) Str::uuid();
        $this->checkout($buyer, $variant->id, $token)->assertRedirect();
        $order = Order::firstOrFail();
        $staff = User::factory()->create(['is_admin' => true]);
        $this->actingAs($staff)->patch('/admin/orders/'.$order->id.'/shipping', ['status' => 'shipped', 'tracking_carrier' => 'Nepal Post', 'tracking_number' => 'NP123'])->assertStatus(422);

        $order->update(['status' => 'confirmed', 'payment_status' => 'paid']);
        $this->patch('/admin/orders/'.$order->id.'/shipping', ['status' => 'shipped', 'tracking_carrier' => 'Nepal Post', 'tracking_number' => 'NP123'])->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped', 'tracking_number' => 'NP123']);
        $this->actingAs($buyer)->get('/orders/track/'.$order->reference)->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('order.status', 'shipped')->where('order.tracking_carrier', 'Nepal Post')->where('order.tracking_number', 'NP123'));
        $this->actingAs(User::factory()->create())->get('/orders/track/'.$order->reference)->assertForbidden();
    }

    public function test_product_questions_are_moderated_and_only_published_answers_are_public(): void
    {
        $buyer = User::factory()->create();
        $product = Product::where('slug', 'city-low')->firstOrFail();
        $this->actingAs($buyer)->post('/products/'.$product->slug.'/questions', ['question' => 'Does this shoe have a removable insole?'])->assertRedirect();
        $question = $product->questions()->firstOrFail();
        $this->get('/products/'.$product->slug)->assertInertia(fn (Assert $page) => $page->has('questions', 0));
        $staff = User::factory()->create(['is_admin' => true]);
        $this->actingAs($staff)->patch('/admin/questions/'.$question->id, ['answer' => 'Yes, the cushioned insole can be removed.'])->assertRedirect();
        $this->get('/products/'.$product->slug)->assertInertia(fn (Assert $page) => $page->where('questions.0.answer', 'Yes, the cushioned insole can be removed.'));
        $this->actingAs($buyer)->post('/products/'.$product->slug.'/questions', ['question' => 'x'])->assertSessionHasErrors('question');
    }

    public function test_saved_addresses_are_private_and_can_be_used_at_checkout(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $payload = ['label' => 'Home', 'recipient' => 'Shoe Buyer', 'phone' => '9800000000', 'address' => 'Kathmandu, Nepal, near the park', 'is_default' => true];
        $this->actingAs($owner)->post('/settings/addresses', $payload)->assertRedirect();
        $addressId = (int) $owner->addresses()->value('id');
        $this->actingAs($other)->patch('/settings/addresses/'.$addressId, [...$payload, 'recipient' => 'Changed'])->assertNotFound();
        $variant = Product::firstOrFail()->variants()->firstOrFail();
        $this->actingAs($owner)->withSession(['cart' => [$variant->id => 1]])->get('/checkout')->assertInertia(fn (Assert $page) => $page->where('addresses.0.id', $addressId));

        $token = (string) Str::uuid();
        $this->withSession(['cart' => [$variant->id => 1], 'checkout_token' => $token])->post('/checkout', [...$payload, 'email' => $owner->email, 'token' => $token, 'address_id' => $addressId, 'name' => 'Tampered', 'phone' => '9800000000'])->assertRedirect();
        $this->assertSame('Shoe Buyer', Order::firstOrFail()->name);
        $this->assertSame($payload['address'], Order::firstOrFail()->address);
    }
}
