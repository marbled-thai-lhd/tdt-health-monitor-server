<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Show login form
     */
    public function showLoginForm()
    {
        // Redirect if already logged in
        if (session()->has('user')) {
            return redirect()->route('dashboard.index');
        }

        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = $this->authService->authenticate(
            $request->username,
            $request->password
        );

        if ($user) {
            // Store user in session
            session(['user' => $user]);

            Log::info('User logged in', [
                'username' => $user['username'],
                'role' => $user['role'],
                'ip' => $request->ip(),
            ]);

            return redirect()
                ->intended(route('dashboard.index'))
                ->with('success', 'Welcome back, ' . $user['name'] . '!');
        }

        Log::warning('Failed login attempt', [
            'username' => $request->username,
            'ip' => $request->ip(),
        ]);

        return back()
            ->withInput($request->only('username'))
            ->with('error', 'Invalid username or password');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        $user = session('user');

        if ($user) {
            Log::info('User logged out', [
                'username' => $user['username'],
                'role' => $user['role'],
            ]);
        }

        session()->forget('user');
        session()->flush();

        return redirect()->route('login')->with('success', 'You have been logged out successfully');
    }
}
