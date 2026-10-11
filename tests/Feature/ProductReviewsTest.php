<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductReviewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function order(User $user, Product $product, string $status = 'fulfilled', string $paymentStatus = 'paid'): Order
    {
        $variant = $product->variants()->firstOrFail();
        $order = Order::create([
            'reference' => (string) Str::uuid(), 'checkout_token' => (string) Str::uuid(),
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'phone' => '9800000000', 'address' => 'Kathmandu', 'channel' => 'retail',
            'currency' => 'NPR', 'subtotal' => 8900, 'total' => 8900,
            'status' => $status, 'payment_status' => $paymentStatus,
        ]);
        $order->items()->create([
            'variant_id' => $variant->id, 'name' => $product->name, 'sku' => $variant->sku,
            'size' => $variant->size, 'color' => $variant->color,
            'quantity' => 1, 'unit_price' => 8900,
        ]);

        return $order;
    }

    private function review(array $changes = []): array
    {
        return array_merge(['rating' => 5, 'fit' => 'true', 'title' => 'Comfortable pair', 'body' => 'The shoes felt comfortable and held up on my daily walks.'], $changes);
    }

    public function test_only_paid_fulfilled_buyers_can_review_the_shoe_they_bought(): void
    {
        $user = User::factory()->create();
        $product = Product::firstOrFail();
        $this->post('/products/'.$product->slug.'/reviews', $this->review())->assertRedirect('/login');
        $this->actingAs($user)->post('/products/'.$product->slug.'/reviews', $this->review())->assertSessionHasErrors('review');
        $order = $this->order($user, $product, 'confirmed', 'paid');
        $this->post('/products/'.$product->slug.'/reviews', $this->review())->assertSessionHasErrors('review');
        $order->update(['status' => 'fulfilled', 'payment_status' => 'unpaid']);
        $this->post('/products/'.$product->slug.'/reviews', $this->review())->assertSessionHasErrors('review');
        $order->update(['payment_status' => 'paid']);
        $this->post('/products/pace-runner/reviews', $this->review())->assertSessionHasErrors('review');
        $this->post('/products/'.$product->slug.'/reviews', $this->review())->assertSessionHasNoErrors();
        $this->assertDatabaseHas('product_reviews', ['user_id' => $user->id, 'product_id' => $product->id, 'order_id' => $order->id, 'rating' => 5]);
    }

    public function test_review_is_validated_and_one_buyer_can_update_one_review(): void
    {
        $user = User::factory()->create();
        $product = Product::firstOrFail();
        $this->order($user, $product);
        $this->actingAs($user)->post('/products/'.$product->slug.'/reviews', $this->review([
            'rating' => 6, 'fit' => 'unknown', 'title' => 'X', 'body' => 'short',
        ]))->assertSessionHasErrors(['rating', 'fit', 'title', 'body']);
        $this->assertDatabaseCount('product_reviews', 0);
        $this->post('/products/'.$product->slug.'/reviews', $this->review())->assertSessionHasNoErrors();
        $this->post('/products/'.$product->slug.'/reviews', $this->review([
            'rating' => 2, 'fit' => 'small', 'title' => 'A little snug',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('product_reviews', 1);
        $this->get('/products/'.$product->slug)->assertInertia(fn (Assert $page) => $page
            ->where('reviewSummary.count', 1)->where('reviewSummary.average', 2)
            ->where('reviewSummary.fit.small', 1)->where('reviews.data.0.title', 'A little snug')
            ->where('ownReview.rating', 2)->where('canReview', true)
            ->missing('reviews.data.0.user_id')->missing('reviews.data.0.order_id'));
    }

    public function test_reviews_are_public_but_edit_rights_and_purchase_details_remain_private(): void
    {
        $buyer = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::firstOrFail();
        $this->order($buyer, $product);
        $this->actingAs($buyer)->post('/products/'.$product->slug.'/reviews', $this->review());
        $this->actingAs($other)->get('/products/'.$product->slug)->assertInertia(fn (Assert $page) => $page
            ->where('reviewSummary.count', 1)->where('reviews.data.0.rating', 5)
            ->where('canReview', false)->where('ownReview', null));
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.review_count', 1)->where('products.data.0.review_average', 5));
        $this->post('/products/'.$product->slug.'/reviews', $this->review(['rating' => 1]))->assertSessionHasErrors('review');
        $this->assertSame(5, DB::table('product_reviews')->first()->rating);
        $product->update(['active' => false]);
        $this->actingAs($buyer)->post('/products/'.$product->slug.'/reviews', $this->review())->assertNotFound();
    }

    public function test_nepali_review_validation_and_success_message(): void
    {
        $user = User::factory()->create();
        $product = Product::firstOrFail();
        $this->order($user, $product);
        $this->actingAs($user)->withSession(['locale' => 'ne'])->post('/products/'.$product->slug.'/reviews', $this->review(['rating' => 6]))
            ->assertSessionHasErrors(['rating' => 'मूल्याङ्कन 1 देखि 5 सम्म हुनुपर्छ।']);
        $this->post('/products/'.$product->slug.'/reviews', $this->review())
            ->assertSessionHas('success', 'आफ्नो अनुभव बाँड्नुभएकोमा धन्यवाद।');
    }
}
