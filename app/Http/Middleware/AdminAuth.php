<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Single-password admin gate.
 *
 * Two entry paths:
 *   - Browser session: user posts password to /login → session gets an
 *     `admin_authed` flag. All subsequent HTML requests pass if the flag
 *     is present.
 *   - API request with Authorization: Bearer <ADMIN_PASSWORD> — same
 *     password validated per-request. Used by fetch() calls from Blade
 *     pages and by the bot when it calls back for CRM overlays.
 */
class AdminAuth {
    public function handle(Request $request, Closure $next): Response {
        $expected = config('services.admin_password');

        // Bearer header path (API-style)
        $bearer = $request->bearerToken();
        if (!empty($bearer) && $bearer === $expected) {
            return $next($request);
        }

        // Session path (browser)
        if ($request->session()->get('admin_authed') === true) {
            return $next($request);
        }

        // Not authed
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return redirect()->route('login');
    }
}
