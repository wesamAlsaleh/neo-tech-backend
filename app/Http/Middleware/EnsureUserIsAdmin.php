<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // get the user from the request
        $user = $request->user();

        // check if the user is not logged in (i.e., the user is null)
        if (!$user) {
            return response()->json([
                'message' => 'You must be logged in to access this route.',
                'devMessage' => 'USER_NOT_LOGGED_IN',
            ], 401); // Unauthorized
        }

        // Check if the user is an admin
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'You do not have the required role!',
                'devMessage' => 'USER_NOT_ADMIN',
            ], 403); // Forbidden
        }

        return $next($request); // Allow access to the route
    }
}
