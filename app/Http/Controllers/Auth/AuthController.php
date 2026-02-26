<?php

namespace App\Http\Controllers\Auth;

use App\Auth\RedisTokenRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    protected RedisTokenRepository $tokenRepository;

    public function __construct(RedisTokenRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Create user with email not verified
        $user = User::create($request->validated());

        return response()->json([
            'message' => 'Registration successful. Please verify your email.',
            'email' => $user->email,
        ], 201);
    }

    /**
     * Login user and return a token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'errors' => [
                    'email' => ['Invalid email or password'],
                ],
            ], 422);
        }

        /** @var User $user */
        $user = Auth::user();

        // Check if email is verified
        if (! $user->email_verified_at) {
            Auth::logout();

            return response()->json([
                'errors' => [
                    'email' => ['Please verify your email address before logging in'],
                ],
                'email' => $user->email,
            ], 422);
        }

        // Delete previous tokens (single session)
        $user->tokens()->delete();
        
        // Clear user tokens from Redis cache
        Cache::forget('user_active_tokens:' . $user->id);

        $token = $user->createToken('auth_token')->plainTextToken;
        
        // Cache new token in Redis for faster validation
        $this->tokenRepository->storeToken($token, $user);

        return response()->json([
            'accessToken'      => $token,
            'userData'         => $user,
            'userAbilityRules' => [['action' => 'manage', 'subject' => 'all']],
        ]);
    }

    /**
     * Logout (revoke current token).
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        
        if ($token) {
            $this->tokenRepository->revokeToken($token);
        }
        
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Return authenticated user data.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'userData'         => $request->user(),
            'userAbilityRules' => [['action' => 'manage', 'subject' => 'all']],
        ]);
    }
}
