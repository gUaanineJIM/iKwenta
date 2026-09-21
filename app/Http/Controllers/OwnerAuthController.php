<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OwnerAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $owner = User::where('username', $credentials['username'])
            ->whereHas('role', fn ($query) => $query->where('role_name', 'store_owner'))
            ->first();

        if (! $owner || ! Hash::check($credentials['password'], $owner->password)) {
            return back()
                ->withErrors(['username' => 'Invalid owner username or password.'])
                ->withInput($request->only('username'));
        }

        $request->session()->regenerate();
        $request->session()->put('owner_id', $owner->user_id);

        return redirect()->route('owner.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('owner_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}