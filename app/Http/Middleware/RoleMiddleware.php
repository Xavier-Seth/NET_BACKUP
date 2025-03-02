<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthorized - Not Logged In'], 403);
        }

        $userRole = auth()->user()->role;

        if (!in_array($userRole, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized - Access Denied'], 403);
            }
            abort(403, 'Unauthorized - Access Denied');
        }

        return $next($request);
    }

}
