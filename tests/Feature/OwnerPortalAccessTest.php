<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class OwnerPortalAccessTest extends TestCase
{
    use RefreshDatabase;

    private string $ownerRoleId;

    private string $ownerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerRoleId = (string) Str::uuid();
        $this->ownerId = (string) Str::uuid();

        DB::table('roles')->insert([
            'role_id' => $this->ownerRoleId,
            'role_name' => 'store_owner',
            'description' => 'Store owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'user_id' => $this->ownerId,
            'role_id' => $this->ownerRoleId,
            'full_name' => 'Store Owner',
            'username' => 'portal.owner',
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_guest_is_redirected_away_from_owner_pages(): void
    {
        $this->get('/owner/dashboard')->assertRedirect(route('landing', ['#login']));
        $this->get('/owner/products')->assertRedirect(route('landing', ['#login']));
        $this->get('/owner/customers')->assertRedirect(route('landing', ['#login']));
        $this->get('/owner/debts')->assertRedirect(route('landing', ['#login']));
        $this->get('/owner/activity-logs')->assertRedirect(route('landing', ['#login']));
    }

    public function test_logged_in_customer_cannot_open_owner_portal(): void
    {
        $customer = Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => '11111',
            'full_name' => 'Portal Customer',
            'gender' => 'male',
        ]);

        $this->withSession(['customer_id' => $customer->customer_id])
            ->get('/owner/customers')
            ->assertRedirect(route('landing', ['#login']));

        $this->withSession(['customer_id' => $customer->customer_id])
            ->get('/owner/products')
            ->assertRedirect(route('landing', ['#login']));

        $this->withSession(['customer_id' => $customer->customer_id])
            ->get('/owner/debts')
            ->assertRedirect(route('landing', ['#login']));
    }

    public function test_customer_login_clears_an_existing_owner_session(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $customer = Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => '22222',
            'full_name' => 'Switching Customer',
            'gender' => 'female',
        ]);

        $response = $this->withSession(['owner_id' => $this->ownerId])
            ->post('/customer/login', ['code' => $customer->customer_code]);

        $response->assertRedirect(route('customer.dashboard'))
            ->assertSessionHas('customer_id', $customer->customer_id)
            ->assertSessionMissing('owner_id');
    }

    public function test_owner_login_clears_an_existing_customer_session(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $customer = Customer::create([
            'customer_id' => (string) Str::uuid(),
            'customer_code' => '33333',
            'full_name' => 'Previous Customer',
            'gender' => 'male',
        ]);

        $response = $this->withSession(['customer_id' => $customer->customer_id])
            ->post('/owner/login', [
                'username' => 'portal.owner',
                'password' => 'secret',
            ]);

        $response->assertRedirect(route('owner.dashboard'))
            ->assertSessionHas('owner_id', $this->ownerId)
            ->assertSessionMissing('customer_id');
    }

    public function test_unauthenticated_write_requests_to_owner_portal_are_rejected(): void
    {
        $this->postJson('/owner/customers', [
            'full_name' => 'Sneaky Customer',
            'debt_types' => ['money'],
            'money_amount' => '100.00',
        ])->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);

        $this->postJson('/owner/products', [
            'products' => [['product_name' => 'Sneaky Product', 'price' => 10]],
        ])->assertUnauthorized();

        $this->assertSame(0, Customer::count());
        $this->assertDatabaseCount('products', 0);
    }

    public function test_unauthenticated_ajax_fragment_request_returns_unauthenticated(): void
    {
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get('/owner/customers/list')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_session_owner_must_be_an_existing_store_owner(): void
    {
        $this->withSession(['owner_id' => (string) Str::uuid()])
            ->get('/owner/customers')
            ->assertRedirect(route('landing', ['#login']));

        $cashierRoleId = (string) Str::uuid();
        $cashierId = (string) Str::uuid();

        DB::table('roles')->insert([
            'role_id' => $cashierRoleId,
            'role_name' => 'cashier',
            'description' => 'Cashier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->insert([
            'user_id' => $cashierId,
            'role_id' => $cashierRoleId,
            'full_name' => 'Not An Owner',
            'username' => 'cashier.user',
            'password' => 'secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['owner_id' => $cashierId])
            ->get('/owner/customers')
            ->assertRedirect(route('landing', ['#login']));
    }
}
