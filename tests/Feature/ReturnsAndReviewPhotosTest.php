<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReturnsAndReviewPhotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function orderFor(User $user, Product $product, array $changes = []): Order
    {
        $variant = $product->variants()->firstOrFail();
        $order = Order::create(array_merge([
            'reference' => (string) Str::uuid(),
            'checkout_token' => (string) Str::uuid(),
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '9800000000',
            'address' => 'Kathmandu',
            'channel' => 'retail',
            'currency' => 'NPR',
            'subtotal' => 8900,
            'total' => 8900,
            'status' => 'fulfilled',
            'payment_status' => 'paid',
            'delivered_at' => now()->subDays(5),
        ], $changes));
        $order->items()->create([
            'variant_id' => $variant->id,
            'name' => $product->name,
            'sku' => $variant->sku,
            'size' => $variant->size,
            'color' => $variant->color,
            'quantity' => 1,
            'unit_price' => 8900,
        ]);

        return $order;
    }

    private function reviewData(): array
    {
        return [
            'rating' => 5,
            'fit' => 'true',
            'title' => 'Comfortable pair',
            'body' => 'The shoes felt comfortable and held up on my daily walks.',
        ];
    }

    public function test_verified_buyers_can_attach_safe_review_photos_and_keep_them_when_editing_text(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create();
        $product = Product::where('slug', 'city-low')->firstOrFail();
        $this->orderFor($buyer, $product);

        $this->actingAs($buyer)->post('/products/'.$product->slug.'/reviews', [
            ...$this->reviewData(),
            'photo' => UploadedFile::fake()->image('shoe.jpg', 800, 800),
        ])->assertSessionHasNoErrors();

        $review = ProductReview::firstOrFail();
        $this->assertNotNull($review->photo_path);
        Storage::disk('public')->assertExists($review->photo_path);
        $this->post('/products/'.$product->slug.'/reviews', [...$this->reviewData(), 'title' => 'Updated fit review'])
            ->assertSessionHasNoErrors();
        $this->assertSame($review->photo_path, $review->fresh()->photo_path);

        $this->get('/products/'.$product->slug)->assertInertia(fn (Assert $page) => $page
            ->where('reviews.data.0.title', 'Updated fit review')
            ->where('reviews.data.0.photo_url', Storage::disk('public')->url($review->photo_path)));
    }

    public function test_review_photo_validation_rejects_non_images_and_oversized_uploads(): void
    {
        $buyer = User::factory()->create();
        $product = Product::where('slug', 'city-low')->firstOrFail();
        $this->orderFor($buyer, $product);

        $this->actingAs($buyer)->post('/products/'.$product->slug.'/reviews', [
            ...$this->reviewData(),
            'photo' => UploadedFile::fake()->create('malware.svg', 1, 'image/svg+xml'),
        ])->assertSessionHasErrors('photo');
        $this->post('/products/'.$product->slug.'/reviews', [
            ...$this->reviewData(),
            'photo' => UploadedFile::fake()->image('too-large.jpg')->size(5200),
        ])->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('product_reviews', 0);
    }

    public function test_customer_return_requests_are_order_scoped_and_staff_updates_notify_customer(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $order = $this->orderFor($customer, Product::where('slug', 'city-low')->firstOrFail());
        $item = $order->items()->firstOrFail();

        $this->actingAs($customer)->post('/orders/'.$order->reference.'/returns', [
            'order_item_id' => $item->id,
            'action' => 'exchange',
            'reason' => 'wrong_size',
            'details' => 'Please advise on the next size up.',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('order_return_requests', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'user_id' => $customer->id,
            'status' => 'requested',
        ]);
        $return = $order->returnRequests()->firstOrFail();
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id]);
        $this->actingAs($admin)->get('/admin?tab=Returns')->assertInertia(fn (Assert $page) => $page
            ->where('returnRequests.data.0.status', 'requested')
            ->where('returnRequests.data.0.order.reference', $order->reference));

        $this->actingAs($admin)->patch('/admin/returns/'.$return->id, [
            'status' => 'approved',
            'staff_note' => 'We can exchange this pair. Please keep the original box.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('approved', $return->fresh()->status);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $customer->id]);
        $this->actingAs($admin)->patch('/admin/returns/'.$return->id, ['status' => 'completed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('completed', $return->fresh()->status);

        $this->actingAs($customer)->get('/orders/track/'.$order->reference)->assertInertia(fn (Assert $page) => $page
            ->where('returnRequests.0.status', 'completed')
            ->where('returnRequests.0.staff_note', 'We can exchange this pair. Please keep the original box.'));
    }

    public function test_return_requests_require_an_eligible_owned_delivered_order_and_prevent_duplicates(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $product = Product::where('slug', 'city-low')->firstOrFail();
        $order = $this->orderFor($owner, $product);
        $item = $order->items()->firstOrFail();
        $payload = ['order_item_id' => $item->id, 'action' => 'refund', 'reason' => 'changed_mind'];

        $this->actingAs($other)->post('/orders/'.$order->reference.'/returns', $payload)->assertNotFound();
        $this->actingAs($owner)->post('/orders/'.$order->reference.'/returns', [...$payload, 'order_item_id' => 99999])->assertSessionHasErrors('order_item_id');
        $this->post('/orders/'.$order->reference.'/returns', $payload)->assertSessionHasNoErrors();
        $this->post('/orders/'.$order->reference.'/returns', $payload)->assertSessionHasErrors('order_item_id');

        $expired = $this->orderFor($owner, $product, ['delivered_at' => now()->subDays(31)]);
        $expiredItem = $expired->items()->firstOrFail();
        $this->post('/orders/'.$expired->reference.'/returns', [...$payload, 'order_item_id' => $expiredItem->id])->assertSessionHasErrors('return');

        $unpaid = $this->orderFor($owner, $product, ['payment_status' => 'unpaid']);
        $unpaidItem = $unpaid->items()->firstOrFail();
        $this->post('/orders/'.$unpaid->reference.'/returns', [...$payload, 'order_item_id' => $unpaidItem->id])->assertSessionHasErrors('return');
    }
}
