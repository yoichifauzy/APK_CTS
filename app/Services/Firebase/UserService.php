<?php

namespace App\Services\Firebase;

use Google\Cloud\Core\Timestamp;
use Kreait\Firebase\Auth;

class UserService
{
    private $firestore;
    private $auth;

    public function __construct()
    {
        $this->firestore = null;
        $this->auth = null;

        // Keep construction safe for DI/controller usage.
        // When Firestore is disabled or not configured, methods will fail gracefully.
        if (!config('firebase.firestore_enabled')) {
            return;
        }

        try {
            $factory = FirebaseFactory::make();
            $this->firestore = $factory->createFirestore()->database();
            $this->auth = $factory->createAuth();
        } catch (\Throwable $e) {
            logger()->error('Firebase init failed: ' . $e->getMessage());
            $this->firestore = null;
            $this->auth = null;
        }
    }

    private function requireFirestore()
    {
        if ($this->firestore === null) {
            throw new \RuntimeException('Firestore is not available. Check FIREBASE_FIRESTORE_ENABLED and credentials.');
        }

        return $this->firestore;
    }

    private function requireAuth()
    {
        if ($this->auth === null) {
            throw new \RuntimeException('Firebase Auth is not available. Check FIREBASE_FIRESTORE_ENABLED and credentials.');
        }

        return $this->auth;
    }

