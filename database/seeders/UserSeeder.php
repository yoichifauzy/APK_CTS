<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use App\Services\Firebase\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultCategoryId = null;
        try {
            $defaultCategoryId = Category::query()->orderBy('id')->value('id');
        } catch (\Throwable $e) {
            $defaultCategoryId = null;
        }

        $seedUsers = [
            [
                'email' => 'superadmin@example.com',
                'name' => 'Super Admin',
                'role' => 'super_admin',
                'password' => 'password',
            ],
            [
                'email' => 'admin@example.com',
                'name' => 'Admin',
                'role' => 'admin',
                'password' => 'password',
                'category_id' => $defaultCategoryId,
            ],
            [
                'email' => 'agent1@example.com',
                'name' => 'Agent 1',
                'role' => 'agent',
                'password' => 'password',
                'category_id' => $defaultCategoryId,
                'availability_status' => 'junior',
            ],
            [
                'email' => 'customer1@example.com',
                'name' => 'Customer 1',
                'role' => 'customer',
                'password' => 'password',
            ],
        ];

        $firebase = new UserService();

        foreach ($seedUsers as $seed) {
            // Step 1: Create user in Laravel DB
            $user = User::updateOrCreate(
                ['email' => $seed['email']],
                [
                    'name' => $seed['name'],
                    'password' => Hash::make($seed['password']),
                    'role' => $seed['role'],
                    'category_id' => $seed['category_id'] ?? null,
                    'availability_status' => $seed['availability_status'] ?? null,
                    'email_verified_at' => now(),
                ]
            );

            // Step 2: Sync to Firestore (best-effort, even if Firebase Auth unavailable)
            try {
                // Check if Firebase Auth is available by attempting create/upsert
                if (config('firebase.firestore_enabled')) {
                    // Try to create user in Firebase Auth (password stored securely)
                    // If user already exists in Firebase, we'll just update profile in Firestore
                    try {
                        $firebase->createUserWithPassword(
                            $user->email,
                            $seed['password'],
                            [
                                'name' => $user->name,
                                'role' => $user->role,
                                'category_id' => (string) ($user->category_id ?? ''),
                                'availability_status' => (string) ($user->availability_status ?? ''),
                                'level' => (string) ($user->availability_status ?? ''),
                                'laravel_id' => (string) $user->id,
                            ]
                        );
                        logger()->info("Created Firebase Auth user: {$user->email}");
                    } catch (\Throwable $e) {
                        // Firebase user might already exist, so fallback to upsert profile only
                        if (str_contains($e->getMessage(), 'EMAIL_EXISTS')) {
                            logger()->info("Firebase user already exists: {$user->email}, updating profile only");
                        }

                        $firebase->upsertByLaravelId((string) $user->id, [
                            'laravel_id' => (string) $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role,
                            'category_id' => (string) ($user->category_id ?? ''),
                            'availability_status' => (string) ($user->availability_status ?? ''),
                            'level' => (string) ($user->availability_status ?? ''),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                logger()->error('Failed to sync seeded user to Firebase: ' . $e->getMessage());
            }
        }
    }
}
