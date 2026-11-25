<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | User Must Be Authenticated
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'errors' => null,
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Role
        |--------------------------------------------------------------------------
        */

        if (!$user->hasAnyRole($roles)) {

            return response()->json([
                'success' => false,
                'message' => 'You do not have the required role.',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}