    /**
     * Create a user in Firebase Auth + Firestore profile.
     *
     * SECURITY NOTE:
     * - Password is stored securely by Firebase Auth (never stored in Firestore)
     * - User profile (name, email, role) is stored in Firestore
     * - Firebase handles password hashing and security
     *
     * @param string $email - User email
     * @param string $password - User password (will be hashed by Firebase)
     * @param array<string, mixed> $profileData - Additional profile data (name, role, etc.)
     * @return array - ['uid' => firebase_uid, 'firestore_id' => firestore_doc_id]
     * @throws \Throwable
     */
    public function createUserWithPassword(string $email, string $password, array $profileData = []): array
    {
        $auth = $this->requireAuth();
        $firestore = $this->requireFirestore();

        try {
            // Step 1: Create user in Firebase Auth (password is hashed securely)
            $userRecord = $auth->createUserWithEmailAndPassword($email, $password);
            $uid = $userRecord['localId'];

            // Step 2: Create profile document in Firestore (WITHOUT password)
            $now = new Timestamp(new \DateTime());
            $firestoreData = array_merge($profileData, [
                'firebase_uid' => $uid,
                'email' => $email,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $ref = $firestore->collection('users')->add($firestoreData);
            $firestoreId = $ref->id();

            logger()->info("Firebase user created: uid=$uid, firestore_id=$firestoreId");

            return [
                'uid' => $uid,
                'firestore_id' => $firestoreId,
            ];
        } catch (\Throwable $e) {
            logger()->error('Firebase user creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a user document in Firestore `users` collection.
     * Returns the Firestore document id.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    public function createUser(array $data): string
    {
        $firestore = $this->requireFirestore();
        $now = new Timestamp(new \DateTime());
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $ref = $firestore->collection('users')->add($data);
        return $ref->id();
    }

    /**
     * Upsert a Firestore user document in `users` collection by `laravel_id`.
     * - If exists: merge update
     * - If not exists: create new
     * Returns true on success, false otherwise.
     *
     * @param string $laravelId
     * @param array<string, mixed> $data
     */
    public function upsertByLaravelId(string $laravelId, array $data): bool
    {
        $firestore = $this->requireFirestore();

        $laravelId = (string) $laravelId;
        $now = new Timestamp(new \DateTime());

        try {
            $found = $this->findByLaravelId($laravelId);
            if ($found) {
                $data['updated_at'] = $now;
                $firestore->collection('users')->document($found['id'])->set($data, ['merge' => true]);
                return true;
            }

            $data['laravel_id'] = $laravelId;
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            $firestore->collection('users')->add($data);
            return true;
        } catch (\Throwable $e) {
            logger()->error('Firestore upsert user failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a registration document in Firestore `register` collection.
     * Returns the Firestore document id.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    public function createRegister(array $data): string
    {
        $firestore = $this->requireFirestore();
        $now = new Timestamp(new \DateTime());
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $ref = $firestore->collection('register')->add($data);
        return $ref->id();
    }

    /**
     * Find a Firestore user document by the Laravel user id stored in `laravel_id`.
     * Returns an array with 'id' and 'data' keys or null when not found.
     *
     * @param string $laravelId
     * @return array|null
     */
    public function findByLaravelId(string $laravelId): ?array
    {
        $firestore = $this->requireFirestore();

        $documents = $firestore->collection('users')
            ->where('laravel_id', '=', $laravelId)
            ->limit(1)
            ->documents();

        foreach ($documents as $doc) {
            if ($doc->exists()) {
                return ['id' => $doc->id(), 'data' => $doc->data()];
            }
        }

        return null;
    }

    /**
     * Update Firestore user document by laravel id (best-effort).
     * Returns true on success, false otherwise.
     *
     * @param string $laravelId
     * @param array $data
     * @return bool
     */
    public function updateByLaravelId(string $laravelId, array $data): bool
    {
        $found = $this->findByLaravelId($laravelId);
        if (!$found) {
            return false;
        }

        try {
            $firestore = $this->requireFirestore();
            $firestore->collection('users')->document($found['id'])->set($data, ['merge' => true]);
            return true;
        } catch (\Throwable $e) {
            logger()->error('Firestore update user failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete Firestore user document by laravel id (best-effort).
     * Returns true when deleted or not found, false when an error occurred.
     *
     * @param string $laravelId
     * @return bool
     */
    public function deleteByLaravelId(string $laravelId): bool
    {
        $found = $this->findByLaravelId($laravelId);
        if (!$found) {
            return true;
        }

        try {
            $firestore = $this->requireFirestore();
            $firestore->collection('users')->document($found['id'])->delete();
            return true;
        } catch (\Throwable $e) {
            logger()->error('Firestore delete user failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate Firebase ID Token after successful login.
     * Optionally saves token info to Firestore for audit/tracking.
     *
     * FLOW:
     * 1. Verify email & password (returns Firebase credentials)
     * 2. Generate ID Token from Firebase
     * 3. Save token metadata to Firestore `tokens` collection (for audit)
     *
     * @param string $email
     * @param string $password
     * @param bool $saveAudit - Save token to Firestore for audit tracking
     * @return array - ['id_token' => token, 'refresh_token' => token, 'uid' => user_uid, 'expires_in' => seconds]
     * @throws \Throwable
     */
    public function generateToken(string $email, string $password, bool $saveAudit = true): array
    {
        $auth = $this->requireAuth();

        try {
            // Step 1: Verify email & password (exchange for tokens)
            $signInResponse = $auth->signInWithEmailAndPassword($email, $password);

            $idToken = $signInResponse['idToken'];
            $refreshToken = $signInResponse['refreshToken'];
            $uid = $signInResponse['localId'];
            $expiresIn = (int)($signInResponse['expiresIn'] ?? 3600);

            // Step 2: Optionally save token metadata to Firestore for audit
            if ($saveAudit && config('firebase.firestore_enabled')) {
                try {
                    $this->saveTokenAudit($uid, $email, $idToken, $expiresIn);
                } catch (\Throwable $e) {
                    logger()->warning('Failed to save token audit: ' . $e->getMessage());
                    // Don't fail the whole request, just log it
                }
            }

            logger()->info("Token generated for user: {$email}, uid={$uid}");

            return [
                'id_token' => $idToken,
                'refresh_token' => $refreshToken,
                'uid' => $uid,
                'expires_in' => $expiresIn,
                'token_type' => 'Bearer',
            ];
        } catch (\Throwable $e) {
            logger()->error('Token generation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save token metadata to Firestore for audit/tracking purposes.
     * Records when token was issued, for which user, and when it expires.
     *
     * @param string $uid - Firebase UID
     * @param string $email - User email
     * @param string $idToken - The ID token (only first 20 chars saved for security)
     * @param int $expiresIn - Token TTL in seconds
     * @return string - Firestore document ID
     */
    private function saveTokenAudit(string $uid, string $email, string $idToken, int $expiresIn): string
    {
        $firestore = $this->requireFirestore();

        $now = new Timestamp(new \DateTime());
        $expiresAt = new Timestamp(new \DateTime('+' . $expiresIn . ' seconds'));

        $tokenData = [
            'firebase_uid' => $uid,
            'email' => $email,
            'token_prefix' => substr($idToken, 0, 20) . '...', // Only store prefix for security
            'issued_at' => $now,
            'expires_at' => $expiresAt,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        $ref = $firestore->collection('tokens')->add($tokenData);
        return $ref->id();
    }

    /**
     * Verify Firebase ID Token from API request.
     * Decodes and validates the JWT token.
     *
     * @param string $idToken - The Bearer token from Authorization header
     * @return array - Token claims ['uid', 'email', 'email_verified', 'iss', 'aud', 'exp', etc]
     * @throws \Throwable
     */
    public function verifyToken(string $idToken): array
    {
        $auth = $this->requireAuth();

        try {
            // Firebase Auth verifies the JWT signature and expiration automatically
            // This will throw exception if token is invalid or expired
            $verifiedIdToken = $auth->verifyIdToken($idToken);

            // Extract claims by decoding JWT manually
            // JWT format: header.payload.signature
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                throw new \RuntimeException('Invalid token format');
            }

            // Decode payload (base64url encoded)
            $payload = $parts[1];
            $payload = str_replace(['-', '_'], ['+', '/'], $payload);
            $payload = base64_decode($payload);
            $claims = json_decode($payload, true) ?? [];

            $uid = $claims['sub'] ?? null;
            $email = $claims['email'] ?? null;
            $emailVerified = $claims['email_verified'] ?? false;
            $name = $claims['name'] ?? null;
            $issuedAt = $claims['iat'] ?? null;
            $expiresAt = $claims['exp'] ?? null;

            return [
                'uid' => $uid,
                'email' => $email,
                'email_verified' => $emailVerified,
                'name' => $name,
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'all_claims' => $claims,
            ];
        } catch (\Throwable $e) {
            logger()->warning('Token verification failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
