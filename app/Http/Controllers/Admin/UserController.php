<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\Firebase\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->with('category');

        // Filter berdasarkan role jika ada parameter ?role=
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter berdasarkan kategori/jobdesk jika ada ?category_id=
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->appends($request->only(['role', 'category_id']));
        $categories = Category::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'categories'));
    }

    public function exportCsv(Request $request)
    {
        $query = User::query()->with('category');
        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $rows = $query->orderByDesc('created_at')->get();
        $role = $request->string('role', 'all');

        $headers = ['No', 'Nama', 'Email', 'Peran'];
        if ($role === 'admin') {
            $headers = array_merge($headers, ['Jobdesk']);
        } elseif ($role === 'agent') {
            $headers = array_merge($headers, ['Jobdesk', 'Level']);
        } elseif ($role === 'customer') {
            $headers = array_merge($headers, ['Kategori', 'Agent']);
        }

        $lines = [];
        $lines[] = implode(',', array_map(fn($h) => '"' . str_replace('"', '""', $h) . '"', $headers));
        $i = 1;
        foreach ($rows as $u) {
            $base = [
                $i++,
                $u->name,
                $u->email,
                $u->role,
            ];
            if ($role === 'admin') {
                $base[] = $u->category->name ?? '-';
            } elseif ($role === 'agent') {
                $base[] = $u->category->name ?? '-';
                $base[] = $u->availability_status ?? '-';
            } elseif ($role === 'customer') {
                $base[] = $u->category->name ?? '-';
                $base[] = '-';
            }
            $lines[] = implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string)$v) . '"', $base));
        }

        $csv = implode("\r\n", $lines) . "\r\n";
        $fileName = 'users_' . ($role ?: 'all') . '_' . date('Ymd_His') . '.csv';
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }

    public function exportPdf(Request $request)
    {
        $query = User::query()->with('category');
        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        $users = $query->orderByDesc('created_at')->get();
        $role = (string) $request->string('role', 'all');

        return view('admin.users.export', compact('users', 'role'));
    }

    public function create(Request $request)
    {
        $role = $request->query('role');
        $categories = Category::orderBy('name')->get();
        return view('admin.users.create', compact('role', 'categories'));
    }

    public function store(Request $request, UserService $userService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,agent,customer',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if (in_array($data['role'], ['admin', 'agent'], true) && empty($data['category_id'])) {
            return back()->withErrors(['category_id' => 'Kategori/jobdesk wajib dipilih untuk Admin/Agent.'])->withInput();
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'category_id' => in_array($data['role'], ['admin', 'agent'], true) ? ($data['category_id'] ?? null) : null,
            'availability_status' => null,
        ]);

        // Write to Firestore (best-effort)
        try {
            $userService->createUser([
                'laravel_id' => (string)$user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'category_id' => (string) ($user->category_id ?? ''),
                'availability_status' => (string) ($user->availability_status ?? ''),
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
        $categories = Category::orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'categories'));
    }

    public function update(Request $request, User $user, UserService $userService)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:admin,agent,customer',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if (in_array($data['role'], ['admin', 'agent'], true) && empty($data['category_id'])) {
            return back()->withErrors(['category_id' => 'Kategori/jobdesk wajib dipilih untuk Admin/Agent.'])->withInput();
        }

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'category_id' => in_array($data['role'], ['admin', 'agent'], true) ? ($data['category_id'] ?? null) : null,
            'availability_status' => null,
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
                'category_id' => (string) ($user->category_id ?? ''),
                'availability_status' => (string) ($user->availability_status ?? ''),
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
