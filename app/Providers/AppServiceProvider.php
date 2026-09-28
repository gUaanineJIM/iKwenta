<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Number of failed login attempts tolerated per minute, per credential
     * and IP pair.
     */
    private const LOGIN_ATTEMPTS_PER_MINUTE = 5;

    /**
     * Ceiling on total login attempts per minute from a single IP, applied
     * across every credential that IP tries.
     */
    private const LOGIN_ATTEMPTS_PER_IP_PER_MINUTE = 30;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.customer');

        $this->configureLoginRateLimiters();
    }

    /**
     * Throttle the two login forms.
     *
     * The owner portal is protected by a password, but a customer signs in
     * with nothing but a 5-digit code drawn from a small space, so both forms
     * are rate limited.
     *
     * Each limiter returns two limits, and a request must satisfy both:
     *
     * - One keyed by the submitted credential *and* the IP, which stops an
     *   attacker hammering a single known customer or owner. Keying on the IP
     *   alone would lock out every customer sharing one store network, and
     *   keying on the credential alone would let an attacker lock a known
     *   customer out on purpose.
     * - One keyed by the IP alone, which stops the same weakness rotating
     *   the credential: without it, each distinct code tried from one address
     *   would land in its own bucket and enumeration would never be throttled.
     */
    private function configureLoginRateLimiters(): void
    {
        RateLimiter::for('owner-login', function (Request $request): array {
            $username = Str::lower($request->string('username')->toString());

            return [
                Limit::perMinute(self::LOGIN_ATTEMPTS_PER_MINUTE)
                    ->by($username.'|'.$request->ip())
                    ->response($this->loginThrottleResponse('username')),
                Limit::perMinute(self::LOGIN_ATTEMPTS_PER_IP_PER_MINUTE)
                    ->by($request->ip())
                    ->response($this->loginThrottleResponse('username')),
            ];
        });

        RateLimiter::for('customer-login', function (Request $request): array {
            $code = $request->string('code')->toString();

            return [
                Limit::perMinute(self::LOGIN_ATTEMPTS_PER_MINUTE)
                    ->by($code.'|'.$request->ip())
                    ->response($this->loginThrottleResponse('code')),
                Limit::perMinute(self::LOGIN_ATTEMPTS_PER_IP_PER_MINUTE)
                    ->by($request->ip())
                    ->response($this->loginThrottleResponse('code')),
            ];
        });
    }

    /**
     * Send a throttled login back to the login modal with a readable
     * message instead of the framework's bare 429 error page.
     *
     * A browser form post is redirected with the usual flash error so the
     * modal keeps working, and the rate limit headers are still attached so
     * the lockout remains observable. JSON callers get a 429, which is the
     * status that tells a client to back off.
     *
     * The error is keyed by the credential the form actually submits, so it
     * lands on the matching field the same way the login controllers key
     * their own failures.
     *
     * @param  string  $errorKey  The submitted field the message belongs to.
     */
    private function loginThrottleResponse(string $errorKey): callable
    {
        return function (Request $request, array $headers) use ($errorKey) {
            $message = 'Too many login attempts. Please wait a minute and try again.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 429, $headers);
            }

            return back()
                ->withInput($request->only(['username', 'code']))
                ->withErrors([$errorKey => $message])
                ->withHeaders($headers);
        };
    }
}
