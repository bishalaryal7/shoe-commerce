<?php

namespace Tests\Feature;

use App\Jobs\SendOrderEmail;
use App\Models\Order;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnpaidOrderExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config([
            'shop.currency' => 'NPR',
            'payments.khalti.enabled' => true,
            'payments.khalti.secret_key' => 'test-key',
            'payments.khalti.sandbox' => true,
        ]);
        Bus::fake([SendOrderEmail::class]);
        Http::preventStrayRequests();
    }

    private function order(array $overrides = []): array
    {
        $user = User::factory()->create();
        $product = Product::firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $variant->decrement('stock');
        $order = Order::create(array_merge([
            'reference' => (string) Str::uuid(),
            'checkout_token' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '9800000000',
            'address' => 'Kathmandu, Nepal',
            'channel' => 'retail',
            'currency' => 'NPR',
            'subtotal' => 8900,
            'total' => 8900,
            'status' => 'pending_confirmation',
            'payment_method' => 'khalti',
            'payment_status' => 'pending',
        ], $overrides));
        $order->items()->create([
            'variant_id' => $variant->id,
            'name' => $product->name,
            'sku' => $variant->sku,
            'size' => $variant->size,
            'color' => $variant->color,
            'quantity' => 1,
            'unit_price' => 8900,
        ]);
        $attempt = PaymentAttempt::create([
            'reference' => (string) Str::uuid(),
            'order_id' => $order->id,
            'provider' => 'khalti',
            'provider_id' => 'test-pidx',
            'amount' => 8900,
            'currency' => 'NPR',
            'status' => 'initiated',
            'expires_at' => now()->subMinute(),
            'sandbox' => true,
        ]);

        return [$order, $variant, $attempt];
    }

    public function test_expired_checkout_releases_reserved_stock_only_after_provider_confirms_failure(): void
    {
        [$order, $variant] = $this->order();
        Http::fake(['*epayment/lookup/*' => Http::response([
            'pidx' => 'test-pidx', 'total_amount' => 8900, 'status' => 'Expired',
        ])]);

        $this->artisan('shop:expire-unpaid-orders')
            ->expectsOutputToContain('cancelled 1 order(s)')
            ->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertSame(20, $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'order_id' => $order->id,
            'quantity' => 1,
            'reason' => 'order_cancelled',
        ]);
        $this->assertDatabaseHas('order_events', [
            'order_id' => $order->id,
            'event' => 'cancelled',
        ]);
        $this->assertDatabaseHas('order_emails', [
            'order_id' => $order->id,
            'event' => 'payment_expired',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $order->user_id,
            'type' => 'App\\Notifications\\StoreUpdate',
        ]);
        Http::assertSentCount(1);
    }

    public function test_pending_provider_result_and_provider_failure_keep_inventory_reserved(): void
    {
        [$order, $variant] = $this->order();
        Http::fake(['*epayment/lookup/*' => Http::response([
            'pidx' => 'test-pidx', 'total_amount' => 8900, 'status' => 'Pending',
        ])]);

        $this->artisan('shop:expire-unpaid-orders')->assertSuccessful();

        $this->assertSame('pending_confirmation', $order->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(19, $variant->fresh()->stock);

        Http::fake(fn () => throw new ConnectionException('Provider offline'));
        $this->artisan('shop:expire-unpaid-orders')->assertFailed();
        $this->assertSame(19, $variant->fresh()->stock);
        $this->assertSame('pending_confirmation', $order->fresh()->status);
    }

    public function test_only_latest_due_online_payment_session_is_reconciled(): void
    {
        [$order, $variant, $oldAttempt] = $this->order();
        PaymentAttempt::create([
            'reference' => (string) Str::uuid(),
            'order_id' => $order->id,
            'provider' => 'khalti',
            'provider_id' => 'new-pidx',
            'amount' => 8900,
            'currency' => 'NPR',
            'status' => 'initiated',
            'expires_at' => now()->addMinutes(20),
            'sandbox' => true,
        ]);
        Http::fake();

        $this->artisan('shop:expire-unpaid-orders')->expectsOutputToContain('Checked 0 expired')->assertSuccessful();

        $this->assertSame('initiated', $oldAttempt->fresh()->status);
        $this->assertSame(19, $variant->fresh()->stock);
        Http::assertNothingSent();
    }

    public function test_previously_verified_failure_is_released_without_an_extra_provider_lookup(): void
    {
        [$order, $variant, $attempt] = $this->order(['payment_status' => 'unpaid']);
        $attempt->update(['status' => 'failed', 'verified_at' => now()]);
        Http::fake();

        $this->artisan('shop:expire-unpaid-orders')
            ->expectsOutputToContain('cancelled 1 order(s)')
            ->assertSuccessful();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(20, $variant->fresh()->stock);
        Http::assertNothingSent();
    }

    public function test_orders_without_an_expired_online_attempt_are_ignored(): void
    {
        [$order, $variant] = $this->order(['payment_method' => 'cod', 'payment_status' => 'unpaid']);
        PaymentAttempt::where('order_id', $order->id)->delete();
        Http::fake();

        $this->artisan('shop:expire-unpaid-orders')->expectsOutputToContain('Checked 0 expired')->assertSuccessful();

        $this->assertSame('pending_confirmation', $order->fresh()->status);
        $this->assertSame(19, $variant->fresh()->stock);
        Http::assertNothingSent();
    }
}
