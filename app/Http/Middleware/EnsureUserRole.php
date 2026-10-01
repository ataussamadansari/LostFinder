<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'Account is inactive or suspended',
            ], 403);
        }

        if (!empty($roles) && !in_array($user->role, $roles, true)) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied: Requires ' . implode(' or ', $roles) . ' privileges',
            ], 403);
        }

        return $next($request);
    }
}
