<?php

namespace App\Http\Controllers\Auth;

use App\Auth\RedisTokenRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    protected RedisTokenRepository $tokenRepository;

    public function __construct(RedisTokenRepository $tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;
    }

    /**
     * Update the authenticated user's password.
     */
    public function update(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        
        $user->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        // Revoke all other tokens so other sessions must re-login
        $user->tokens()
            ->where('id', '!=', $user->currentAccessToken()->id)
            ->delete();
        
        // Clear all user tokens from Redis cache
        Cache::forget('user_active_tokens:' . $user->id);

        return response()->json([
            'message' => 'Password changed successfully.',
        ]);
    }
}
