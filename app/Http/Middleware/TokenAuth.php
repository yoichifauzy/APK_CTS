<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Firebase\UserService;

class TokenAuth
{
    /**
     * Handle an incoming request.
     *
     * Validates Firebase ID Token from Authorization header.
     * Expected format: Authorization: Bearer <id_token>
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Step 1: Extract token from Authorization header
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing or invalid Authorization header. Expected: Bearer <token>',
            ], 401);
        }

        $token = substr($authHeader, 7); // Remove "Bearer " prefix

        // Step 2: Verify token with Firebase
        try {
            $userService = new UserService();
            $claims = $userService->verifyToken($token);

            // Step 3: Store token claims in request for later use
            $request->merge([
                'token_uid' => $claims['uid'],
                'token_email' => $claims['email'],
                'token_claims' => $claims,
            ]);

            return $next($request);
        } catch (\Throwable $e) {
            logger()->warning('Token validation failed: ' . $e->getMessage());

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or expired token',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 401);
        }
    }
}
