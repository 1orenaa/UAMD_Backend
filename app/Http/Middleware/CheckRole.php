<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Kontrollon nëse useri i autentikuar ka rolin e kërkuar.
     *
     * Përdorimi në routes:
     *   ->middleware('role:admin')
     *   ->middleware('role:admin,pedagog')   ← lejon të dy rolet
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Nuk jeni i autentikuar.',
            ], 401);
        }

        if (!in_array($user->role, $roles)) {
            return response()->json([
                'message' => 'Nuk keni leje për këtë veprim.',
                'kerkuar' => $roles,
                'aktual'  => $user->role,
            ], 403);
        }

        return $next($request);
    }
}
