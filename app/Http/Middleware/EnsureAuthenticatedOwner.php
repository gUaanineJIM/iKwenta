<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticatedOwner
{
    /**
     * Require a session pinned to a store owner account.
     *
     * The owner id is never trusted from the URL or request body. The
     * session owner must still exist and keep the store_owner role, so a
     * deleted or demoted account is signed out on the next request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $owner = $this->authenticatedOwner($request);

        if ($owner === null) {
            $request->session()->forget('owner_id');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('landing', ['#login']);
        }

        return $next($request);
    }

    private function authenticatedOwner(Request $request): ?User
    {
        $ownerId = $request->session()->get('owner_id');

        if ($ownerId === null) {
            return null;
        }

        return User::query()
            ->whereKey($ownerId)
            ->whereHas('role', fn ($query) => $query->where('role_name', 'store_owner'))
            ->first();
    }
}
