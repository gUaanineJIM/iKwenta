<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateOwnerCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_it_creates_a_store_owner_that_can_sign_in_to_the_owner_portal(): void
    {
        $this->artisan('owner:create', [
            'username' => 'store.owner',
            '--name' => 'Store Owner',
            '--password' => 'correct-password',
        ])->assertSuccessful();

        $owner = User::where('username', 'store.owner')->firstOrFail();

        $this->assertSame('Store Owner', $owner->full_name);
        $this->assertSame('store_owner', $owner->role->role_name);
        $this->assertTrue(Hash::check('correct-password', $owner->password));

        $this->post('/owner/login', [
            'username' => 'store.owner',
            'password' => 'correct-password',
        ])
            ->assertRedirect(route('owner.dashboard'))
            ->assertSessionHas('owner_id', $owner->user_id);

        $this->get('/owner/dashboard')->assertOk();
    }

    public function test_it_creates_the_store_owner_role_when_the_roles_table_is_empty(): void
    {
        $this->assertSame(0, Role::count());

        $this->artisan('owner:create', [
            'username' => 'first.owner',
            '--name' => 'First Owner',
            '--password' => 'correct-password',
        ])->assertSuccessful();

        $this->assertSame(1, Role::where('role_name', 'store_owner')->count());
        $this->assertSame(1, User::where('username', 'first.owner')->count());
    }

    public function test_it_reuses_an_existing_store_owner_role(): void
    {
        Role::create([
            'role_name' => 'store_owner',
            'description' => 'Already seeded',
        ]);

        $this->artisan('owner:create', [
            'username' => 'second.owner',
            '--name' => 'Second Owner',
            '--password' => 'correct-password',
        ])->assertSuccessful();

        $this->assertSame(1, Role::where('role_name', 'store_owner')->count());
        $this->assertSame('Already seeded', Role::where('role_name', 'store_owner')->firstOrFail()->description);
    }

    public function test_it_refuses_a_username_that_is_already_taken(): void
    {
        User::factory()->storeOwner()->create(['username' => 'store.owner']);

        $this->artisan('owner:create', [
            'username' => 'store.owner',
            '--name' => 'Impostor',
            '--password' => 'correct-password',
        ])->assertFailed();

        $this->assertSame(1, User::where('username', 'store.owner')->count());
        $this->assertNull(User::where('full_name', 'Impostor')->first());
    }

    public function test_it_refuses_a_password_that_is_too_short(): void
    {
        $this->artisan('owner:create', [
            'username' => 'weak.owner',
            '--name' => 'Weak Owner',
            '--password' => 'short',
        ])->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_it_requires_a_username(): void
    {
        $this->artisan('owner:create', [
            'username' => '',
            '--name' => 'Nameless Owner',
            '--password' => 'correct-password',
        ])->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_it_prompts_for_a_password_when_none_is_supplied(): void
    {
        $this->artisan('owner:create', ['username' => 'prompted.owner'])
            ->expectsQuestion('Password', 'prompted-password')
            ->assertSuccessful();

        $owner = User::where('username', 'prompted.owner')->firstOrFail();

        $this->assertTrue(Hash::check('prompted-password', $owner->password));
    }

    public function test_the_created_owner_never_stores_the_password_in_plain_text(): void
    {
        $this->artisan('owner:create', [
            'username' => 'store.owner',
            '--name' => 'Store Owner',
            '--password' => 'correct-password',
        ])->assertSuccessful();

        $owner = User::where('username', 'store.owner')->firstOrFail();

        $this->assertNotSame('correct-password', $owner->password);
        $this->assertStringStartsWith('$2y$', $owner->password);
    }
}
