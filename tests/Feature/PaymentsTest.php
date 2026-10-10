<?php

namespace Tests\Feature;

use App\Jobs\SendOrderEmail;
use App\Mail\OrderUpdate;
use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\PaymentAttempt;
use App\Models\User;
use App\Payments\PayPayGateway;
use App\Services\OrderPayments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
        config(['shop.currency' => 'NPR', 'payments.khalti.enabled' => true, 'payments.khalti.secret_key' => 'test-key', 'payments.khalti.sandbox' => true,
            'payments.paypay.enabled' => true, 'payments.paypay.api_key' => 'sample_key', 'payments.paypay.api_secret' => 'sample_secret', 'payments.paypay.merchant_id' => 'test-merchant', 'payments.paypay.sandbox' => true]);
        Http::preventStrayRequests();
        Mail::fake();
        Bus::fake([SendOrderEmail::class]);
    }

    private function order(): Order
    {
        $token = (string) Str::uuid();
        $this->withSession(['cart' => [1 => 2], 'checkout_token' => $token])->post('/checkout', ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '9800000001', 'address' => 'Example, Kathmandu', 'token' => $token])->assertRedirect();

        return Order::latest('id')->first();
    }

    private function initiate(): void
    {
        Http::fake(['*epayment/initiate/' => Http::response(['pidx' => 'test-pidx', 'payment_url' => 'https://test-pay.khalti.com/?pidx=test-pidx', 'expires_at' => now()->addMinutes(30)->toIso8601String()])]);
    }

    private function lookup(string $status = 'Completed', int $amount = 17800, string $transaction = 'test-transaction'): void
    {
        Http::fake(['*epayment/lookup/' => Http::response(['pidx' => 'test-pidx', 'total_amount' => $amount, 'status' => $status, 'transaction_id' => $transaction])]);
    }

    public function test_khalti_initiation_uses_the_saved_total_and_reuses_checkout(): void
    {
        $order = $this->order();
        $this->initiate();
        $this->post('/orders/'.$order->reference.'/pay', ['provider' => 'khalti', 'amount' => 1])->assertRedirect('https://test-pay.khalti.com/?pidx=test-pidx');
        Http::assertSent(fn ($r) => $r['amount'] === 17800 && $r->hasHeader('Authorization', 'Key test-key'));
        $this->post('/orders/'.$order->reference.'/pay', ['provider' => 'khalti'])->assertRedirect('https://test-pay.khalti.com/?pidx=test-pidx');
        $this->assertSame(1, PaymentAttempt::count());
        Http::assertSentCount(1);
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_browser_success_parameters_never_mark_an_unverified_payment_paid(): void
    {
        $order = $this->order();
        $this->initiate();
        $this->post('/orders/'.$order->reference.'/pay', ['provider' => 'khalti']);
        $attempt = PaymentAttempt::first();
        $this->lookup('Pending');
        $this->get('/payments/'.$attempt->reference.'/return?pidx=test-pidx&status=Completed&amount=17800')->assertRedirect();
        $this->assertSame('pending', $order->fresh()->payment_status);
    }

    public function test_verified_callback_is_idempotent_and_sends_one_payment_event(): void
    {
        $order = $this->order();
        $this->initiate();
        $this->post('/orders/'.$order->reference.'/pay', ['provider' => 'khalti']);
        $attempt = PaymentAttempt::first();
        $this->lookup();
        $this->get('/payments/'.$attempt->reference.'/return?pidx=test-pidx')->assertRedirect();
        $this->get('/payments/'.$attempt->reference.'/return?pidx=test-pidx')->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame(1, OrderEmail::where('event', 'paid')->count());
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 18]);
    }

    public function test_amount_mismatch_is_held_for_review(): void
    {
        $order = $this->order();
        $this->initiate();
        $this->post('/orders/'.$order->reference.'/pay', ['provider' => 'khalti']);
        $this->lookup('Completed', 1);
        $this->assertSame('review', app(OrderPayments::class)->verify(PaymentAttempt::first()));
        $this->assertSame('review', $order->fresh()->payment_status);
        $this->assertNull($order->fresh()->paid_at);
    }

    public function test_callback_for_another_payment_id_is_rejected_before_lookup(): void
    {
        $order = $this->order();
        $this->initiate();
        $this->post('/orders/'.$order->reference.'/pay', ['provider' => 'khalti']);
        $this->get('/payments/'.PaymentAttempt::first()->reference.'/return?pidx=other-pidx')->assertStatus(422);
        Http::assertSentCount(1);
    }

    public function test_unknown_initiation_outcome_prevents_a_second_payment(): void
    {
        $order = $this->order();
        Http::fake(fn () => throw new ConnectionException('Simulated timeout'));
        $this->post('/orders/'.$order->reference.'/pay', ['provider' => 'khalti'])->assertSessionHasErrors('payment_method');
        $this->assertSame('review', $order->fresh()->payment_status);
        $this->post('/orders/'.$order->reference.'/pay', ['provider' => 'khalti'])->assertSessionHasErrors('payment_method');
        $this->assertSame(1, PaymentAttempt::count());
    }

    public function test_unconfigured_methods_are_rejected_without_reserving_stock(): void
    {
        config(['payments.khalti.enabled' => false]);
        $token = (string) Str::uuid();
        $this->withSession(['cart' => [1 => 1], 'checkout_token' => $token])->post('/checkout', ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '123', 'address' => 'Nepal', 'token' => $token, 'payment_method' => 'khalti'])->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 20]);
    }

    public function test_nepal_checkout_does_not_offer_jpy_paypay(): void
    {
        $this->withSession(['cart' => [1 => 1]])->get('/checkout')->assertInertia(fn (Assert $p) => $p->has('paymentOptions', 1)->where('paymentOptions.0.id', 'khalti'));
        $order = $this->order();
        $this->post('/orders/'.$order->reference.'/pay', ['provider' => 'paypay'])->assertSessionHasErrors('payment_method');
        Http::assertNothingSent();
    }

    public function test_paypay_hmac_matches_the_official_get_reference_vector(): void
    {
        $header = app(PayPayGateway::class)->authorization('/v2/codes/payments/TEST_merchantPaymentID', 'GET', null, 'a360e2', '1743139337');
        $this->assertSame('hmac OPA-Auth:sample_key:4GBwHd1rzZ5CrMRieDW/jDQcvOCeVhg4bBx4iZ6Hzw0=:a360e2:1743139337:empty', $header);
    }

    public function test_paypay_sandbox_lookup_checks_yen_amount_without_creating_an_order(): void
    {
        $ref = (string) Str::uuid();
        $attempt = PaymentAttempt::create(['reference' => $ref, 'provider' => 'paypay', 'provider_id' => $ref, 'amount' => 100, 'currency' => 'JPY', 'sandbox' => true]);
        Http::fake(['*v2/codes/payments/*' => Http::response(['resultInfo' => ['code' => 'SUCCESS'], 'data' => ['merchantPaymentId' => $ref, 'amount' => ['amount' => 100, 'currency' => 'JPY'], 'status' => 'COMPLETED', 'paymentId' => 'jpy-transaction']])]);
        $this->assertSame('paid', app(OrderPayments::class)->verify($attempt));
        $this->assertSame(0, Order::count());
        Http::assertSent(fn ($request) => $request->method() === 'GET' && ! $request->hasHeader('Content-Type') && str_ends_with($request->header('Authorization')[0], ':empty'));
    }

    public function test_users_cannot_pay_or_read_another_customers_order(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = $this->order();
        $this->actingAs(User::factory()->create())->post('/orders/'.$order->reference.'/pay', ['provider' => 'khalti'])->assertForbidden();
        $this->get('/orders/track/'.$order->reference)->assertForbidden();
        $this->get('/orders')->assertInertia(fn (Assert $p) => $p->has('orders.data', 0));
        Http::assertNothingSent();
    }

    public function test_private_guest_tracking_links_expire_and_reject_tampering(): void
    {
        $order = $this->order();
        $url = URL::temporarySignedRoute('orders.track', now()->addMinute(), ['reference' => $order->reference]);
        $this->get($url)->assertOk();
        $this->get($url.'&extra=1')->assertForbidden();
        $this->travel(2)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_shipping_and_tax_are_calculated_and_saved_on_the_server(): void
    {
        config(['shop.shipping_amount' => 5000, 'shop.tax_basis_points' => 1300]);
        $order = $this->order();
        $this->assertSame(17800, $order->subtotal);
        $this->assertSame(5000, $order->shipping_amount);
        $this->assertSame(2314, $order->tax_amount);
        $this->assertSame(25114, $order->total);
    }

    public function test_email_outbox_delivery_is_repeat_safe_and_renders_the_receipt(): void
    {
        $order = $this->order();
        $email = OrderEmail::where('order_id', $order->id)->first();
        $job = new SendOrderEmail($email->id);
        $job->handle();
        $job->handle();
        Mail::assertSent(OrderUpdate::class, 1);
        $this->assertNotNull($email->fresh()->sent_at);
        $mail = new OrderUpdate($email->payload, 'created');
        $html = $mail->render();
        $this->assertStringContainsString('City Low', $html);
        $this->assertStringContainsString($order->reference, $html);
    }

    public function test_staff_cannot_cancel_an_unsettled_online_payment_and_release_stock(): void
    {
        $order = $this->order();
        $this->initiate();
        $this->post('/orders/'.$order->reference.'/pay', ['provider' => 'khalti']);
        $staff = User::factory()->create();
        $staff->forceFill(['is_admin' => true])->save();
        $this->actingAs($staff)->patch('/admin/orders/'.$order->id, ['status' => 'cancelled'])->assertStatus(422);
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 18]);
    }

    public function test_offline_receipt_is_audited_and_allows_fulfillment_only_once(): void
    {
        $order = $this->order();
        $staff = User::factory()->create(['is_admin' => true]);
        $this->actingAs($staff)->patch('/admin/orders/'.$order->id, ['status' => 'confirmed'])->assertRedirect();
        $this->patch('/admin/orders/'.$order->id, ['status' => 'fulfilled'])->assertStatus(422);
        $this->post('/admin/orders/'.$order->id.'/offline-payment', ['reference' => 'BANK-123'])->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('order_events', ['order_id' => $order->id, 'actor_id' => $staff->id, 'event' => 'offline_payment_received']);
        $this->post('/admin/orders/'.$order->id.'/offline-payment', ['reference' => 'BANK-123'])->assertStatus(422);
        $this->patch('/admin/orders/'.$order->id, ['status' => 'fulfilled'])->assertRedirect();
        $this->assertSame('fulfilled', $order->fresh()->status);
        $this->assertDatabaseHas('inventory_movements', ['order_id' => $order->id, 'quantity' => -2, 'reason' => 'order_reserved']);
    }

    public function test_verified_refund_does_not_restock_or_regress_to_pending(): void
    {
        $order = $this->order();
        $this->initiate();
        $attempt = app(OrderPayments::class)->start($order, 'khalti');
        Http::fake(['*epayment/lookup/' => Http::sequence()
            ->push(['pidx' => 'test-pidx', 'total_amount' => 17800, 'status' => 'Completed', 'transaction_id' => 'test-transaction'])
            ->push(['pidx' => 'test-pidx', 'total_amount' => 17800, 'status' => 'Refunded', 'transaction_id' => 'test-transaction'])
            ->push(['pidx' => 'test-pidx', 'total_amount' => 17800, 'status' => 'Pending', 'transaction_id' => 'test-transaction'])]);
        $this->assertSame('paid', app(OrderPayments::class)->verify($attempt));
        $this->assertSame('refunded', app(OrderPayments::class)->verify($attempt));
        $this->assertSame('refunded', $order->fresh()->payment_status);
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 18]);
        $this->assertSame('refunded', app(OrderPayments::class)->verify($attempt));
        $this->assertSame(1, OrderEmail::where('order_id', $order->id)->where('event', 'refunded')->count());
    }
}
