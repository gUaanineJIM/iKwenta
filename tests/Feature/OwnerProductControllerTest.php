<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\DebtItem;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $userId;

    private string $ownerRoleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->ownerRoleId = (string) Str::uuid();
        $this->userId = (string) Str::uuid();

        DB::table('roles')->insert([
            'role_id' => $this->ownerRoleId,
            'role_name' => 'store_owner',
            'description' => 'Store owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'user_id' => $this->userId,
            'role_id' => $this->ownerRoleId,
            'full_name' => 'Store Owner',
            'username' => 'store.owner',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['owner_id' => $this->userId]);
    }

    public function test_store_creates_multiple_products_and_price_history_in_one_request(): void
    {
        $response = $this->postJson('/owner/products', [
            'products' => [
                [
                    'product_name' => 'Test Bulk Product A',
                    'description' => 'First bulk product',
                    'price' => 123.45,
                ],
                [
                    'product_name' => 'Test Bulk Product B',
                    'price' => 9.99,
                ],
            ],
        ]);

        $response->assertCreated();

        $products = $response->json('products');
        $this->assertCount(2, $products);
        $this->assertSame('Test Bulk Product A', $products[0]['product_name']);
        $this->assertSame('Test Bulk Product B', $products[1]['product_name']);

        $this->assertSame(2, Product::count());

        $this->assertDatabaseHas('products', [
            'product_name' => 'Test Bulk Product A',
            'price' => '123.45',
            'created_by' => $this->userId,
        ]);

        $this->assertDatabaseHas('products', [
            'product_name' => 'Test Bulk Product B',
            'price' => '9.99',
            'created_by' => $this->userId,
        ]);

        foreach ($products as $product) {
            $this->assertDatabaseHas('product_price_history', [
                'product_id' => $product['product_id'],
                'price' => $product['price'],
                'changed_by' => $this->userId,
            ]);
        }
    }

    public function test_store_rejects_missing_name_and_zero_price(): void
    {
        $response = $this->postJson('/owner/products', [
            'products' => [
                [
                    'product_name' => '',
                    'price' => 0,
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'products.0.product_name',
                'products.0.price',
            ]);

        $this->assertSame(0, Product::count());
    }

    public function test_store_rejects_empty_payload(): void
    {
        $response = $this->postJson('/owner/products', [
            'products' => [],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('products');

        $this->assertSame(0, Product::count());
    }

    public function test_store_uses_seeded_store_owner_not_request_body(): void
    {
        $response = $this->postJson('/owner/products', [
            'created_by' => 'spoofed-user-id',
            'products' => [
                [
                    'product_name' => 'Spoof Test Product',
                    'price' => 50,
                ],
            ],
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('products', [
            'product_name' => 'Spoof Test Product',
            'created_by' => $this->userId,
        ]);

        $this->assertDatabaseMissing('products', [
            'product_name' => 'Spoof Test Product',
            'created_by' => 'spoofed-user-id',
        ]);
    }

    public function test_list_filters_products_by_search_term(): void
    {
        $this->postJson('/owner/products', [
            'products' => [
                ['product_name' => 'Special Blend Coffee', 'description' => 'Single origin beans', 'price' => 88],
                ['product_name' => 'Plain Notebook', 'price' => 45],
            ],
        ])->assertCreated();

        $matchesList = $this->get('/owner/products/list?q=coffee');
        $matchesList->assertOk()
            ->assertSee('Special Blend Coffee')
            ->assertDontSee('Plain Notebook');

        $descriptionMatches = $this->get('/owner/products/list?q=Single origin');
        $descriptionMatches->assertOk()
            ->assertSee('Special Blend Coffee');

        $noMatch = $this->get('/owner/products/list?q=zzz-no-such-product');
        $noMatch->assertOk()
            ->assertSee('No matches');

        $index = $this->get('/owner/products?q=Notebook');
        $index->assertOk()
            ->assertSee('Plain Notebook')
            ->assertDontSee('Special Blend Coffee');
    }

    public function test_list_fragment_renders_and_index_shows_products(): void
    {
        $response = $this->postJson('/owner/products', [
            'products' => [
                ['product_name' => 'Fragment Product', 'price' => 25],
            ],
        ]);

        $response->assertCreated();

        $list = $this->get('/owner/products/list');
        $list->assertOk()
            ->assertSee('Fragment Product');

        $index = $this->get('/owner/products');
        $index->assertOk()
            ->assertSee('Fragment Product');
    }

    public function test_update_records_new_price_history_and_activity_log(): void
    {
        $created = $this->postJson('/owner/products', [
            'products' => [
                ['product_name' => 'Editable Product', 'price' => 100],
            ],
        ])->json('products.0');

        $productId = $created['product_id'];

        $response = $this->putJson('/owner/products/'.$productId, [
            'product_name' => 'Editable Product Renamed',
            'description' => 'Updated description',
            'price' => 150,
        ]);

        $response->assertOk()
            ->assertJsonPath('product.product_name', 'Editable Product Renamed')
            ->assertJsonPath('product.price', '150.00');

        $this->assertDatabaseHas('products', [
            'product_id' => $productId,
            'product_name' => 'Editable Product Renamed',
            'price' => '150.00',
        ]);

        $history = ProductPriceHistory::query()
            ->where('product_id', $productId)
            ->orderBy('effective_from')
            ->get();

        $this->assertCount(2, $history);
        $this->assertNotNull($history[0]->effective_to);
        $this->assertNull($history[1]->effective_to);
        $this->assertSame('150.00', $history[1]->price);

        $this->assertDatabaseHas('activity_logs', [
            'table_name' => 'products',
            'record_id' => $productId,
            'action' => 'update',
            'user_id' => $this->userId,
        ]);

        $log = ActivityLog::query()
            ->where('table_name', 'products')
            ->where('record_id', $productId)
            ->where('action', 'update')
            ->first();

        $this->assertSame('100.00', $log->old_values['price']);
        $this->assertSame('150.00', $log->new_values['price']);
    }

    public function test_update_without_price_change_keeps_single_history_row(): void
    {
        $created = $this->postJson('/owner/products', [
            'products' => [
                ['product_name' => 'Stable Product', 'price' => 80],
            ],
        ])->json('products.0');

        $this->putJson('/owner/products/'.$created['product_id'], [
            'product_name' => 'Stable Product Updated',
            'description' => null,
            'price' => 80,
        ])->assertOk();

        $historyCount = ProductPriceHistory::query()
            ->where('product_id', $created['product_id'])
            ->count();

        $this->assertSame(1, $historyCount);
    }

    public function test_delete_removes_unused_product_and_logs_activity(): void
    {
        $created = $this->postJson('/owner/products', [
            'products' => [
                ['product_name' => 'Deletable Product', 'price' => 60],
            ],
        ])->json('products.0');

        $productId = $created['product_id'];

        $response = $this->deleteJson('/owner/products/'.$productId);

        $response->assertOk();

        $this->assertDatabaseMissing('products', [
            'product_id' => $productId,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'table_name' => 'products',
            'record_id' => $productId,
            'action' => 'delete',
            'user_id' => $this->userId,
        ]);
    }

    public function test_delete_is_blocked_when_product_is_used_on_debt_item(): void
    {
        $created = $this->postJson('/owner/products', [
            'products' => [
                ['product_name' => 'Protected Product', 'price' => 120],
            ],
        ])->json('products.0');

        $productId = $created['product_id'];

        $customerId = (string) Str::uuid();
        $debtId = (string) Str::uuid();

        Customer::create([
            'customer_id' => $customerId,
            'customer_code' => '99999',
            'full_name' => 'Test Customer',
        ]);

        Debt::create([
            'debt_id' => $debtId,
            'customer_id' => $customerId,
            'created_by' => $this->userId,
            'status' => 'unpaid',
        ]);

        DebtItem::create([
            'debt_item_id' => (string) Str::uuid(),
            'debt_id' => $debtId,
            'product_id' => $productId,
            'quantity' => 1,
            'unit_price' => 120,
            'subtotal' => 120,
        ]);

        $response = $this->deleteJson('/owner/products/'.$productId);

        $response->assertStatus(409)
            ->assertJsonPath('message', 'This product is used in a debt item and cannot be deleted.');

        $this->assertDatabaseHas('products', [
            'product_id' => $productId,
        ]);

        $this->assertDatabaseMissing('activity_logs', [
            'table_name' => 'products',
            'record_id' => $productId,
            'action' => 'delete',
        ]);
    }
}
