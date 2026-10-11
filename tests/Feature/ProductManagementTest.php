<?php

namespace Tests\Feature;

use App\Console\Commands\ServeStore;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function staff(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_admin' => true])->save();
        $this->actingAs($user);
    }

    private function data(): array
    {
        return ['name' => 'New Shoe', 'category' => 'Everyday', 'description' => 'A new comfortable shoe.', 'retail_price' => 950000, 'wholesale_price' => 700000, 'color' => 'black', 'sizes' => ['40', '41'], 'stock' => 10];
    }

    public function test_staff_can_create_products_with_unique_variant_inventory(): void
    {
        $this->staff();
        $this->post('/admin/products', $this->data())->assertRedirect();
        $product = Product::where('name', 'New Shoe')->firstOrFail();
        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(20, (int) $product->variants()->sum('stock'));
        $this->assertSame(950000, $product->retail_price);
        $this->get('/products/'.$product->slug)->assertInertia(fn (Assert $p) => $p->where('product.name', 'New Shoe')->missing('product.wholesale_price'));
    }

    public function test_duplicate_sizes_are_rejected_without_partial_product_creation(): void
    {
        $this->staff();
        $this->post('/admin/products', [...$this->data(), 'sizes' => ['40', '40']])->assertSessionHasErrors('sizes.0');
        $this->assertSame(6, Product::count());
    }

    public function test_customer_cannot_create_products_or_upload_images(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post('/admin/products', $this->data())->assertForbidden();
        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->image('shoe.png')])->assertForbidden();
    }

    public function test_inventory_csv_is_staff_only_and_safe_to_open_in_spreadsheets(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin/inventory/export')->assertForbidden();

        $this->staff();
        Product::findOrFail(1)->update(['name' => '=HYPERLINK("https://example.com")']);
        $response = $this->get('/admin/inventory/export')->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('eve-shoes-inventory-', $response->headers->get('content-disposition'));

        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $rows = array_map('str_getcsv', explode("\n", trim(substr($csv, 3))));
        $this->assertSame(['SKU', 'Product', 'Category', 'Color', 'EU size', 'Stock', 'Retail price', 'Wholesale price', 'Active'], $rows[0]);
        $this->assertSame("'=HYPERLINK(\"https://example.com\")", $rows[1][1]);
        $this->assertSame('20', $rows[1][5]);
    }

    public function test_staff_can_import_valid_inventory_updates_and_keep_audit_history(): void
    {
        $this->staff();
        $variants = Product::firstOrFail()->variants()->take(2)->get();
        $csv = "\xEF\xBB\xBFSKU,Product,Category,Color,EU size,Stock,Retail price,Wholesale price,Active\n";
        $csv .= $variants[0]->sku.',Daily Knit,Everyday,cream,40,7,7900,5900,yes'."\n";
        $csv .= $variants[1]->sku.',Daily Knit,Everyday,cream,41,9,7900,5900,yes'."\n";

        $this->post('/admin/inventory/import', [
            'inventory_file' => UploadedFile::fake()->createWithContent('stock.csv', $csv),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(7, (int) $variants[0]->fresh()->stock);
        $this->assertSame(9, (int) $variants[1]->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', ['variant_id' => $variants[0]->id, 'quantity' => -13, 'reason' => 'staff_adjustment']);
        $this->assertDatabaseHas('inventory_movements', ['variant_id' => $variants[1]->id, 'quantity' => -11, 'reason' => 'staff_adjustment']);
    }

    public function test_invalid_inventory_import_is_rejected_without_partial_stock_changes(): void
    {
        $this->staff();
        $variant = Product::firstOrFail()->variants()->firstOrFail();
        $csv = "SKU,Product,Category,Color,EU size,Stock,Retail price,Wholesale price,Active\n";
        $csv .= $variant->sku.',Daily Knit,Everyday,cream,40,2,7900,5900,yes'."\n";
        $csv .= "UNKNOWN-SKU,Daily Knit,Everyday,cream,41,9,7900,5900,yes\n";

        $this->post('/admin/inventory/import', [
            'inventory_file' => UploadedFile::fake()->createWithContent('stock.csv', $csv),
        ])->assertSessionHasErrors('inventory_file');

        $this->assertSame(20, (int) $variant->fresh()->stock);
        $this->assertDatabaseMissing('inventory_movements', ['variant_id' => $variant->id, 'reason' => 'staff_adjustment']);
    }

    public function test_inventory_import_rejects_customer_access_and_bad_csv_headers(): void
    {
        $file = UploadedFile::fake()->createWithContent('stock.csv', "Product,Stock\nDaily Knit,10\n");
        $this->actingAs(User::factory()->create())->post('/admin/inventory/import', ['inventory_file' => $file])->assertForbidden();

        $this->staff();
        $this->post('/admin/inventory/import', [
            'inventory_file' => UploadedFile::fake()->createWithContent('stock.csv', "Product,Stock\nDaily Knit,10\n"),
        ])->assertSessionHasErrors('inventory_file');
    }

    public function test_photos_are_stored_using_generated_paths(): void
    {
        Storage::fake('public');
        $this->staff();
        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->image('shoe.png')])->assertRedirect();
        $image = Product::findOrFail(1)->image;
        $this->assertStringStartsWith('/storage/products/', $image);
        Storage::disk('public')->assertExists(substr($image, strlen('/storage/')));
    }

    public function test_scriptable_and_oversized_uploads_are_rejected(): void
    {
        Storage::fake('public');
        $this->staff();
        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->create('unsafe.svg', 1, 'image/svg+xml')])->assertSessionHasErrors('image');
        $this->post('/admin/products/1/image', ['image' => UploadedFile::fake()->image('large.png')->size(6000)])->assertSessionHasErrors('image');
        $this->assertSame('/images/shoe-cream.svg', Product::findOrFail(1)->image);
    }

    public function test_unlisted_products_cannot_be_viewed_or_added_to_the_bag(): void
    {
        $product = Product::first();
        $product->update(['active' => false]);
        $this->get('/products/'.$product->slug)->assertNotFound();
        $this->post('/cart', ['variant_id' => $product->variants()->first()->id, 'quantity' => 1])->assertNotFound();
    }

    public function test_staff_can_edit_customer_description_and_category_without_changing_the_product_url(): void
    {
        $this->staff();
        $product = Product::first();
        $slug = $product->slug;
        $this->patch('/admin/products/'.$product->id, ['name' => 'Updated shoe', 'retail_price' => 950000, 'wholesale_price' => 700000, 'active' => true, 'description' => "Soft leather upper.\nEasy everyday fit.", 'category' => 'Outdoor'])->assertRedirect();
        $this->assertSame($slug, $product->fresh()->slug);
        $this->get('/products/'.$slug)->assertInertia(fn (Assert $p) => $p->where('product.description', "Soft leather upper.\nEasy everyday fit.")->where('product.category', 'Outdoor'));
        $this->patch('/admin/products/'.$product->id, ['name' => 'Updated shoe', 'retail_price' => 950000, 'wholesale_price' => 700000, 'active' => true, 'description' => ''])->assertSessionHasErrors('description');
    }

    public function test_development_server_allows_the_documented_photo_upload_size(): void
    {
        $command = Artisan::all()['serve'];
        $this->assertInstanceOf(ServeStore::class, $command);
        $input = new ArrayInput(['--host' => '127.0.0.1', '--port' => '8000'], $command->getDefinition());
        $property = new \ReflectionProperty(Command::class, 'input');
        $property->setValue($command, $input);
        $method = new \ReflectionMethod($command, 'serverCommand');
        $arguments = $method->invoke($command);
        $this->assertContains('upload_max_filesize=8M', $arguments);
        $this->assertContains('post_max_size=10M', $arguments);
        $this->assertContains('-S', $arguments);
    }
}
