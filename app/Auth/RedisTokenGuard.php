<?php

namespace App\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RedisTokenGuard implements Guard
{
    use GuardHelpers;

    protected Request $request;
    protected UserProvider $provider;
    protected string $inputKey;
    protected string $storageKey;
    protected int $tokenTtl;

    public function __construct(
        UserProvider $provider,
        Request $request,
        int $tokenTtl = 43200,
    ) {
        $this->provider = $provider;
        $this->request = $request;
        $this->inputKey = 'Authorization';
        $this->storageKey = 'token:';
        $this->tokenTtl = $tokenTtl;
    }

    /**
     * Get the token from the request.
     */
    protected function getTokenForRequest(): ?string
    {
        $token = $this->request->bearerToken();

        if (empty($token)) {
            return null;
        }

        return $token;
    }

    /**
     * Get the user from Redis token.
     */
    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->getTokenForRequest();

        if (empty($token)) {
            return null;
        }

        // Check if token exists in Redis
        $userId = Cache::get($this->storageKey . hash('sha256', $token));

        if ($userId) {
            $this->user = $this->provider->retrieveById($userId);
            return $this->user;
        }

        return null;
    }

    /**
     * Authenticate the user, creating and caching the token.
     */
    public function authenticate() {}

    /**
     * Validate user credentials and return token.
     */
    public function validate(array $credentials = []): bool
    {
        return false;
    }
}
