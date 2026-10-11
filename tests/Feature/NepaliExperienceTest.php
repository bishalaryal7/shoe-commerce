<?php

namespace Tests\Feature;

use App\Mail\OrderUpdate;
use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderEmails;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NepaliExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_nepali_catalog_search_cart_and_flash_use_the_same_translated_product(): void
    {
        $this->withSession(['locale' => 'ne'])->get('/?q='.rawurlencode('सिटी'))->assertInertia(fn (Assert $page) => $page->has('products.data', 1)->where('products.data.0.name', 'सिटी लो'));
        $this->post('/cart', ['variant_id' => 1, 'quantity' => 1])->assertSessionHas('success', 'तपाईंको झोलामा राखियो।');
        $this->get('/cart')->assertInertia(fn (Assert $page) => $page->where('lines.0.name', 'सिटी लो')->where('lines.0.quantity', 1));
        $this->post('/language', ['locale' => 'en']);
        $this->get('/cart')->assertInertia(fn (Assert $page) => $page->where('lines.0.name', 'City Low')->where('lines.0.quantity', 1));
    }

    public function test_stock_and_staff_validation_messages_do_not_fall_back_to_english(): void
    {
        $this->withSession(['locale' => 'ne'])->post('/cart', ['variant_id' => 1, 'quantity' => 21])->assertSessionHasErrors(['quantity' => 'त्यस साइजमा पर्याप्त मौज्दात छैन।']);
        $this->actingAs(User::factory()->create(['is_admin' => true]))->patch('/admin/variants/1', ['stock' => 'abc'])->assertSessionHasErrors(['stock' => 'मौज्दात पूर्ण सङ्ख्या हुनुपर्छ।']);
        $this->post('/admin/team', ['email' => 'missing@example.com'])->assertSessionHasErrors(['email' => 'यस व्यक्तिले पहिले खाता खोल्नुपर्छ।']);
    }

    public function test_staff_can_create_nepali_product_content_without_translating_enum_values(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->withSession(['locale' => 'ne'])->post('/admin/products', [
            'name' => 'New Trail', 'name_ne' => 'नयाँ पहाडी जुत्ता', 'category' => 'Outdoor',
            'description' => 'Comfortable shoes for the trail.', 'description_ne' => 'पहाडी बाटोमा हिँड्न आरामदायी जुत्ता।',
            'retail_price' => 200000, 'wholesale_price' => 150000, 'color' => 'green', 'sizes' => ['40'], 'stock' => 5,
        ])->assertSessionHasNoErrors();
        $product = Product::where('name', 'New Trail')->firstOrFail();
        $this->assertSame('Outdoor', $product->category);
        $this->assertSame('green', $product->variants->first()->color);
        $this->get('/products/'.$product->slug)->assertInertia(fn (Assert $page) => $page->where('product.name', 'नयाँ पहाडी जुत्ता')->where('product.description', 'पहाडी बाटोमा हिँड्न आरामदायी जुत्ता।'));
    }

    public function test_queued_order_emails_keep_the_customer_language_after_staff_switch_to_english(): void
    {
        $this->actingAs(User::factory()->create());
        $token = (string) Str::uuid();
        $this->withSession(['locale' => 'ne', 'cart' => [1 => 1], 'checkout_token' => $token])->post('/checkout', [
            'name' => 'ग्राहक', 'email' => 'buyer@example.com', 'phone' => '9800000000', 'address' => 'काठमाडौं, नेपाल', 'token' => $token,
        ])->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertSame('ne', OrderEmail::where('event', 'created')->firstOrFail()->payload['locale']);
        app()->setLocale('en');
        app(OrderEmails::class)->record($order, 'confirmed');
        $email = OrderEmail::where('event', 'confirmed')->firstOrFail();
        $this->assertSame('ne', $email->payload['locale']);
        $mail = new OrderUpdate($email->payload, 'confirmed');
        $html = $mail->render();
        $this->assertStringContainsString('तपाईंको अर्डर पुष्टि भयो।', $html);
        $this->assertStringContainsString('आफ्नो अर्डर हेर्नुहोस्', $html);
        $this->assertStringContainsString('सिटी लो', $html);
        $this->assertStringNotContainsString('Your order is confirmed.', $html);
        $this->assertSame('en', app()->getLocale());
    }

    public function test_account_validation_and_password_reset_notifications_use_nepali(): void
    {
        $this->withSession(['locale' => 'ne'])->post('/register', ['name' => 'ग्राहक', 'email' => 'invalid', 'password' => 'secret123', 'password_confirmation' => 'different'])
            ->assertSessionHasErrors(['email' => 'सही इमेल ठेगाना लेख्नुहोस्।', 'password' => 'पासवर्ड र पुष्टि गर्न लेखिएको विवरण मिलेन।']);
        app()->setLocale('ne');
        $mail = (new ResetPassword('test-token'))->toMail(User::factory()->create());
        $this->assertSame('पासवर्ड फेरि सेट गर्ने सूचना', $mail->subject);
        $this->assertSame('पासवर्ड फेरि सेट गर्नुहोस्', $mail->actionText);
        $this->assertStringContainsString('अनुरोध प्राप्त भएकाले', $mail->introLines[0]);
    }
}
