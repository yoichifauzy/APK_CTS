<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TokenController;
use App\Http\Middleware\TokenAuth;

/**
 * =================================================================
 * API ROUTES - Token-based authentication for API endpoints
 * =================================================================
 *
 * ALUR AUTHENTICATION:
 * 1. Client call POST /api/token dengan email+password
 * 2. Server generate Firebase ID Token (valid ~1 jam)
 * 3. Client simpan token di local storage / session
 * 4. Client kirim token di setiap request: Authorization: Bearer <token>
 * 5. Middleware TokenAuth validate token sebelum akses endpoint
 *
 * TOKEN STORAGE (optional):
 * - Setiap token yang di-generate disimpan di Firestore `tokens` collection
 * - Untuk audit trail, tracking user sessions, IP address, user agent
 * - Bisa digunakan untuk revoke token (manual logout dari semua device)
 */

// Public API routes (no authentication required)
Route::post('token', [TokenController::class, 'store'])->name('api.token.store');

// Protected API routes (requires token authentication)
Route::middleware(['token-auth'])->group(function () {
    /**
     * Verify Token Endpoint
     * GET /api/token/verify
     *
     * Verify if current token is still valid.
     * Header: Authorization: Bearer <token>
     * Response: { "valid": true, "uid": "...", "email": "...", "expires_at": "..." }
     */
    Route::get('token/verify', [TokenController::class, 'verify'])->name('api.token.verify');

    /**
     * Revoke Token Endpoint
     * POST /api/token/revoke
     *
     * Invalidate/revoke the current token.
     * Header: Authorization: Bearer <token>
     * Response: { "success": true, "message": "Token telah di-revoke" }
     *
     * Note: Clients should discard token setelah revoke.
     */
    Route::post('token/revoke', [TokenController::class, 'revoke'])->name('api.token.revoke');
});
