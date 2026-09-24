<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerCustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
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
            'username' => 'store.owner',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['owner_id' => $this->userId]);
    }

    public function test_store_creates_customer_with_product_and_money_debt(): void
    {
        $response = $this->postJson('/owner/customers', [
            'full_name' => 'Maria Santos',
            'debt_types' => ['product', 'money'],
            'products' => [[
                'product_name' => 'Rice',
                'quantity' => 2,
                'amount' => '125.50',
            ]],
            'money_amount' => '300.25',
            'loaned_at' => '2026-09-20T14:30',
        ]);

        $response->assertCreated()->assertJsonPath('customer.full_name', 'Maria Santos');
        $code = $response->json('customer.customer_code');
        $this->assertMatchesRegularExpression('/^\d{5}$/', $code);

        $customer = Customer::where('customer_code', $code)->firstOrFail();
        $debt = $customer->debts()->firstOrFail();
        $this->assertSame('300.25', $debt->money_amount);
        $this->assertSame('2026-09-20 14:30', $debt->loaned_at->format('Y-m-d H:i'));
        $this->assertDatabaseHas('debt_items', [
            'debt_id' => $debt->debt_id,
            'product_name' => 'Rice',
            'quantity' => 2,
            'subtotal' => '251.00',
        ]);
    }

    public function test_owner_can_record_a_partial_payment_against_the_open_debt(): void
    {
        $this->postJson('/owner/customers', [
            'full_name' => 'Juan Dela Cruz',
            'debt_types' => ['money'],
            'money_amount' => '1000.00',
        ])->assertCreated();

        $customer = Customer::where('full_name', 'Juan Dela Cruz')->firstOrFail();
        $debt = Debt::where('customer_id', $customer->customer_id)->firstOrFail();

        $this->postJson('/owner/customers/'.$customer->customer_id.'/payments', [
            'amount' => '275.55',
        ])->assertOk()->assertJsonPath('remaining_balance', '724.45');

        $this->assertSame(1, Payment::where('debt_id', $debt->debt_id)->count());
        $this->assertSame('partially_paid', $debt->fresh()->status->value);
    }

    public function test_typed_product_name_is_available_to_the_customer_portal(): void
    {
        $response = $this->postJson('/owner/customers', [
            'full_name' => 'Ana Reyes',
            'debt_types' => ['product'],
            'products' => [[
                'product_name' => 'Handmade Basket',
                'quantity' => 1,
                'amount' => '450.00',
            ]],
        ])->assertCreated();

        $customer = Customer::where('full_name', 'Ana Reyes')->firstOrFail();

        $this->withSession(['customer_id' => $customer->customer_id])
            ->get('/customer/dashboard/section/items', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Handmade Basket')
            ->assertDontSee('Unknown Product');
    }

    public function test_customers_are_separated_by_remaining_balance(): void
    {
        $this->postJson('/owner/customers', [
            'full_name' => 'Still Owes',
            'debt_types' => ['money'],
            'money_amount' => '500.00',
        ])->assertCreated();

        $this->postJson('/owner/customers', [
            'full_name' => 'Paid In Full',
            'debt_types' => ['money'],
            'money_amount' => '250.00',
        ])->assertCreated();

        $paidCustomer = Customer::where('full_name', 'Paid In Full')->firstOrFail();
        $this->postJson('/owner/customers/'.$paidCustomer->customer_id.'/payments', [
            'amount' => '250.00',
        ])->assertOk();

        $this->get('/owner/customers')
            ->assertOk()
            ->assertSee('Debt Remaining')
            ->assertSee('Still Owes')
            ->assertSee('Paid Customers')
            ->assertSee('Paid In Full');
    }

    public function test_owner_can_view_customer_debt_items_and_payment_history(): void
    {
        $this->postJson('/owner/customers', [
            'full_name' => 'History Customer',
            'debt_types' => ['product', 'money'],
            'products' => [[
                'product_name' => 'Notebook',
                'quantity' => 2,
                'amount' => '75.00',
            ]],
            'money_amount' => '100.00',
            'loaned_at' => '2026-09-21T09:15',
        ])->assertCreated();

        $customer = Customer::where('full_name', 'History Customer')->firstOrFail();
        $this->postJson('/owner/customers/'.$customer->customer_id.'/payments', [
            'amount' => '50.00',
        ])->assertOk();

        $this->get('/owner/customers')
            ->assertOk()
            ->assertSee('owner-customer-modal', false)
            ->assertSee('Record Payment')
            ->assertSee('Notebook')
            ->assertSee('Money owed: ₱100.00')
            ->assertSee('₱50.00')
            ->assertSee('Sep 21, 2026 09:15 AM');
    }

    public function test_edit_customer_can_append_a_new_product_and_money_debt(): void
    {
        $this->postJson('/owner/customers', [
            'full_name' => 'Repeat Customer',
            'debt_types' => ['money'],
            'money_amount' => '100.00',
        ])->assertCreated();

        $customer = Customer::where('full_name', 'Repeat Customer')->firstOrFail();

        $this->putJson('/owner/customers/'.$customer->customer_id, [
            'full_name' => 'Repeat Customer Updated',
            'debt_types' => ['product', 'money'],
            'products' => [[
                'product_name' => 'School Bag',
                'quantity' => 1,
                'amount' => '850.00',
            ]],
            'money_amount' => '50.25',
            'loaned_at' => '2026-09-22T16:45',
        ])->assertOk();

        $customer->refresh();
        $this->assertSame('Repeat Customer Updated', $customer->full_name);
        $this->assertSame(1, Customer::where('full_name', 'Repeat Customer Updated')->count());
        $this->assertSame(2, $customer->debts()->count());
        $newDebt = $customer->debts()->where('money_amount', '50.25')->firstOrFail();
        $this->assertDatabaseHas('debt_items', [
            'debt_id' => $newDebt->debt_id,
            'product_name' => 'School Bag',
        ]);
        $this->assertSame('2026-09-22 16:45', $newDebt->loaned_at->format('Y-m-d H:i'));
    }

    public function test_customer_profile_accepts_gender_and_optional_avatar(): void
    {
        $response = $this->post('/owner/customers', [
            'full_name' => 'Profile Customer',
            'gender' => 'female',
            'debt_types' => ['money'],
            'money_amount' => '100.00',
            'avatar' => UploadedFile::fake()->image('profile.png'),
        ]);

        $response->assertCreated();

        $customer = Customer::where('full_name', 'Profile Customer')->firstOrFail();
        $this->assertSame('female', $customer->gender);
        $this->assertNotNull($customer->avatar_path);
    }

    public function test_paid_in_full_option_settles_all_open_debts(): void
    {
        $this->postJson('/owner/customers', [
            'full_name' => 'Full Settlement Customer',
            'debt_types' => ['money'],
            'money_amount' => '100.00',
        ])->assertCreated();

        $customer = Customer::where('full_name', 'Full Settlement Customer')->firstOrFail();

        $this->putJson('/owner/customers/'.$customer->customer_id, [
            'full_name' => $customer->full_name,
            'debt_types' => ['money'],
            'money_amount' => '50.00',
        ])->assertOk();

        $this->postJson('/owner/customers/'.$customer->customer_id.'/payments', [
            'amount' => '150.00',
            'pay_in_full' => true,
        ])->assertOk()->assertJsonPath('remaining_balance', '0.00');

        $this->assertSame(2, Payment::where('customer_id', $customer->customer_id)->count());
        $this->assertSame(0, $customer->debts()->where('status', '!=', 'paid')->count());
    }
}
