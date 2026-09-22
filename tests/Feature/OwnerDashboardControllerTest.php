<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Debt;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
