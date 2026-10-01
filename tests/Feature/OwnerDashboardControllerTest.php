<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

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
            'username' => 'dashboard.owner',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_dashboard_rankings_use_live_customer_debt_data(): void
    {
        $highest = $this->customer('Highest Debt', '30001');
        $oldest = $this->customer('Oldest Debt', '30002');

        $this->debt($highest, '1000.00', now()->subDays(5));
        $this->debt($oldest, '500.00', now()->subDays(20));

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/dashboard');

        $response->assertOk()
            ->assertSee('Total Debt Ranking')
            ->assertSee('Longest Outstanding Debt')
            ->assertSee('Highest Debt')
            ->assertSee('Oldest Debt')
            ->assertSee('Days outstanding')
            ->assertSee('days');
    }

    public function test_dashboard_renders_live_weekly_chart_and_overview_cards(): void
    {
        $customer = $this->customer('Weekly Customer', '30003');

        // Two debts inside the current Mon-Sun week, one clearly outside it.
        $this->debt($customer, '1250.00', now()->startOfWeek(Carbon::MONDAY)->addDays(2));
        $this->debt($customer, '4000.00', now()->subMonths(2));
        $debt = $this->debt($customer, '500.00', now()->startOfWeek(Carbon::MONDAY)->addDays(2));

        $this->payment($debt, '200.00', now());

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/dashboard');

        $response->assertOk()
            // Week headline sums only this week's debts: 1250 + 500.
            ->assertSee('1,750.00')
            // The full total in the ranking table still includes the 4000 debt,
            // proving it exists and was excluded from the week, not lost.
            ->assertSee('5,750.00')
            ->assertSee('200.00')
            // The serialised payload drives both Chart.js datasets.
            ->assertSee('"added":[0,0,1750,0,0,0,0]', false)
            ->assertSee('"collected":[', false)
            // The former hard-coded placeholders must never come back.
            ->assertDontSee('53,590')
            ->assertDontSee('4250')
            // Paid progress renders as a bar from the computed percentage (200 / 5750).
            ->assertSee('owner-progress__bar')
            ->assertSee('width: 3%');
    }

    public function test_dashboard_shows_payoff_empty_state_when_no_recent_customer_is_fully_paid(): void
    {
        $customer = $this->customer('No Payments', '30004');
        $debt = $this->debt($customer, '300.00', now()->subDays(3));
        $this->payment($debt, '150.00', now()->subMonth());

        $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/dashboard')
            ->assertOk()
            ->assertSee('No recent payoffs yet')
            ->assertSee('No customers have paid off their full balance in the last 30 days.');
    }

    public function test_dashboard_lists_recent_debt_free_customers_in_settlement_order(): void
    {
        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
        $latest = $this->customer('Latest Paid Customer', '30005');
        $this->paidDebt($latest, '100.00', now()->subDay());

        $atRetentionBoundary = $this->customer('Boundary Customer', '30006');
        $this->paidDebt($atRetentionBoundary, '200.00', now()->subDays(30));

        $stillOwes = $this->customer('Still Owes', '30007');
        $this->paidDebt($stillOwes, '100.00', now()->subDays(2));
        $this->debt($stillOwes, '50.00', now()->subDays(3));

        $tooOld = $this->customer('Old Settlement', '30008');
        $this->paidDebt($tooOld, '300.00', now()->subDays(31));

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/dashboard');

        $response->assertOk()
            ->assertSee('Recent payoffs')
            ->assertSee('Latest Paid Customer')
            ->assertSee('Sep 30, 2026')
            ->assertSee('12:00 PM');
        $recentPaidOffCustomers = $response->viewData('recentPaidOffCustomers');

        $this->assertSame(
            ['Latest Paid Customer', 'Boundary Customer'],
            $recentPaidOffCustomers->pluck('name')->all(),
        );
        $this->assertSame(
            $latest->customer_id,
            $response->viewData('latestPaidOffCustomer')['customer_id'],
        );
        $this->assertSame(
            '2026-09-30 12:00:00',
            $recentPaidOffCustomers->first()['latest_paid_at']->format('Y-m-d H:i:s'),
        );
    }

    private function customer(string $name, string $code): Customer
    {
        return Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => $code,
            'full_name' => $name,
            'gender' => 'male',
        ]);
    }

    private function debt(Customer $customer, string $amount, \DateTimeInterface $loanedAt): Debt
    {
        return Debt::create([
            'debt_id' => (string) Str::uuid(),
            'customer_id' => $customer->customer_id,
            'created_by' => $this->userId,
            'status' => 'unpaid',
            'money_amount' => $amount,
            'loaned_at' => $loanedAt,
        ]);
    }

    private function paidDebt(Customer $customer, string $amount, \DateTimeInterface $paidAt): Debt
    {
        $debt = $this->debt($customer, $amount, $paidAt);
        $debt->update([
            'status' => 'paid',
            'paid_at' => $paidAt,
        ]);

        return $debt;
    }

    private function payment(Debt $debt, string $amount, \DateTimeInterface $paidAt): Payment
    {
        return Payment::create([
            'payment_id' => (string) Str::uuid(),
            'debt_id' => $debt->debt_id,
            'customer_id' => $debt->customer_id,
            'amount_paid' => $amount,
            'payment_date' => $paidAt,
            'received_by' => $this->userId,
        ]);
    }
}
