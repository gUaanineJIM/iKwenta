<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticatedCustomer
{
    /**
     * Require a session pinned to an existing customer account.
     *
     * The customer id is never trusted from the URL or request body, and a
     * stale id (for example after the customer is deleted) signs the visitor
     * out on the next request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $this->authenticatedCustomer($request);

        if ($customer === null) {
            $request->session()->forget('customer_id');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('landing')
                ->withErrors(['code' => 'Please sign in first.']);
        }

        return $next($request);
    }

    private function authenticatedCustomer(Request $request): ?Customer
    {
        $customerId = $request->session()->get('customer_id');

        if ($customerId === null) {
            return null;
        }

        return Customer::find($customerId);
    }
}
