<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private const ATTEMPTS_ALLOWED = 5;

    private const IP_CEILING = 30;

    private const THROTTLE_MESSAGE = 'Too many login attempts. Please wait a minute and try again.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_owner_login_is_throttled_after_repeated_failures(): void
    {
        $this->storeOwner('store.owner');

        for ($attempt = 1; $attempt <= self::ATTEMPTS_ALLOWED; $attempt++) {
            $this->post('/owner/login', [
                'username' => 'store.owner',
                'password' => 'wrong-password',
            ])->assertRedirect()->assertSessionHasErrors('username');
        }

        $this->assertThrottled($this->post('/owner/login', [
            'username' => 'store.owner',
            'password' => 'wrong-password',
        ]));
    }

    public function test_throttled_owner_login_does_not_grant_a_session_even_with_valid_credentials(): void
    {
        $this->storeOwner('store.owner');

        for ($attempt = 1; $attempt <= self::ATTEMPTS_ALLOWED; $attempt++) {
            $this->post('/owner/login', [
                'username' => 'store.owner',
                'password' => 'wrong-password',
            ]);
        }

        // Correct credentials must not be a way past the limit.
        $response = $this->post('/owner/login', [
            'username' => 'store.owner',
            'password' => 'correct-password',
        ]);

        $this->assertThrottled($response);
        $response->assertSessionMissing('owner_id');
    }

    public function test_owner_login_limit_is_scoped_to_a_single_username_and_ip_pair(): void
    {
        $this->storeOwner('store.owner');

        for ($attempt = 1; $attempt <= self::ATTEMPTS_ALLOWED; $attempt++) {
            $this->post('/owner/login', [
                'username' => 'store.owner',
                'password' => 'wrong-password',
            ]);
        }

        $this->assertThrottled($this->post('/owner/login', [
            'username' => 'store.owner',
            'password' => 'correct-password',
        ]));

        // A different address has its own bucket, so the owner is not locked
        // out of their own account.
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->post('/owner/login', [
                'username' => 'store.owner',
                'password' => 'correct-password',
            ])
            ->assertRedirect(route('owner.dashboard'))
            ->assertSessionHas('owner_id');
    }

    public function test_owner_login_ceiling_covers_every_username_tried_from_one_address(): void
    {
        // A per-username bucket alone would give each attempted username a
        // fresh allowance, so enumeration would never be throttled.
        for ($attempt = 1; $attempt <= self::IP_CEILING; $attempt++) {
            $this->post('/owner/login', [
                'username' => 'owner-'.$attempt,
                'password' => 'wrong-password',
            ])->assertRedirect();
        }

        $this->assertThrottled($this->post('/owner/login', [
            'username' => 'owner-extra',
            'password' => 'wrong-password',
        ]));
    }

    public function test_customer_login_is_throttled_after_repeated_failures(): void
    {
        $this->customerWithCode('58321');

        for ($attempt = 1; $attempt <= self::ATTEMPTS_ALLOWED; $attempt++) {
            $this->post('/customer/login', ['code' => '99999'])
                ->assertRedirect()
                ->assertSessionHasErrors('code');
        }

        $this->assertThrottled($this->post('/customer/login', ['code' => '99999']), 'code');
    }

    public function test_throttled_customer_login_does_not_grant_a_session_even_with_a_valid_code(): void
    {
        $customer = $this->customerWithCode('58321');

        // A customer signs in with the code alone, so there is no "wrong
        // password" retry to exhaust a per-credential bucket. Exhausting the
        // per-address ceiling must refuse a code that is in fact valid.
        for ($attempt = 1; $attempt <= self::IP_CEILING; $attempt++) {
            $this->post('/customer/login', ['code' => $this->unusedCode($attempt)]);
        }

        $response = $this->post('/customer/login', ['code' => $customer->customer_code]);

        $this->assertThrottled($response, 'code');
        $response->assertSessionMissing('customer_id');
    }

    public function test_customer_login_ceiling_covers_every_code_tried_from_one_address(): void
    {
        $this->customerWithCode('58321');

        // Each distinct code would otherwise get its own bucket, letting a
        // rotating guesser enumerate the whole code space.
        for ($attempt = 1; $attempt <= self::IP_CEILING; $attempt++) {
            $this->post('/customer/login', ['code' => $this->unusedCode($attempt)])
                ->assertRedirect();
        }

        $this->assertThrottled($this->post('/customer/login', ['code' => '99999']), 'code');
    }

    public function test_customer_login_limit_is_scoped_to_a_single_code_and_ip_pair(): void
    {
        $customer = $this->customerWithCode('58321');

        for ($attempt = 1; $attempt <= self::ATTEMPTS_ALLOWED; $attempt++) {
            $this->post('/customer/login', ['code' => '99999']);
        }

        $this->assertThrottled($this->post('/customer/login', ['code' => '99999']), 'code');

        // One customer exhausting their bucket must not lock out a different
        // customer signing in from the same store network.
        $this->post('/customer/login', ['code' => $customer->customer_code])
            ->assertRedirect(route('customer.dashboard'))
            ->assertSessionHas('customer_id', $customer->customer_id);
    }

    public function test_throttled_login_returns_a_429_for_json_requests(): void
    {
        $this->customerWithCode('58321');

        for ($attempt = 1; $attempt <= self::ATTEMPTS_ALLOWED; $attempt++) {
            $this->postJson('/customer/login', ['code' => '99999']);
        }

        $this->postJson('/customer/login', ['code' => '99999'])
            ->assertStatus(429)
            ->assertExactJson(['message' => self::THROTTLE_MESSAGE]);
    }

    public function test_a_successful_login_within_the_limit_is_unaffected(): void
    {
        $customer = $this->customerWithCode('58321');

        $this->post('/customer/login', ['code' => $customer->customer_code])
            ->assertRedirect(route('customer.dashboard'))
            ->assertSessionHas('customer_id', $customer->customer_id);
    }

    /**
     * A throttled form post is redirected back to the login modal with the
     * friendly message keyed by the field the form submits, carrying the rate
     * limit headers so the lockout stays observable to anything inspecting
     * the response.
     */
    private function assertThrottled(TestResponse $response, string $errorKey = 'username'): void
    {
        $response->assertRedirect();
        $response->assertSessionHasErrors([$errorKey => self::THROTTLE_MESSAGE]);
        $response->assertHeader('Retry-After');
    }

    private function storeOwner(string $username): User
    {
        return User::factory()->storeOwner()->create([
            'username' => $username,
            'password' => Hash::make('correct-password'),
        ]);
    }

    private function customerWithCode(string $code): Customer
    {
        return Customer::create([
            'customer_code' => $code,
            'full_name' => 'Throttle Test Customer',
            'gender' => 'male',
        ]);
    }

    /**
     * A valid-looking but unused code, so each guess lands in its own
     * per-credential bucket.
     */
    private function unusedCode(int $attempt): string
    {
        return str_pad((string) $attempt, 5, '0', STR_PAD_LEFT);
    }
}
