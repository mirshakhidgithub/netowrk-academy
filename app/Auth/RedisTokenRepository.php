<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RedisTokenRepository
{
    protected string $prefix = 'sanctum_token:';
    protected int $ttl;

    public function __construct(int $ttl = 43200)
    {
        $this->ttl = $ttl; // Default 30 days in minutes
    }

    /**
     * Store token in Redis with user association.
     */
    public function storeToken(string $plainToken, User $user): void
    {
        $hashedToken = hash('sha256', $plainToken);
        
        Cache::put(
            $this->prefix . $hashedToken,
            [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'created_at' => now(),
                'expires_at' => now()->addMinutes($this->ttl),
            ],
            now()->addMinutes($this->ttl)
        );
    }

    /**
     * Verify token exists in Redis.
     */
    public function verifyToken(string $plainToken): ?array
    {
        $hashedToken = hash('sha256', $plainToken);
        $data = Cache::get($this->prefix . $hashedToken);

        if (! $data) {
            return null;
        }

        // Token found and valid
        if ($data['expires_at'] > now()) {
            return $data;
        }

        // Token expired, remove it
        Cache::forget($this->prefix . $hashedToken);

        return null;
    }

    /**
     * Get user from token.
     */
    public function getUserFromToken(string $plainToken): ?User
    {
        $data = $this->verifyToken($plainToken);

        if (! $data) {
            return null;
        }

        return User::find($data['user_id']);
    }

    /**
     * Revoke specific token.
     */
    public function revokeToken(string $plainToken): void
    {
        $hashedToken = hash('sha256', $plainToken);
        Cache::forget($this->prefix . $hashedToken);
    }

    /**
     * Revoke all tokens for user.
     */
    public function revokeAllUserTokens(User $user): void
    {
        // In a real scenario, you might store a list of user's token hashes
        // For now, this would require a separate index
        // You could use: Cache::remember('user_tokens:' . $user->id, ...)
    }

    /**
     * Flush all tokens (use with caution).
     */
    public function flushAll(): void
    {
        Cache::flush();
    }
}
