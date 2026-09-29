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

    public function test_dashboard_weekly_chart_and_stat_cards_render_live_data(): void
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
            // Collected this month reflects the seeded payment.
            ->assertSee('200.00')
            ->assertSee('from 1 payment received')
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

    public function test_dashboard_reports_zero_when_no_payments_were_received_this_month(): void
    {
        $customer = $this->customer('No Payments', '30004');
        $debt = $this->debt($customer, '300.00', now()->subDays(3));
        $this->payment($debt, '150.00', now()->subMonth());

        $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/dashboard')
            ->assertOk()
            ->assertSee('from 0 payments received');
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
