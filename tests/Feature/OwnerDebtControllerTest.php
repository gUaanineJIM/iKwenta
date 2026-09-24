<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Debt;
use App\Services\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerDebtControllerTest extends TestCase
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
            'username' => 'debts.owner',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_debts_page_redirects_guests_to_login(): void
    {
        $this->get('/owner/debts')
            ->assertRedirect(route('landing', ['#login']));
    }

    public function test_debts_page_renders_live_analytics_from_debt_records(): void
    {
        $partial = $this->customer('Maria Santos', '40001');
        $unpaid = $this->customer('Juan Dela Cruz', '40002');
        $settled = $this->customer('Ana Reyes', '40003');

        $partialDebt = $this->debt($partial, '1000.00', now()->subDays(25));
        $this->debt($unpaid, '500.00', now()->subDays(75));
        $settledDebt = $this->debt($settled, '400.00', now()->subDays(5));

        $this->app->make(CustomerAccountService::class)->recordPayment($partialDebt, '300.00', $this->userId);
        $this->app->make(CustomerAccountService::class)->recordPayment($settledDebt, '400.00', $this->userId);

        $response = $this->withSession(['owner_id' => $this->userId])
            ->get('/owner/debts');

        $response->assertOk()
            ->assertSee('Outstanding Balance')
            ->assertSee('Monthly Debt vs Collections')
            ->assertSee('By Status')
            ->assertSee('Outstanding by Age')
            ->assertSee('Top Debtors')
            ->assertSee('Recent Debt Records')
            ->assertSee('₱700.00')
            ->assertSee('Total Unpaid Debt')
            ->assertSee('37% of debt recorded')
            ->assertSee('Maria Santos')
            ->assertSee('Juan Dela Cruz')
            ->assertSee('Ana Reyes')
            ->assertSee('Partially Paid')
            ->assertSee('Unpaid')
            ->assertSee('Paid');
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
}
