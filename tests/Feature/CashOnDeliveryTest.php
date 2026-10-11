<?php

namespace Tests\Feature;

use App\Jobs\SendOrderEmail;
use App\Models\Order;
use App\Models\User;
use App\Models\WholesaleAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CashOnDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
        config(['shop.currency' => 'NPR', 'shop.cash_on_delivery' => true]);
        Bus::fake([SendOrderEmail::class]);
        Http::preventStrayRequests();
    }

    private function checkout(array $replace = [], int $quantity = 1)
    {
        $token = (string) Str::uuid();

        return $this->withSession(['cart' => [1 => $quantity], 'checkout_token' => $token])->post('/checkout', array_merge(['name' => 'Shoe Buyer', 'email' => 'buyer@example.com', 'phone' => '9800000000', 'address' => 'Kathmandu, Nepal', 'token' => $token, 'payment_method' => 'cod'], $replace));
    }

    public function test_cash_on_delivery_reserves_stock_and_stays_unpaid_until_staff_records_collection(): void
    {
        $this->checkout()->assertRedirect();
        $order = Order::sole();
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertDatabaseCount('payment_attempts', 0);
        Http::assertNothingSent();
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post('/admin/orders/'.$order->id.'/offline-payment', ['reference' => 'Cash receipt 1001'])->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('Cash receipt 1001', $order->fresh()->offline_payment_reference);
        $this->post('/admin/orders/'.$order->id.'/offline-payment', ['reference' => 'Duplicate'])->assertStatus(422);
    }

    public function test_disabled_or_wrong_currency_cod_cannot_be_submitted_directly(): void
    {
        config(['shop.cash_on_delivery' => false]);
        $this->checkout()->assertSessionHasErrors('payment_method');
        config(['shop.cash_on_delivery' => true, 'shop.currency' => 'JPY']);
        $this->checkout()->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_wholesale_application_and_order_require_a_valid_phone(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/wholesale', ['company' => 'Shoe Retailer'])->assertSessionHasErrors('phone');
        $this->post('/wholesale', ['company' => 'Shoe Retailer', 'phone' => 'invalid'])->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('wholesale_accounts', 0);
        WholesaleAccount::create(['user_id' => $user->id, 'company' => 'Shoe Retailer', 'phone' => '9800000000', 'status' => 'approved']);
        $this->checkout(['phone' => ''], 12)->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('orders', 0);
    }
}
