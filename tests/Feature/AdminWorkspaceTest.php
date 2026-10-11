<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\PaymentAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge(['reference' => (string) Str::uuid(), 'checkout_token' => (string) Str::uuid(), 'name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '9800000000', 'address' => 'Kathmandu', 'channel' => 'retail', 'currency' => 'NPR', 'subtotal' => 1000, 'total' => 1000], $overrides));
    }

    public function test_staff_can_reach_older_orders_and_search_without_leaking_checkout_tokens(): void
    {
        $oldest = $this->order(['name' => 'Older customer']);
        for ($i = 0; $i < 22; $i++) {
            $this->order();
        }
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin')->assertInertia(fn (Assert $p) => $p->has('orders.data', 20)->where('orders.total', 23)->missing('orders.data.0.checkout_token'));
        $this->get('/admin?orders_page=2')->assertInertia(fn (Assert $p) => $p->has('orders.data', 3)->where('orders.data.2.reference', $oldest->reference));
        $this->get('/admin?search=Older')->assertInertia(fn (Assert $p) => $p->has('orders.data', 1)->where('orders.data.0.reference', $oldest->reference));
    }

    public function test_staff_order_filters_are_combined_and_invalid_values_rejected(): void
    {
        $matching = $this->order(['status' => 'confirmed', 'payment_status' => 'review']);
        $this->order(['status' => 'confirmed', 'payment_status' => 'paid']);
        $this->order(['status' => 'cancelled', 'payment_status' => 'review']);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/admin?status=confirmed&payment=review')->assertInertia(fn (Assert $p) => $p->has('orders.data', 1)->where('orders.data.0.reference', $matching->reference));
        $this->get('/admin?status=unknown')->assertSessionHasErrors('status');
    }

    public function test_order_audit_identifies_staff_and_payment_pages_remain_independent(): void
    {
        $staff = User::factory()->create(['is_admin' => true]);
        $order = $this->order();
        OrderEvent::create(['order_id' => $order->id, 'actor_id' => $staff->id, 'event' => 'confirmed']);
        for ($i = 0; $i < 21; $i++) {
            PaymentAttempt::create(['reference' => (string) Str::uuid(), 'provider' => 'khalti', 'amount' => 1000, 'currency' => 'NPR', 'sandbox' => true]);
        }
        $this->actingAs($staff)->get('/admin?payments_page=2')->assertInertia(fn (Assert $p) => $p->has('payments.data', 1)->has('orders.data', 1)->where('orders.data.0.events.0.actor.name', $staff->name));
        $this->actingAs(User::factory()->create())->get('/admin?search=Buyer')->assertForbidden();
    }

    public function test_deployment_readiness_requires_the_inventory_and_order_audit_schema(): void
    {
        $this->get('/ready')->assertOk()->assertExactJson(['status' => 'ready']);
        Schema::drop('inventory_movements');
        $this->get('/ready')->assertStatus(503)->assertExactJson(['status' => 'unavailable']);
    }

    public function test_customer_sees_staff_access_help_without_becoming_staff(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden()->assertInertia(fn (Assert $p) => $p->component('admin/Access')->where('auth.user.email', $user->email));
        $this->assertFalse($user->fresh()->is_admin);
    }
}
