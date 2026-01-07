<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Services\Firebase\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TechnicianController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $admin */
        $admin = Auth::user();

        $query = User::query()
            ->where('role', 'agent')
            ->orderBy('name');

        if ($admin->category_id) {
            $query->where('category_id', $admin->category_id);
        } else {
            // Admin tanpa jobdesk/category tidak boleh melihat teknisi
            $query->whereRaw('1 = 0');
        }

        $level = $request->query('level');
        if (in_array($level, ['junior', 'intermediate', 'expert'], true)) {
            $query->where('availability_status', $level);
        }

        $users = $query->paginate(20)->withQueryString();

        return view('admin.technicians.index', compact('users', 'level'));
    }

    public function tracking(Request $request)
    {
        /** @var User $admin */
        $admin = Auth::user();

        $query = User::query()
            ->where('role', 'agent')
            ->orderBy('name');

        if ($admin->category_id) {
            $query->where('category_id', $admin->category_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%');
            });
        }

        $level = $request->query('level');
        if (in_array($level, ['junior', 'intermediate', 'expert'], true)) {
            $query->where('availability_status', $level);
        }

        $agents = $query->get();
        $agentIds = $agents->pluck('id')->all();

        // Current workload status per agent:
        // - If has any ticket status != closed => use latest updated ticket status
        // - Else treat as Open (belum ada kerjaan)
        $workStatus = [];
        if (count($agentIds) > 0) {
            $tickets = \App\Models\Ticket::query()
                ->whereIn('agent_id', $agentIds)
                ->where('status', '!=', 'closed')
                ->orderBy('updated_at', 'desc')
                ->get(['agent_id', 'status']);

            foreach ($tickets as $t) {
                $aid = (int) $t->agent_id;
                if (!isset($workStatus[$aid])) {
                    $workStatus[$aid] = (string) $t->status;
                }
            }
        }

        $status = trim((string) $request->query('status', ''));
        if (in_array($status, ['open', 'assigned', 'in_progress', 'resolved'], true)) {
            $agents = $agents->filter(function ($agent) use ($workStatus, $status) {
                $cur = $workStatus[$agent->id] ?? 'open';
                return (string) $cur === (string) $status;
            })->values();
        }

        return view('admin.technicians.tracking', compact('agents', 'workStatus', 'q', 'level', 'status'));
    }

    public function create()
    {
        return view('admin.technicians.create');
    }

    public function store(Request $request, UserService $userService)
    {
        /** @var User $admin */
        $admin = Auth::user();

        if (!$admin->category_id) {
            return redirect()->route('admin.technicians.index')
                ->with('error', 'Admin belum memiliki jobdesk/kategori. Hubungi Super Admin.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'availability_status' => 'nullable|in:junior,intermediate,expert',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'agent',
            'category_id' => $admin->category_id,
            'availability_status' => $data['availability_status'] ?? 'junior',
        ]);

        // Write to Firestore (best-effort)
        try {
            $userService->createUser([
                'laravel_id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'category_id' => (string) ($user->category_id ?? ''),
                'availability_status' => (string) ($user->availability_status ?? ''),
                'level' => (string) ($user->availability_status ?? ''),
            ]);
        } catch (\Throwable $e) {
            logger()->error('Failed to write technician to Firestore: ' . $e->getMessage());
        }

        return redirect()->route('admin.technicians.index')->with('success', 'Teknisi berhasil dibuat');
    }

    public function edit(User $user)
    {
        /** @var User $admin */
        $admin = Auth::user();

        if ($user->role !== 'agent') {
            abort(404);
        }

        if (!$admin->category_id || (int) $user->category_id !== (int) $admin->category_id) {
            abort(403);
        }

        return view('admin.technicians.edit', compact('user'));
    }

    public function update(Request $request, User $user, UserService $userService)
    {
        /** @var User $admin */
        $admin = Auth::user();

        if ($user->role !== 'agent') {
            abort(404);
        }

        if (!$admin->category_id || (int) $user->category_id !== (int) $admin->category_id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'availability_status' => 'nullable|in:junior,intermediate,expert',
        ]);

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'availability_status' => $data['availability_status'] ?? $user->availability_status,
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        // Update Firestore user (best-effort)
        try {
            $userService->updateByLaravelId((string) $user->id, [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'category_id' => (string) ($user->category_id ?? ''),
                'availability_status' => (string) ($user->availability_status ?? ''),
                'level' => (string) ($user->availability_status ?? ''),
            ]);
        } catch (\Throwable $e) {
            logger()->error('Failed to update technician in Firestore: ' . $e->getMessage());
        }

        return redirect()->route('admin.technicians.index')->with('success', 'Teknisi berhasil diperbarui');
    }

    public function destroy(User $user, UserService $userService)
    {
        /** @var User $admin */
        $admin = Auth::user();

        if ($user->role !== 'agent') {
            abort(404);
        }

        if (!$admin->category_id || (int) $user->category_id !== (int) $admin->category_id) {
            abort(403);
        }

        try {
            $userService->deleteByLaravelId((string) $user->id);
        } catch (\Throwable $e) {
            logger()->error('Failed to delete technician in Firestore: ' . $e->getMessage());
        }

        $user->delete();

        return redirect()->route('admin.technicians.index')->with('success', 'Teknisi berhasil dihapus');
    }
}
