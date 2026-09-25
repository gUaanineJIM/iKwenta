<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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

        ActivityLog::create([
            'user_id' => $owner->user_id,
            'action' => 'auth.login',
            'table_name' => 'users',
            'record_id' => $owner->user_id,
            'old_values' => null,
            'new_values' => ['username' => $owner->username],
            'created_at' => now(),
        ]);

        return redirect()->route('owner.dashboard');
    }

    public function logout(Request $request)
    {
        $ownerId = $request->session()->get('owner_id');

        if ($ownerId && ($owner = User::find($ownerId))) {
            ActivityLog::create([
                'user_id' => $ownerId,
                'action' => 'auth.logout',
                'table_name' => 'users',
                'record_id' => $ownerId,
                'old_values' => null,
                'new_values' => ['username' => $owner->username],
                'created_at' => now(),
            ]);
        }

        $request->session()->forget('owner_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing');
    }
}
