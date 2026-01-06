<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Firebase\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    /**
     * Generate Firebase ID Token for API authentication.
     *
     * ENDPOINT: POST /api/token
     * BODY: { "email": "admin@example.com", "password": "password" }
     * RETURNS: { "id_token": "...", "refresh_token": "...", "uid": "...", "expires_in": 3600 }
     *
     * USAGE:
     * 1. Call this endpoint with email+password
     * 2. Get back id_token (valid for ~1 hour)
     * 3. Use token in subsequent API calls: Authorization: Bearer <id_token>
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Step 1: Validate input
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Step 2: Verify user exists in Laravel DB
        $user = User::query()->where('email', $request->email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'Email tidak ditemukan',
            ]);
        }

        // Step 3: Attempt to generate token from Firebase
        try {
            $userService = new UserService();
            $tokenData = $userService->generateToken(
                $request->email,
                $request->password,
                saveAudit: true
            );

            logger()->info("Token issued for user: {$user->email} (id={$user->id})");

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'id_token' => $tokenData['id_token'],
                    'refresh_token' => $tokenData['refresh_token'],
                    'token_type' => $tokenData['token_type'],
                    'expires_in' => $tokenData['expires_in'],
                ],
            ]);
        } catch (\Throwable $e) {
            logger()->error('Token generation failed: ' . $e->getMessage());

            throw ValidationException::withMessages([
                'auth' => 'Email atau password salah, atau Firebase Auth belum dikonfigurasi',
            ]);
        }
    }

    /**
     * Verify current token validity.
     *
     * ENDPOINT: GET /api/token/verify (requires token auth)
     * HEADER: Authorization: Bearer <id_token>
     * RETURNS: { "valid": true, "uid": "...", "email": "...", "expires_at": "2026-01-05T..." }
     *
     * Middleware TokenAuth will validate the token before this method runs.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        // Token claims were already validated by TokenAuth middleware
        $claims = $request->get('token_claims');

        return response()->json([
            'success' => true,
            'data' => [
                'valid' => true,
                'uid' => $claims['uid'] ?? null,
                'email' => $claims['email'] ?? null,
                'expires_at' => $claims['expires_at'] ?? null,
                'issued_at' => $claims['issued_at'] ?? null,
            ],
        ]);
    }

    /**
     * Revoke token (invalidate by UID).
     *
     * ENDPOINT: POST /api/token/revoke (requires token auth)
     * HEADER: Authorization: Bearer <id_token>
     * RETURNS: { "success": true, "message": "Token revoked" }
     *
     * Note: Firebase doesn't have built-in token revocation. This logs the revocation
     * for audit purposes. Clients should discard the token.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function revoke(Request $request)
    {
        $uid = $request->get('token_uid');
        $email = $request->get('token_email');

        logger()->info("Token revoked for user: {$email} (uid={$uid})");

        return response()->json([
            'success' => true,
            'message' => 'Token telah di-revoke. Gunakan email+password untuk login kembali.',
        ]);
    }
}
