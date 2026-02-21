<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'accessToken'      => $token,
            'userData'         => $user,
            'userAbilityRules' => [['action' => 'manage', 'subject' => 'all']],
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

        // Delete previous tokens (single session)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

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
