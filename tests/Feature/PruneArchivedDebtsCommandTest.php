<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\DebtItem;
use App\Models\Payment;
use App\Models\Product;
use App\Services\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PruneArchivedDebtsCommandTest extends TestCase
{
    use RefreshDatabase;

    private CustomerAccountService $service;

    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CustomerAccountService::class);

        $roleId = (string) Str::uuid();
        $this->userId = (string) Str::uuid();

        DB::table('roles')->insert([
            'role_id' => $roleId,
            'role_name' => 'store_owner',
            'description' => 'Store owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'user_id' => $this->userId,
            'role_id' => $roleId,
            'full_name' => 'Store Owner',
            'username' => 'prune.owner',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_paid_debts_past_retention_are_pruned_with_payments_and_items(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');
        $this->service->recordPayment($debt, '100.00', $this->userId);

        $this->assertSame('paid', $debt->refresh()->status->value);
        $this->assertNotNull($debt->paid_at);

        $paymentId = Payment::where('debt_id', $debt->debt_id)->value('payment_id');
        $itemId = DebtItem::where('debt_id', $debt->debt_id)->value('debt_item_id');

        $this->travelTo(now()->addDays(Debt::ARCHIVE_RETENTION_DAYS + 1));

        $this->artisan('debts:prune-archived')->assertSuccessful();

        $this->assertDatabaseMissing('debts', ['debt_id' => $debt->debt_id]);
        $this->assertDatabaseMissing('payments', ['payment_id' => $paymentId]);
        $this->assertDatabaseMissing('debt_items', ['debt_item_id' => $itemId]);
    }

    public function test_manually_marked_debts_past_retention_are_pruned(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');
        $this->service->markAsPaid($debt, $this->userId);

        $this->travelTo(now()->addDays(Debt::ARCHIVE_RETENTION_DAYS + 1));

        $this->artisan('debts:prune-archived')->assertSuccessful();

        $this->assertDatabaseMissing('debts', ['debt_id' => $debt->debt_id]);
    }

    public function test_recently_paid_debts_remain_in_the_archive(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');
        $this->service->recordPayment($debt, '100.00', $this->userId);

        $this->travelTo(now()->addDays(Debt::ARCHIVE_RETENTION_DAYS - 1));

        $this->artisan('debts:prune-archived')->assertSuccessful();

        $this->assertDatabaseHas('debts', ['debt_id' => $debt->debt_id]);
    }

    public function test_open_debts_are_never_pruned(): void
    {
        $customer = $this->customer();
        $debt = $this->debt($customer);
        $this->item($debt, '100.00');

        $this->travelTo(now()->addDays(Debt::ARCHIVE_RETENTION_DAYS + 30));

        $this->artisan('debts:prune-archived')->assertSuccessful();

        $this->assertDatabaseHas('debts', ['debt_id' => $debt->debt_id]);
    }

    private function customer(): Customer
    {
        return Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => Str::random(5),
            'full_name' => 'Renesme Moral',
        ]);
    }

    private function debt(Customer $customer): Debt
    {
        return Debt::create([
            'debt_id' => (string) Str::uuid(),
            'customer_id' => $customer->customer_id,
            'created_by' => $this->userId,
        ]);
    }

    private function item(Debt $debt, string $price): DebtItem
    {
        $product = Product::create([
            'product_id' => (string) Str::uuid(),
            'product_name' => 'Goods '.Str::random(4),
            'description' => null,
            'price' => $price,
            'created_by' => $this->userId,
        ]);

        return DebtItem::create([
            'debt_item_id' => (string) Str::uuid(),
            'debt_id' => $debt->debt_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => $price,
            'subtotal' => $price,
        ]);
    }
}
