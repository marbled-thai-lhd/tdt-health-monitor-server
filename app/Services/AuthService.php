<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Get all users from environment configuration
     */
    public function getUsers(): array
    {
        return [
            'admin' => [
                'username' => env('ADMIN_USERNAME', 'admin'),
                'password_hash' => env('ADMIN_PASSWORD_HASH'),
                'role' => 'admin',
                'name' => 'Administrator',
            ],
            'viewer' => [
                'username' => env('VIEWER_USERNAME', 'viewer'),
                'password_hash' => env('VIEWER_PASSWORD_HASH'),
                'role' => 'viewer',
                'name' => 'Viewer',
            ],
        ];
    }

    /**
     * Authenticate user with username and password
     */
    public function authenticate(string $username, string $password): ?array
    {
        $users = $this->getUsers();

        foreach ($users as $user) {
            if ($user['username'] === $username &&
                !empty($user['password_hash']) &&
                Hash::check($password, $user['password_hash'])) {

                return [
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'name' => $user['name'],
                ];
            }
        }

        return null;
    }

    /**
     * Check if user has required role
     */
    public function hasRole(array $user, string $role): bool
    {
        return $user['role'] === $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(array $user): bool
    {
        return $this->hasRole($user, 'admin');
    }

    /**
     * Check if user is viewer
     */
    public function isViewer(array $user): bool
    {
        return $this->hasRole($user, 'viewer');
    }

    /**
     * Check if user can perform action
     */
    public function canPerformAction(array $user, string $action): bool
    {
        // Admin can do everything
        if ($this->isAdmin($user)) {
            return true;
        }

        // Viewer can only read
        $readOnlyActions = [
            'view',
            'index',
            'show',
            'export',
        ];

        return in_array($action, $readOnlyActions);
    }

    /**
     * Generate password hash (helper for creating .env values)
     */
    public static function generatePasswordHash(string $password): string
    {
        return Hash::make($password);
    }
}
