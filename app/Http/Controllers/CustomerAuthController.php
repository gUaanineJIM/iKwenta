<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'code' => [
                'required',
                'digits:5',
            ],
        ]);

        $customer = Customer::where(
            'customer_code',
            $request->code
        )->first();

        if (! $customer) {
            // A customer signs in with nothing but this code, so guessing is
            // the main threat. This branch only ever runs for a code that
            // matched no customer, so a valid code is never written to the
            // log. `activity_logs.user_id` is a non-nullable foreign key and
            // there is no customer to attribute the attempt to, so it goes to
            // the application log instead.
            Log::warning('customer.login.failed', [
                'code' => $request->string('code')->toString(),
                'ip' => $request->ip(),
            ]);

            return back()
                ->withErrors([
                    'code' => 'Invalid customer code.',
                ])
                ->withInput();
        }

        // Store the customer's UUID in the session
        $request->session()->regenerate();
        $request->session()->forget('owner_id');

        $request->session()->put(
            'customer_id',
            $customer->customer_id
        );

        return redirect()->route('customer.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('customer_id');

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
