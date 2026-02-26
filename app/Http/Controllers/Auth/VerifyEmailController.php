<?php

namespace App\Http\Controllers\Auth;

use App\Auth\EmailVerificationHandler;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class VerifyEmailController
{
    protected EmailVerificationHandler $verificationHandler;

    public function __construct(EmailVerificationHandler $verificationHandler)
    {
        $this->verificationHandler = $verificationHandler;
    }

    /**
     * Send verification code to email
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // Check if email is already verified
        $user = User::where('email', $validated['email'])->first();
        if ($user && $user->email_verified_at) {
            return response()->json([
                'message' => 'Email is already verified',
            ], 422);
        }

        // Rate limit: max 3 resends per 15 minutes
        if (! $this->verificationHandler->canResendCode($validated['email'])) {
            return response()->json([
                'message' => 'Too many verification requests. Please try again later.',
            ], 429);
        }

        // Generate and store code
        $code = $this->verificationHandler->generateAndStoreCode($validated['email']);

        // TODO: Send code via email (using Mail facade)
        // For development, we can log it or return in response
        \Log::info("Verification code for {$validated['email']}: {$code}");

        return response()->json([
            'message' => 'Verification code sent successfully',
            // Only for development/testing - remove in production
            'code' => $code,
        ]);
    }

    /**
     * Verify email with code
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        // Verify code
        if (! $this->verificationHandler->verifyCode($validated['email'], $validated['code'])) {
            return response()->json([
                'errors' => [
                    'code' => ['Invalid or expired verification code'],
                ],
            ], 422);
        }

        // Find user and mark email as verified
        $user = User::where('email', $validated['email'])->first();
        if (! $user) {
            return response()->json([
                'errors' => [
                    'email' => ['User not found'],
                ],
            ], 422);
        }

        // Mark email as verified
        $user->update(['email_verified_at' => now()]);

        return response()->json([
            'message' => 'Email verified successfully',
        ]);
    }
}
