<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ValidationLocaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_invalid_contact_fields_never_create_an_order_or_reserve_stock(): void
    {
        foreach (['name' => ' ', 'email' => 'not-an-email', 'phone' => 'call-me', 'address' => 'Here', 'payment_method' => 'invented'] as $field => $invalid) {
            $token = (string) Str::uuid();
            $data = ['name' => 'Eve Customer', 'email' => 'buyer@example.com', 'phone' => '+977 9800000000', 'address' => 'Kathmandu, Nepal', 'payment_method' => 'request', 'token' => $token];
            $data[$field] = $invalid;
            $this->withSession(['cart' => [1 => 1], 'checkout_token' => $token])->post('/checkout', $data)->assertSessionHasErrors($field);
            $this->assertSame(0, Order::count());
            $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 20]);
        }
    }

    public function test_language_switch_persists_and_invalid_locales_do_not_replace_it(): void
    {
        $this->post('/language', ['locale' => 'ne'])->assertRedirect()->assertSessionHas('locale', 'ne');
        $this->get('/')->assertInertia(fn (Assert $p) => $p->where('locale', 'ne'));
        $this->post('/language', ['locale' => 'unsupported'])->assertSessionHasErrors('locale')->assertSessionHas('locale', 'ne');
        $this->post('/language', ['locale' => 'en'])->assertSessionHas('locale', 'en');
    }

    public function test_nepali_checkout_validation_uses_nepali_messages(): void
    {
        $token = (string) Str::uuid();
        $this->withSession(['locale' => 'ne', 'cart' => [1 => 1], 'checkout_token' => $token])->post('/checkout', ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => 'abc', 'address' => 'Kathmandu, Nepal', 'token' => $token])->assertSessionHasErrors(['phone' => '७–१५ अङ्क भएको सही फोन नम्बर लेख्नुहोस्, जस्तै +977 9800000000।']);
    }

    public function test_nepali_product_content_is_used_with_english_fallback(): void
    {
        $product = Product::first();
        $product->update(['name_ne' => 'दैनिक जुत्ता', 'description_ne' => 'दैनिक हिँडाइका लागि आरामदायी जुत्ता।']);
        $this->withSession(['locale' => 'ne'])->get('/products/'.$product->slug)->assertInertia(fn (Assert $p) => $p->where('product.name', 'दैनिक जुत्ता')->where('product.description', 'दैनिक हिँडाइका लागि आरामदायी जुत्ता।'));
        $this->get('/?q='.rawurlencode('दैनिक'))->assertInertia(fn (Assert $p) => $p->has('products.data', 1)->where('products.data.0.name', 'दैनिक जुत्ता'));
        $other = Product::findOrFail(2);
        $this->get('/products/'.$other->slug)->assertInertia(fn (Assert $p) => $p->where('product.name', $other->name));
    }

    public function test_wholesale_price_cannot_exceed_retail_and_customer_cannot_edit_translations(): void
    {
        $product = Product::first();
        $data = ['name' => $product->name, 'retail_price' => 1000, 'wholesale_price' => 2000, 'active' => true, 'name_ne' => 'दैनिक जुत्ता'];
        $this->actingAs(User::factory()->create(['is_admin' => true]))->patch('/admin/products/'.$product->id, $data)->assertSessionHasErrors('wholesale_price');
        $this->assertNull($product->fresh()->name_ne);
        $this->actingAs(User::factory()->create())->patch('/admin/products/'.$product->id, $data)->assertForbidden();
    }

    public function test_invalid_cart_quantities_and_stock_updates_do_not_change_inventory(): void
    {
        foreach ([-1, 0, 1000, '1.5', [1]] as $quantity) {
            $this->post('/cart', ['variant_id' => 1, 'quantity' => $quantity])->assertSessionHasErrors('quantity');
            $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 20]);
        }
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        foreach ([-1, 1000001, '1.5'] as $stock) {
            $this->patch('/admin/variants/1', ['stock' => $stock])->assertSessionHasErrors('stock');
            $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 20]);
        }
    }

    public function test_duplicate_checkout_cannot_start_payment_after_switching_customer_accounts(): void
    {
        $token = (string) Str::uuid();
        $first = User::factory()->create();
        $data = ['name' => 'Buyer', 'email' => 'buyer@example.com', 'phone' => '9800000001', 'address' => 'Kathmandu, Nepal', 'token' => $token];
        $this->actingAs($first)->withSession(['cart' => [1 => 1], 'checkout_token' => $token])->post('/checkout', $data)->assertRedirect();
        $this->actingAs(User::factory()->create())->post('/checkout', $data)->assertSessionHasErrors('token');
        $this->assertSame(1, Order::count());
        $this->assertDatabaseHas('variants', ['id' => 1, 'stock' => 19]);
    }

    public function test_language_cookie_restores_preference_for_a_new_session(): void
    {
        $this->withCookie('locale', 'ne')->get('/login')->assertInertia(fn (Assert $p) => $p->where('locale', 'ne'));
        $this->post('/language', ['locale' => 'en'])->assertCookie('locale', 'en');
    }

    public function test_nepali_phone_digits_are_validated_and_stored_in_normalized_form(): void
    {
        $token = (string) Str::uuid();
        $this->withSession(['locale' => 'ne', 'cart' => [1 => 1], 'checkout_token' => $token])->post('/checkout', ['name' => 'ग्राहक', 'email' => 'buyer@example.com', 'phone' => '+९७७ ९८००००००००', 'address' => 'काठमाडौं, नेपाल', 'token' => $token])->assertRedirect();
        $this->assertSame('+977 9800000000', Order::firstOrFail()->phone);
    }
}
