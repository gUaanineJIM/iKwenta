<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
                'notes' => 'Handle with care',
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
            'notes' => 'Handle with care',
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

    public function test_fully_paid_customers_move_to_history_after_thirty_days_without_deleting_records(): void
    {
        $this->travelTo(Carbon::parse('2026-10-02 12:00:00'));

        $recentCustomer = Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => '40001',
            'full_name' => 'Recently Paid',
            'gender' => 'male',
        ]);
        $recentPaidAt = now()->subDays(30);
        $recentDebt = Debt::create([
            'debt_id' => (string) Str::uuid(),
            'customer_id' => $recentCustomer->customer_id,
            'created_by' => $this->userId,
            'status' => 'paid',
            'money_amount' => '100.00',
            'loaned_at' => now()->subDays(40),
            'paid_at' => $recentPaidAt,
        ]);
        Payment::create([
            'payment_id' => (string) Str::uuid(),
            'customer_id' => $recentCustomer->customer_id,
            'debt_id' => $recentDebt->debt_id,
            'amount_paid' => '100.00',
            'payment_date' => $recentPaidAt,
            'received_by' => $this->userId,
        ]);

        $archivedCustomer = Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => '40002',
            'full_name' => 'Archived Customer',
            'gender' => 'female',
        ]);
        $archivedPaidAt = now()->subDays(31);
        $archivedDebt = Debt::create([
            'debt_id' => (string) Str::uuid(),
            'customer_id' => $archivedCustomer->customer_id,
            'created_by' => $this->userId,
            'status' => 'paid',
            'money_amount' => '200.00',
            'loaned_at' => now()->subDays(40),
            'paid_at' => $archivedPaidAt,
        ]);
        Payment::create([
            'payment_id' => (string) Str::uuid(),
            'customer_id' => $archivedCustomer->customer_id,
            'debt_id' => $archivedDebt->debt_id,
            'amount_paid' => '200.00',
            'payment_date' => $archivedPaidAt,
            'received_by' => $this->userId,
        ]);

        $stillOwes = Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => '40003',
            'full_name' => 'Still Owes',
            'gender' => 'male',
        ]);
        $oldPaidDebt = Debt::create([
            'debt_id' => (string) Str::uuid(),
            'customer_id' => $stillOwes->customer_id,
            'created_by' => $this->userId,
            'status' => 'paid',
            'money_amount' => '50.00',
            'loaned_at' => now()->subDays(40),
            'paid_at' => $archivedPaidAt,
        ]);
        Payment::create([
            'payment_id' => (string) Str::uuid(),
            'customer_id' => $stillOwes->customer_id,
            'debt_id' => $oldPaidDebt->debt_id,
            'amount_paid' => '50.00',
            'payment_date' => $archivedPaidAt,
            'received_by' => $this->userId,
        ]);
        $openDebt = Debt::create([
            'debt_id' => (string) Str::uuid(),
            'customer_id' => $stillOwes->customer_id,
            'created_by' => $this->userId,
            'status' => 'unpaid',
            'money_amount' => '25.00',
            'loaned_at' => now()->subDay(),
        ]);

        $response = $this->get('/owner/customers');
        $customerGroups = $response->viewData('customerGroups');

        $response->assertOk();
        $response->assertSee('Active')
            ->assertSee('Paid History')
            ->assertSee('Paid off Sep 01, 2026 12:00 PM')
            ->assertSee('data-customer-view-toggle="history"', false);
        $this->assertSame(['Recently Paid'], $customerGroups['paid']->pluck('full_name')->all());
        $this->assertSame(['Archived Customer'], $customerGroups['archived']->pluck('full_name')->all());
        $this->assertSame(['Still Owes'], $customerGroups['owing']->pluck('full_name')->all());
        $this->assertSame('75.00', $customerGroups['owing']->first()->total_debt);
        $this->assertSame('25.00', $customerGroups['owing']->first()->remaining_balance);
        $this->assertDatabaseHas('debts', ['debt_id' => $archivedDebt->debt_id]);
        $this->assertDatabaseHas('debts', ['debt_id' => $openDebt->debt_id]);
        $this->assertSame(1, Payment::where('debt_id', $archivedDebt->debt_id)->count());

        $this->get('/owner/customers/list?q=Archived')
            ->assertOk()
            ->assertSee('Archived Customer')
            ->assertDontSee('Recently Paid');
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

        $customer->debts()->first()->items()->first()->update(['notes' => 'Handle with care']);

        $this->get('/owner/customers')
            ->assertOk()
            ->assertSee('owner-customer-modal', false)
            ->assertSee('owner-detail-page__back', false)
            ->assertSee('Record Payment')
            ->assertSee('Notebook')
            ->assertSee('Money owed: ₱100.00')
            ->assertSee('₱50.00')
            ->assertSee('Sep 21, 2026 09:15 AM')
            ->assertSee('Loaned Sep 21, 2026 09:15 AM')
            ->assertSee('Note: Handle with care');
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

    public function test_customer_profile_accepts_gender_and_generates_a_deterministic_avatar(): void
    {
        $response = $this->post('/owner/customers', [
            'full_name' => 'Profile Customer',
            'gender' => 'female',
            'debt_types' => ['money'],
            'money_amount' => '100.00',
        ]);

        $response->assertCreated();

        $customer = Customer::where('full_name', 'Profile Customer')->firstOrFail();
        $this->assertSame('female', $customer->gender);
        $this->assertNotNull($customer->avatar_path);
        $this->assertMatchesRegularExpression('/^customer-identicons\/[A-Za-z0-9-]+\.svg$/', $customer->avatar_path);
        Storage::disk('public')->assertExists($customer->avatar_path);
    }

    public function test_generating_an_avatar_is_stable_across_updates(): void
    {
        Storage::fake('public');

        $this->postJson('/owner/customers', [
            'full_name' => 'Avatar Swap Customer',
            'debt_types' => ['money'],
            'money_amount' => '10.00',
        ])->assertCreated();

        $customer = Customer::where('full_name', 'Avatar Swap Customer')->firstOrFail();
        $firstAvatar = $customer->avatar_path;

        Storage::disk('public')->assertExists($firstAvatar);

        $this->putJson('/owner/customers/'.$customer->customer_id, [
            'full_name' => $customer->full_name,
        ])->assertOk();

        $secondAvatar = $customer->fresh()->avatar_path;

        $this->assertSame($firstAvatar, $secondAvatar);
        Storage::disk('public')->assertExists($secondAvatar);
    }

    public function test_deleting_a_customer_deletes_their_generated_avatar(): void
    {
        Storage::fake('public');

        // A portal-created customer always has at least one debt, and
        // deletion refuses customers with debt history, so the branch is only
        // reachable for a customer recorded without one.
        $customer = Customer::create([
            'customer_code' => '31415',
            'full_name' => 'Deleted Avatar Customer',
            'gender' => 'female',
            'avatar_path' => 'customer-identicons/31415.svg',
        ]);
        Storage::disk('public')->put($customer->avatar_path, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        Storage::disk('public')->assertExists($customer->avatar_path);

        $this->deleteJson('/owner/customers/'.$customer->customer_id)->assertOk();

        Storage::disk('public')->assertMissing($customer->avatar_path);
    }

    public function test_owner_can_save_and_update_a_customer_description(): void
    {
        $this->postJson('/owner/customers', [
            'full_name' => 'Described Customer',
            'description' => '  Prefers cash payments.  ',
            'debt_types' => ['money'],
            'money_amount' => '75.00',
        ])->assertCreated();

        $customer = Customer::where('full_name', 'Described Customer')->firstOrFail();
        $this->assertSame('Prefers cash payments.', $customer->description);

        $this->get('/owner/customers')
            ->assertOk()
            ->assertSee('owner-customer-detail__note', false)
            ->assertSee('Prefers cash payments.');

        $this->putJson('/owner/customers/'.$customer->customer_id, [
            'full_name' => $customer->full_name,
            'description' => 'Lives near the wet market.',
            'debt_types' => ['money'],
            'money_amount' => '75.00',
        ])->assertOk();

        $customer->refresh();
        $this->assertSame('Lives near the wet market.', $customer->description);

        $updateLog = ActivityLog::where('action', 'customer.update')
            ->where('record_id', $customer->customer_id)
            ->firstOrFail();
        $this->assertSame('Prefers cash payments.', $updateLog->old_values['description']);
        $this->assertSame('Lives near the wet market.', $updateLog->new_values['description']);

        $this->putJson('/owner/customers/'.$customer->customer_id, [
            'full_name' => $customer->full_name,
            'description' => '   ',
        ])->assertOk();

        $this->assertNull($customer->fresh()->description);
    }

    public function test_customer_description_cannot_exceed_five_thousand_characters(): void
    {
        $this->postJson('/owner/customers', [
            'full_name' => 'Too Wordy',
            'description' => str_repeat('a', 5001),
            'debt_types' => ['money'],
            'money_amount' => '10.00',
        ])->assertStatus(422)->assertJsonValidationErrors('description');

        $this->assertSame(0, Customer::where('full_name', 'Too Wordy')->count());
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
