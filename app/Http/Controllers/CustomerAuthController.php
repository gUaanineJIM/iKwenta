<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

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
            return back()
                ->withErrors([
                    'code' => 'Invalid customer code.',
                ])
                ->withInput();
        }

        // Store the customer's UUID in the session
        $request->session()->regenerate();

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
