<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Debt;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerActivityLogControllerTest extends TestCase
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
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_activity_logs_page_renders_log_entries_with_diff_details(): void
    {
        $this->log('customer.create', 'customers', [
            'full_name' => 'Maria Santos',
            'customer_code' => '00123',
        ]);

        $this->log('debt.mark_paid', 'debts', [
            'status' => 'paid',
            'payment_id' => Str::uuid(),
        ]);

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/activity-logs');

        $response->assertOk()
            ->assertSee('Activity Logs')
            ->assertSee('Store Owner')
            ->assertSee('Customer created')
            ->assertSee('Maria Santos')
            ->assertSee('Debt marked as paid')
            ->assertSee('fields');
    }

    public function test_activity_logs_requires_owner_session(): void
    {
        $this->get('/owner/activity-logs')
            ->assertRedirect(route('landing', ['#login']));
    }

    public function test_activity_logs_list_requires_owner_session_for_ajax(): void
    {
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/owner/activity-logs/list')
            ->assertUnauthorized();
    }

    public function test_activity_logs_list_requires_owner_session_for_plain_request(): void
    {
        $this->get('/owner/activity-logs/list')
            ->assertRedirect(route('landing', ['#login']));
    }

    public function test_activity_logs_list_filters_by_search_query(): void
    {
        $this->log('customer.create', 'customers', [
            'full_name' => 'Maria Santos',
        ]);

        $this->log('payment.create', 'payments', [
            'customer_name' => 'Juan Dela Cruz',
            'amount_paid' => '275.55',
        ]);

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/activity-logs/list?q=Maria');

        $response->assertOk()
            ->assertSee('Maria Santos')
            ->assertDontSee('Juan Dela Cruz');
    }

    public function test_activity_logs_list_filters_by_record_type(): void
    {
        $this->log('customer.create', 'customers', [
            'full_name' => 'Maria Santos',
        ]);

        $this->log('payment.create', 'payments', [
            'customer_name' => 'Juan Dela Cruz',
            'amount_paid' => '275.55',
        ]);

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/activity-logs/list?filter=payments');

        $response->assertOk()
            ->assertSee('Payment recorded')
            ->assertSee('Juan Dela Cruz')
            ->assertDontSee('Maria Santos');
    }

    public function test_activity_logs_list_shows_empty_state_for_no_matches(): void
    {
        $this->log('customer.create', 'customers', [
            'full_name' => 'Maria Santos',
        ]);

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/activity-logs/list?q=NoSuchName');

        $response->assertOk()
            ->assertSee('No matches')
            ->assertDontSee('Maria Santos');
    }

    public function test_activity_logs_paginates_entries(): void
    {
        foreach (range(1, 30) as $index) {
            $this->log(
                'customer.create',
                'customers',
                ['full_name' => 'Customer '.$index],
                now()->subMinutes($index),
            );
        }

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/activity-logs?page=2');

        $response->assertOk()
            ->assertSee('Customer 30')
            ->assertDontSee('Customer 1');
    }

    public function test_recording_a_payment_writes_a_payment_activity_log(): void
    {
        $customer = $this->customer('Juan Dela Cruz');
        $debt = $this->debt($customer, '500.00');

        $this->withSession(['owner_id' => $this->userId])
            ->postJson('/owner/customers/'.$customer->customer_id.'/payments', [
                'amount' => '200.00',
                'debt_id' => $debt->debt_id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->userId,
            'action' => 'payment.create',
            'table_name' => 'payments',
        ]);
    }

    public function test_paid_in_full_writes_payment_activity_logs_for_each_debt(): void
    {
        $customer = $this->customer('Jose Rizal');
        $this->debt($customer, '400.00');
        $this->debt($customer, '600.00');

        $this->withSession(['owner_id' => $this->userId])
            ->postJson('/owner/customers/'.$customer->customer_id.'/payments', [
                'amount' => '1.00',
                'pay_in_full' => true,
            ])
            ->assertOk();

        $this->assertSame(2, ActivityLog::where('action', 'payment.create')->where('table_name', 'payments')->count());
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->userId,
            'action' => 'payment.pay_in_full',
            'table_name' => 'payments',
        ]);
    }

    public function test_owner_login_and_logout_write_activity_logs(): void
    {
        $this->post('/owner/login', [
            'username' => 'store.owner',
            'password' => 'secret',
        ])->assertRedirect(route('owner.dashboard'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->userId,
            'action' => 'auth.login',
            'table_name' => 'users',
        ]);

        $this->post('/owner/logout')
            ->assertRedirect(route('landing'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->userId,
            'action' => 'auth.logout',
            'table_name' => 'users',
        ]);
    }

    private function log(string $action, string $table, array $newValues, ?Carbon $createdAt = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $this->userId,
            'action' => $action,
            'table_name' => $table,
            'record_id' => (string) Str::uuid(),
            'old_values' => null,
            'new_values' => $newValues,
            'created_at' => $createdAt ?? now(),
        ]);
    }

    private function customer(string $name): Customer
    {
        return Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => (string) random_int(10000, 99999),
            'full_name' => $name,
            'gender' => 'male',
        ]);
    }

    private function debt(Customer $customer, string $amount): Debt
    {
        return Debt::create([
            'debt_id' => (string) Str::uuid(),
            'customer_id' => $customer->customer_id,
            'created_by' => $this->userId,
            'status' => 'unpaid',
            'money_amount' => $amount,
            'loaned_at' => now(),
        ]);
    }
}
