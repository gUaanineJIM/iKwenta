<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeding_twice_produces_the_same_records(): void
    {
        $this->seed();

        $afterFirst = $this->recordCounts();
        $this->assertGreaterThan(0, $afterFirst['roles']);

        $this->seed();

        $this->assertSame($afterFirst, $this->recordCounts());
    }

    public function test_seeding_thrice_does_not_duplicate_roles_or_customer_codes(): void
    {
        $this->seed();
        $this->seed();
        $this->seed();

        // `roles.role_name` and `customers.customer_code` are both unique, so
        // a plain insert would abort the second seed.
        $this->assertSame(2, Role::count());

        $codes = Customer::pluck('customer_code');
        $this->assertSame($codes->count(), $codes->unique()->count());
    }

    /**
     * @return array<string, int>
     */
    private function recordCounts(): array
    {
        return [
            'roles' => Role::count(),
            'users' => User::count(),
            'customers' => Customer::count(),
            'payments' => Payment::count(),
        ];
    }
}
