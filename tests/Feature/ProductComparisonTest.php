<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WholesaleAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    public function test_guest_can_compare_products_and_the_list_is_shared_across_store_pages(): void
    {
        $this->post('/compare/1')->assertSessionHas('success', 'Shoe added to comparison.');
        $this->post('/compare/2');
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('compareIds', [2, 1]));
        $this->get('/compare')->assertInertia(fn (Assert $page) => $page->component('shop/Compare')
            ->has('products', 2)->where('products.0.id', 2)->where('products.1.id', 1)
            ->where('products.1.price', 8900)->missing('products.1.wholesale_price'));
        $this->get('/products/city-low')->assertInertia(fn (Assert $page) => $page->where('compareIds', [2, 1]));
    }

    public function test_only_three_active_shoes_can_be_compared_and_removal_frees_a_slot(): void
    {
        $this->post('/compare/1');
        $this->post('/compare/2');
        $this->post('/compare/3');
        $this->post('/compare/4')->assertSessionHasErrors('compare');
        $this->assertSame([3, 2, 1], session('compare_products'));
        $this->post('/compare/2')->assertSessionHas('success', 'Shoe removed from comparison.');
        $this->post('/compare/4')->assertSessionHasNoErrors();
        $this->get('/compare')->assertInertia(fn (Assert $page) => $page->has('products', 3));
        $this->delete('/compare')->assertSessionHas('success', 'Comparison list cleared.');
        $this->get('/compare')->assertInertia(fn (Assert $page) => $page->has('products', 0));
    }

    public function test_inactive_shoes_are_removed_and_approved_businesses_compare_partner_prices(): void
    {
        $this->withSession(['compare_products' => [1, 2]])->post('/compare/3');
        Product::findOrFail(2)->update(['active' => false]);
        $this->get('/compare')->assertInertia(fn (Assert $page) => $page->has('products', 2)->where('products.0.id', 3)->where('products.1.id', 1));

        $user = User::factory()->create();
        WholesaleAccount::create(['user_id' => $user->id, 'company' => 'Shoe Shop', 'phone' => '9800000001', 'status' => 'approved']);
        $this->actingAs($user)->withSession(['compare_products' => [1]])->get('/compare')
            ->assertInertia(fn (Assert $page) => $page->where('wholesale', true)->where('products.0.price', 5900));
    }

    public function test_comparison_is_translated_in_nepali(): void
    {
        $this->withSession(['locale' => 'ne'])->post('/compare/1')
            ->assertSessionHas('success', 'जुत्ता तुलना सूचीमा थपियो।');
        $this->get('/compare')->assertInertia(fn (Assert $page) => $page->where('products.0.name', 'सिटी लो'));
    }
}
