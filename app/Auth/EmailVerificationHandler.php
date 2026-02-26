<?php

namespace App\Auth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EmailVerificationHandler
{
    protected string $cachePrefix = 'email_verification:';
    protected int $verificationTtl = 60; // 60 minutes

    /**
     * Generate verification code and send email
     */
    public function generateAndStoreCode(string $email): string
    {
        $code = Str::random(6);
        $key = $this->cachePrefix . $email;

        // Store code with expiration
        Cache::put($key, [
            'code' => $code,
            'attempts' => 0,
            'created_at' => now(),
        ], now()->addMinutes($this->verificationTtl));

        return $code;
    }

    /**
     * Verify the code provided by user
     */
    public function verifyCode(string $email, string $code): bool
    {
        $key = $this->cachePrefix . $email;
        $data = Cache::get($key);

        if (! $data) {
            return false;
        }

        // Check max attempts
        if ($data['attempts'] >= 5) {
            Cache::forget($key);
            return false;
        }

        // Increment attempts
        $data['attempts']++;

        if ($data['code'] !== $code) {
            Cache::put($key, $data, now()->addMinutes($this->verificationTtl));
            return false;
        }

        // Code is valid, remove from cache
        Cache::forget($key);

        return true;
    }

    /**
     * Check if verification code exists and is valid
     */
    public function hasValidCode(string $email): bool
    {
        return Cache::has($this->cachePrefix . $email);
    }

    /**
     * Resend code (check rate limit)
     */
    public function canResendCode(string $email): bool
    {
        $rateLimitKey = 'email_verification_rate:' . $email;
        $attempts = Cache::get($rateLimitKey, 0);

        if ($attempts >= 3) {
            return false;
        }

        Cache::put($rateLimitKey, $attempts + 1, now()->addMinutes(15));

        return true;
    }

    /**
     * Clean up verification data
     */
    public function cleanup(string $email): void
    {
        Cache::forget($this->cachePrefix . $email);
        Cache::forget('email_verification_rate:' . $email);
    }
}
