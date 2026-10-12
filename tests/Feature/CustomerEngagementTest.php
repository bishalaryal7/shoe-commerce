<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\ProductReview;
use App\Models\SupportConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerEngagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function reviewFor(User $user, Product $product): ProductReview
    {
        $variant = $product->variants()->firstOrFail();
        $order = Order::create([
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
        ]);
        $order->items()->create([
            'variant_id' => $variant->id,
            'name' => $product->name,
            'sku' => $variant->sku,
            'size' => $variant->size,
            'color' => $variant->color,
            'quantity' => 1,
            'unit_price' => 8900,
        ]);

        return ProductReview::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 4,
            'fit' => 'true',
            'title' => 'Comfortable everyday shoes',
            'body' => 'Comfortable for long walks and the size fits as expected.',
        ]);
    }

    public function test_customers_can_comment_and_review_owner_gets_a_private_notification(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $product = Product::where('slug', 'city-low')->firstOrFail();
        $review = $this->reviewFor($author, $product);

        $this->actingAs($commenter)->post('/products/'.$product->slug.'/reviews/'.$review->id.'/comments', [
            'body' => 'Does this shoe feel comfortable after a full day at work?',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_review_comments', [
            'product_review_id' => $review->id,
            'user_id' => $commenter->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $author->id,
            'type' => 'App\\Notifications\\StoreUpdate',
        ]);
        $this->actingAs($author)->get('/products/'.$product->slug)
            ->assertInertia(fn ($page) => $page
                ->where('reviews.data.0.comments.0.body', 'Does this shoe feel comfortable after a full day at work?')
                ->where('reviews.data.0.comments.0.is_staff', false));
    }

    public function test_shop_reply_is_public_and_notifies_only_the_review_owner(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $product = Product::where('slug', 'city-low')->firstOrFail();
        $review = $this->reviewFor($buyer, $product);

        $this->actingAs($admin)->patch('/admin/reviews/'.$review->id, [
            'reply' => 'Thanks for sharing this feedback with us.',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_reviews', [
            'id' => $review->id,
            'seller_reply' => 'Thanks for sharing this feedback with us.',
            'seller_replied_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $buyer->id,
            'type' => 'App\\Notifications\\StoreUpdate',
        ]);
        $this->get('/products/'.$product->slug)->assertInertia(fn ($page) => $page
            ->where('reviews.data.0.seller_reply', 'Thanks for sharing this feedback with us.'));

        $customer = User::factory()->create();
        $this->actingAs($customer)->patch('/admin/reviews/'.$review->id, ['reply' => 'Fake shop reply'])->assertForbidden();
    }

    public function test_customers_can_message_the_shop_and_receive_staff_replies(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($customer)->post('/messages', [
            'subject' => 'Help with my order',
            'body' => 'Could you tell me when my order will ship?',
        ])->assertRedirect();
        $conversation = SupportConversation::firstOrFail();
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'type' => 'App\\Notifications\\StoreUpdate',
        ]);

        $this->actingAs($admin)->post('/admin/messages/'.$conversation->id, [
            'body' => 'Your order is being prepared and we will update tracking soon.',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $customer->id,
            'type' => 'App\\Notifications\\StoreUpdate',
        ]);
        $this->actingAs($customer)->get('/messages?conversation='.$conversation->id)->assertInertia(fn ($page) => $page
            ->where('activeConversation.messages.1.is_staff', true)
            ->where('activeConversation.status', 'answered'));

        $this->actingAs($admin)->patch('/admin/messages/'.$conversation->id.'/status', ['status' => 'closed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('closed', $conversation->fresh()->status);

        $other = User::factory()->create();
        $this->actingAs($other)->get('/messages?conversation='.$conversation->id)->assertNotFound();
        $this->actingAs($other)->post('/messages/'.$conversation->id, ['body' => 'Trying to access someone else’s support thread.'])->assertNotFound();
    }

    public function test_notification_read_actions_are_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $review = $this->reviewFor($owner, Product::where('slug', 'city-low')->firstOrFail());
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->patch('/admin/reviews/'.$review->id, ['reply' => 'Thank you for your review.']);
        $notification = $owner->fresh()->notifications()->firstOrFail();

        $this->actingAs($other)->post('/notifications/'.$notification->id.'/read')->assertNotFound();
        $this->actingAs($owner)->post('/notifications/'.$notification->id.'/read')->assertRedirect('/products/city-low#reviews');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_product_question_answers_notify_the_customer(): void
    {
        $customer = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $product = Product::where('slug', 'city-low')->firstOrFail();
        $question = ProductQuestion::create([
            'product_id' => $product->id,
            'user_id' => $customer->id,
            'question' => 'Does this shoe have a removable insole?',
        ]);

        $this->actingAs($admin)->patch('/admin/questions/'.$question->id, [
            'answer' => 'Yes, the insole can be removed for cleaning.',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $customer->id,
            'type' => 'App\\Notifications\\StoreUpdate',
        ]);
    }
}
