<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerPaymentControllerTest extends TestCase
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

    public function test_payments_page_lists_payments_across_customers(): void
    {
        $this->payment($this->customer('Maria Santos'), '275.55', now()->subDay());
        $this->payment($this->customer('Juan Dela Cruz'), '1200.00', now());

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments');

        $response->assertOk()
            ->assertSee('Payments')
            ->assertSee('Maria Santos')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('₱1,200.00')
            ->assertSee('Store Owner')
            ->assertSee('@store.owner');
    }

    public function test_payments_page_summarizes_the_matching_payments(): void
    {
        $this->payment($this->customer('Maria Santos'), '100.00', now());
        $this->payment($this->customer('Juan Dela Cruz'), '300.00', now());

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments');

        $response->assertOk()
            ->assertSee('Total collected')
            ->assertSee('₱400.00')
            ->assertSee('Average payment')
            ->assertSee('₱200.00')
            ->assertSee('Largest payment')
            ->assertSee('₱300.00');
    }

    public function test_payments_page_shows_empty_state_when_no_payments_exist(): void
    {
        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments');

        $response->assertOk()
            ->assertSee('No payments yet')
            ->assertSee('₱0.00');
    }

    public function test_payments_requires_owner_session(): void
    {
        $this->get('/owner/payments')
            ->assertRedirect(route('landing', ['#login']));

        $this->get('/owner/payments/list')
            ->assertRedirect(route('landing', ['#login']));
    }

    public function test_payments_list_filters_by_customer_name(): void
    {
        $this->payment($this->customer('Maria Santos'), '100.00', now());
        $this->payment($this->customer('Juan Dela Cruz'), '300.00', now());

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments/list?q=Maria');

        $response->assertOk()
            ->assertSee('Maria Santos')
            ->assertDontSee('Juan Dela Cruz');
    }

    public function test_payments_list_filters_by_customer_code(): void
    {
        $maria = $this->customer('Maria Santos', '00042');
        $this->payment($maria, '100.00', now());
        $this->payment($this->customer('Juan Dela Cruz', '00099'), '300.00', now());

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments/list?q=00042');

        $response->assertOk()
            ->assertSee('Maria Santos')
            ->assertDontSee('Juan Dela Cruz');
    }

    public function test_payments_list_filters_by_receiver(): void
    {
        $this->payment($this->customer('Maria Santos'), '100.00', now());
        $this->payment($this->customer('Juan Dela Cruz'), '300.00', now(), $this->cashier());

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments/list?received_by='.$this->userId);

        $response->assertOk()
            ->assertSee('Maria Santos')
            ->assertDontSee('Juan Dela Cruz');
    }

    public function test_payments_list_filters_by_amount_range(): void
    {
        $this->payment($this->customer('Maria Santos'), '100.00', now());
        $this->payment($this->customer('Juan Dela Cruz'), '500.00', now());

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments/list?min=400');

        $response->assertOk()
            ->assertSee('Juan Dela Cruz')
            ->assertDontSee('Maria Santos');
    }

    public function test_payments_list_filters_by_date_range(): void
    {
        $this->payment($this->customer('Maria Santos'), '100.00', now()->subDays(10));
        $this->payment($this->customer('Juan Dela Cruz'), '500.00', now());

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments/list?from='.now()->toDateString());

        $response->assertOk()
            ->assertSee('Juan Dela Cruz')
            ->assertDontSee('Maria Santos');
    }

    public function test_payments_list_paginates_newest_first(): void
    {
        foreach (range(1, 25) as $index) {
            $this->payment($this->customer('Customer '.$index), '10.00', now()->subMinutes($index));
        }

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments?page=2');

        $response->assertOk()
            ->assertSee('Customer 25')
            ->assertDontSee('Customer 1');
    }

    public function test_payments_pagination_links_preserve_active_filters(): void
    {
        foreach (range(1, 45) as $index) {
            $this->payment($this->customer('Customer '.$index), '10.00', now()->subMinutes($index));
        }

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments?min=1');

        $response->assertOk()
            ->assertSee('min=1&amp;page=2', false)
            ->assertSee('min=1&amp;page=3', false);
    }

    public function test_payments_sidebar_link_points_to_the_payments_page(): void
    {
        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/dashboard');

        $response->assertOk()
            ->assertSee(route('owner.payments'), false);
    }

    public function test_payments_customer_cell_stays_a_table_cell(): void
    {
        $this->payment($this->customer('Maria Santos'), '100.00', now());

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments');

        // .owner-customer-cell forces display:flex, which breaks the column layout.
        $response->assertOk()
            ->assertSee('<td data-label="Customer">', false)
            ->assertSee('owner-payments-customer', false)
            ->assertDontSee('class="owner-customer-cell"', false);
    }

    public function test_payments_collapse_the_filter_panel_until_filters_are_active(): void
    {
        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments');

        $response->assertOk()
            ->assertSee('data-payments-toggle', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('id="payments-filters"', false)
            ->assertSee('data-payments-filters hidden', false);
    }

    public function test_payments_expand_the_filter_panel_and_badge_when_a_filter_is_active(): void
    {
        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/payments?min=500&received_by='.$this->userId);

        $response->assertOk()
            ->assertSee('aria-expanded="true"', false)
            ->assertSee('owner-payments__toggle is-active', false)
            ->assertDontSee('data-payments-filters hidden', false);
    }

    private function cashier(): string
    {
        $userId = (string) Str::uuid();

        DB::table('users')->insert([
            'user_id' => $userId,
            'role_id' => $this->ownerRoleId,
            'full_name' => 'Cashier On Duty',
            'username' => 'cashier',
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $userId;
    }

    private function customer(string $name, ?string $code = null): Customer
    {
        return Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => $code ?? (string) random_int(10000, 99999),
            'full_name' => $name,
            'gender' => 'male',
        ]);
    }

    private function payment(Customer $customer, string $amount, Carbon $date, ?string $receivedBy = null): Payment
    {
        $debt = Debt::create([
            'debt_id' => (string) Str::uuid(),
            'customer_id' => $customer->customer_id,
            'created_by' => $this->userId,
            'status' => 'unpaid',
            'money_amount' => $amount,
            'loaned_at' => $date,
        ]);

        return Payment::create([
            'payment_id' => (string) Str::uuid(),
            'debt_id' => $debt->debt_id,
            'customer_id' => $customer->customer_id,
            'amount_paid' => $amount,
            'payment_date' => $date,
            'received_by' => $receivedBy ?? $this->userId,
        ]);
    }
}
