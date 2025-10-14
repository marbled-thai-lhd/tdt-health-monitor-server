<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role = null): Response
    {
        // Check if user is logged in
        if (!session()->has('user')) {
            return redirect()->route('login')->with('error', 'Please login to continue');
        }

        $user = session('user');

        // If specific role is required, check it
        if ($role) {
            if ($user['role'] !== $role && $user['role'] !== 'admin') {
                abort(403, 'Unauthorized action. Admin access required.');
            }
        }

        return $next($request);
    }
}
