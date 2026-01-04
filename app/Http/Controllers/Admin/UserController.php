<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\Firebase\UserService;
use Illuminate\Support\Facades\Hash;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        // Filter berdasarkan role jika ada parameter ?role=
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('name')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create(Request $request)
    {
        $role = $request->query('role');
        return view('admin.users.create', compact('role'));
    }

    public function store(Request $request, UserService $userService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,agent,customer',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        // Write to Firestore (best-effort)
        try {
            $userService->createUser([
                'laravel_id' => (string)$user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]);
        } catch (\Throwable $e) {
            // Do not fail the whole request if Firestore is unavailable — log and continue
            logger()->error('Failed to write user to Firestore: ' . $e->getMessage());
        }

        // Redirect ke halaman manajemen sesuai role yang baru dibuat
        return redirect()->route('admin.users.index', ['role' => $user->role])
            ->with('success', 'User berhasil dibuat');
    }

    public function show(User $user, UserService $userService)
    {
        $firestoreUser = null;
        try {
            $firestoreUser = $userService->findByLaravelId((string)$user->id);
        } catch (Throwable $e) {
            logger()->error('Firestore read failed: ' . $e->getMessage());
        }

        return view('admin.users.show', compact('user', 'firestoreUser'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user, UserService $userService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:admin,agent,customer',
        ]);

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        // Update Firestore user (best-effort)
        try {
            $userService->updateByLaravelId((string)$user->id, [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]);
        } catch (Throwable $e) {
            logger()->error('Failed to update user in Firestore: ' . $e->getMessage());
        }

        // Redirect back to the management list for the (possibly updated) role
        return redirect()->route('admin.users.index', ['role' => $data['role']])->with('success', 'User berhasil diperbarui');
    }

    public function destroy(User $user, UserService $userService)
    {
        // Delete Firestore doc (best-effort)
        try {
            $userService->deleteByLaravelId((string)$user->id);
        } catch (Throwable $e) {
            logger()->error('Failed to delete user in Firestore: ' . $e->getMessage());
        }

        $user->delete();

        // Redirect back to the management list for the same role as the deleted user
        return redirect()->route('admin.users.index', ['role' => $user->role])->with('success', 'User berhasil dihapus');
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:admin,agent,customer',
        ]);

        $user->update([
            'role' => $request->string('role')->toString(),
        ]);

        return back()->with('success', 'Role user berhasil diperbarui');
    }
}
