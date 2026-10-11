<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShoppingDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    public function test_guest_can_save_and_remove_shoes_and_list_only_active_products(): void
    {
        $this->post('/saved/1')->assertRedirect()->assertSessionHas('success', 'Shoe saved for later.');
        $this->post('/saved/2')->assertRedirect();
        $this->get('/saved')->assertInertia(fn (Assert $page) => $page->component('shop/Saved')->has('products', 2)
            ->where('products.0.id', 2)->where('products.1.id', 1)->missing('products.0.wholesale_price'));
        Product::findOrFail(2)->update(['active' => false]);
        $this->get('/saved')->assertInertia(fn (Assert $page) => $page->has('products', 1)->where('products.0.id', 1));
        $this->post('/saved/2')->assertNotFound();
        $this->post('/saved/1')->assertSessionHas('success', 'Shoe removed from saved list.');
        $this->get('/saved')->assertInertia(fn (Assert $page) => $page->has('products', 0));
    }

    public function test_guest_saved_shoes_merge_into_account_and_stay_private(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->post('/saved/1');
        $this->post('/saved/2');
        $this->actingAs($owner)->get('/saved')->assertInertia(fn (Assert $page) => $page->has('products', 2));
        $this->assertSame(2, DB::table('saved_products')->where('user_id', $owner->id)->count());
        $this->get('/saved');
        $this->assertSame(2, DB::table('saved_products')->where('user_id', $owner->id)->count());
        $this->actingAs($other)->get('/saved')->assertInertia(fn (Assert $page) => $page->has('products', 0));
        $this->assertSame(0, DB::table('saved_products')->where('user_id', $other->id)->count());
    }

    public function test_recently_viewed_shoes_are_deduplicated_and_can_be_cleared(): void
    {
        $this->get('/products/city-low')->assertInertia(fn (Assert $page) => $page->where('product.id', 1)->has('recentlyViewed', 0));
        $this->get('/products/pace-runner')->assertInertia(fn (Assert $page) => $page->where('recentlyViewed.0.id', 1));
        $this->get('/products/city-low')->assertInertia(fn (Assert $page) => $page->where('recentlyViewed.0.id', 2)->has('recentlyViewed', 1));
        $this->get('/')->assertInertia(fn (Assert $page) => $page->where('recentlyViewed.0.id', 1)->where('recentlyViewed.1.id', 2));
        $this->delete('/recently-viewed')->assertRedirect();
        $this->get('/')->assertInertia(fn (Assert $page) => $page->has('recentlyViewed', 0));
    }

    public function test_nepali_saved_list_and_flash_use_translated_product_content(): void
    {
        $this->withSession(['locale' => 'ne'])->post('/saved/1')->assertSessionHas('success', 'जुत्ता पछि हेर्न सुरक्षित राखियो।');
        $this->get('/saved')->assertInertia(fn (Assert $page) => $page->where('products.0.name', 'सिटी लो'));
    }

    public function test_saved_list_limit_ignores_inactive_shoes_and_rejects_a_fifty_first_active_shoe(): void
    {
        $ids = [];
        for ($index = 0; $index < 50; $index++) {
            $ids[] = Product::create([
                'name' => 'Test shoe '.$index, 'slug' => 'test-shoe-'.$index,
                'category' => 'Everyday', 'description' => 'A test shoe for saved-list capacity.',
                'image' => '/images/shoe-cream.svg', 'retail_price' => 1000,
                'wholesale_price' => 800, 'active' => true,
            ])->id;
        }
        $this->withSession(['saved_products' => $ids])->post('/saved/1')->assertSessionHasErrors('saved');
        Product::findOrFail($ids[0])->update(['active' => false]);
        $this->post('/saved/1')->assertSessionHasNoErrors();
        $this->get('/saved')->assertInertia(fn (Assert $page) => $page->has('products', 50));
    }
}
