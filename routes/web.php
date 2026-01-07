<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketDiscussionController;
use App\Http\Controllers\TicketBarcodeController;
use App\Services\Firebase\FirebaseFactory;
use Illuminate\Support\Facades\Auth;

/**
 * =================================================================
 * ROUTES WEB.PHP - Definisi semua route/URL aplikasi
 * =================================================================
 *
 * PENJELASAN KE DOSEN:
 * File ini adalah "peta jalan" aplikasi yang mendefinisikan:
 * - URL apa saja yang tersedia
 * - Controller mana yang handle URL tersebut
 * - Middleware apa yang melindungi route (auth, role, dll)
 *
 * MIDDLEWARE PENTING:
 * - auth: User harus login dulu
 * - role:admin,agent: Hanya admin dan agent yang bisa akses
 * - verified: Email harus terverifikasi (optional)
 * - signed: URL harus valid (untuk download file)
 *
 * STRUKTUR ROUTE:
 * 1. Public routes (landing page)
 * 2. Auth routes (login, register) - di auth.php
 * 3. Dashboard route (setelah login)
 * 4. Admin-only routes (kelola user, kategori)
 * 5. Ticket routes (semua role bisa akses, tapi dengan permission berbeda)
 */

// =================================================================
// PUBLIC ROUTES - Bisa diakses tanpa login
// =================================================================

/**
 * Landing Page / Home
 * Route: GET /
 * Controller: Inline closure (return view langsung)
 * View: resources/views/welcome.blade.php
 */
Route::get('/', function () {
    return view('welcome');
})->name('home');

// =================================================================
// DASHBOARD ROUTE - Butuh login (middleware auth)
// =================================================================

/**
 * Dashboard - Halaman utama setelah login
 * Route: GET /dashboard
 * Middleware: auth, verified
 *
 * LOGIC DASHBOARD:
 * 1. Ambil data user yang sedang login
 * 2. Hitung statistik tickets berdasarkan role user:
 *    - Admin: Lihat SEMUA tickets
 *    - Agent: Lihat tickets yang DI-ASSIGN ke dia (exclude resolved & closed)
 *    - Customer: Lihat tickets MILIK dia saja
 * 3. Tampilkan dashboard dengan card statistik
 *
 * STATISTIK YANG DIHITUNG:
 * - open: Jumlah ticket baru (belum ditangani)
 * - assigned: Jumlah ticket yang sudah di-assign ke agent
 * - in_progress: Jumlah ticket yang sedang dikerjakan
 * - resolved: Jumlah ticket yang sudah selesai
 * - closed: Jumlah ticket yang sudah ditutup/arsip
 * - total: Total semua tickets
 * - unassigned (admin only): Ticket yang belum di-assign
 */
Route::get('/dashboard', function () {
    // STEP 1: Ambil data user yang login
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    if (!$user) {
        abort(401);
    }
    $role = $user->role;

    // STEP 2: Inisialisasi array statistik
    // Array ini akan diisi dengan jumlah tickets berdasarkan status
    $stats = [
        'open' => 0,
        'in_progress' => 0,
        'resolved' => 0,
        'closed' => 0,
        'total' => 0
    ];

    $agentStatusLabels = collect();
    $agentStatusValues = collect();
    $agentPriorityLabels = collect();
    $agentPriorityValues = collect();

    $customerStatusLabels = collect();
    $customerStatusValues = collect();
    $customerPriorityLabels = collect();
    $customerPriorityValues = collect();

    // STEP 3: Hitung statistik berdasarkan role user

    if ($role === 'super_admin') {
        // SUPER ADMIN: Lihat SEMUA tickets
        $stats['open'] = \App\Models\Ticket::where('status', 'open')->count();
        $stats['assigned'] = \App\Models\Ticket::where('status', 'assigned')->count();
        $stats['in_progress'] = \App\Models\Ticket::where('status', 'in_progress')->count();
        $stats['resolved'] = \App\Models\Ticket::where('status', 'resolved')->count();
        $stats['closed'] = \App\Models\Ticket::where('status', 'closed')->count();
        $stats['total'] = \App\Models\Ticket::count();
    } elseif ($role === 'admin') {
        // ADMIN: hanya ticket sesuai kategori/jobdesk
        $base = \App\Models\Ticket::query();
        if ($user->category_id) {
            $base->where('category_id', $user->category_id);
        } else {
            $base->whereRaw('1 = 0');
        }

        $stats['open'] = (clone $base)->where('status', 'open')->count();
        $stats['assigned'] = (clone $base)->where('status', 'assigned')->count();
        $stats['in_progress'] = (clone $base)->where('status', 'in_progress')->count();
        $stats['resolved'] = (clone $base)->where('status', 'resolved')->count();
        $stats['closed'] = (clone $base)->where('status', 'closed')->count();
        $stats['total'] = (clone $base)->count();

        // Statistik khusus admin: tickets yang belum di-assign (Open saja)
        // (Di sistem ini, ticket Open semestinya belum memiliki agent)
        $stats['unassigned'] = (clone $base)
            ->where('status', 'open')
            ->whereNull('agent_id')
            ->count();
    } elseif ($role === 'agent') {
        // AGENT: Hanya lihat tickets yang DI-ASSIGN ke dia
        // Exclude tickets yang sudah resolved atau closed (sudah selesai dikerjakan)
        $stats['open'] = \App\Models\Ticket::where('agent_id', $user->id)->where('status', 'open')->count();
        $stats['assigned'] = \App\Models\Ticket::where('agent_id', $user->id)->where('status', 'assigned')->count();
        $stats['in_progress'] = \App\Models\Ticket::where('agent_id', $user->id)->where('status', 'in_progress')->count();
        $stats['resolved'] = \App\Models\Ticket::where('agent_id', $user->id)->where('status', 'resolved')->count();
        $stats['closed'] = \App\Models\Ticket::where('agent_id', $user->id)->where('status', 'closed')->count();

        // Total untuk agent: hanya yang belum selesai (exclude resolved & closed)
        $stats['total'] = \App\Models\Ticket::where('agent_id', $user->id)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();

        $agentStatusLabels = collect(['open', 'assigned', 'in_progress', 'resolved', 'closed']);
        $agentStatusValues = collect([
            (int) $stats['open'],
            (int) $stats['assigned'],
            (int) $stats['in_progress'],
            (int) $stats['resolved'],
            (int) $stats['closed'],
        ]);

        $prioRows = \App\Models\Ticket::query()
            ->where('agent_id', $user->id)
            ->whereNotNull('priority')
            ->select('priority', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->get();
        $prioMap = $prioRows->pluck('total', 'priority')->map(fn($v) => (int) $v);
        $priorityOrder = ['low', 'medium', 'high'];
        $agentPriorityLabels = collect($priorityOrder);
        $agentPriorityValues = collect(array_map(fn($p) => (int) ($prioMap[$p] ?? 0), $priorityOrder));
    } else {
        // CUSTOMER: Hanya lihat tickets MILIK dia (filter by customer_id)
        $stats['open'] = \App\Models\Ticket::where('customer_id', $user->id)->where('status', 'open')->count();
        $stats['assigned'] = \App\Models\Ticket::where('customer_id', $user->id)->where('status', 'assigned')->count();
        $stats['in_progress'] = \App\Models\Ticket::where('customer_id', $user->id)->where('status', 'in_progress')->count();
        $stats['resolved'] = \App\Models\Ticket::where('customer_id', $user->id)->where('status', 'resolved')->count();
        $stats['closed'] = \App\Models\Ticket::where('customer_id', $user->id)->where('status', 'closed')->count();
        $stats['total'] = \App\Models\Ticket::where('customer_id', $user->id)->count();

        $customerStatusLabels = collect(['open', 'assigned', 'in_progress', 'resolved', 'closed']);
        $customerStatusValues = collect([
            (int) $stats['open'],
            (int) $stats['assigned'],
            (int) $stats['in_progress'],
            (int) $stats['resolved'],
            (int) $stats['closed'],
        ]);

        $prioRows = \App\Models\Ticket::query()
            ->where('customer_id', $user->id)
            ->whereNotNull('priority')
            ->select('priority', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->get();
        $prioMap = $prioRows->pluck('total', 'priority')->map(fn($v) => (int) $v);
        $priorityOrder = ['low', 'medium', 'high'];
        $customerPriorityLabels = collect($priorityOrder);
        $customerPriorityValues = collect(array_map(fn($p) => (int) ($prioMap[$p] ?? 0), $priorityOrder));
    }

    // =========================
    // Admin Charts (Jobdesk)
    // =========================
    $adminTicketStatusLabels = collect();
    $adminTicketStatusValues = collect();
    $adminTicketPriorityLabels = collect();
    $adminTicketPriorityValues = collect();
    $adminTechnicianStatusLabels = collect();
    $adminTechnicianStatusValues = collect();
    $adminTechnicianLevelLabels = collect();
    $adminTechnicianLevelValues = collect();

    if ($role === 'admin') {
        $baseTickets = \App\Models\Ticket::query();
        if ($user->category_id) {
            $baseTickets->where('category_id', $user->category_id);
        } else {
            $baseTickets->whereRaw('1 = 0');
        }

        // 1) Bar: ticket status counts
        $adminTicketStatusLabels = collect(['open', 'assigned', 'in_progress', 'resolved', 'closed']);
        $adminTicketStatusValues = collect([
            (int) ($stats['open'] ?? 0),
            (int) ($stats['assigned'] ?? 0),
            (int) ($stats['in_progress'] ?? 0),
            (int) ($stats['resolved'] ?? 0),
            (int) ($stats['closed'] ?? 0),
        ]);

        // 2) Line: ticket priority counts (scoped)
        $prioRows = (clone $baseTickets)
            ->whereNotNull('priority')
            ->select('priority', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->get();
        $prioMap = $prioRows->pluck('total', 'priority')->map(fn($v) => (int) $v);
        $priorityOrder = ['low', 'medium', 'high'];
        $adminTicketPriorityLabels = collect($priorityOrder);
        $adminTicketPriorityValues = collect(array_map(fn($p) => (int) ($prioMap[$p] ?? 0), $priorityOrder));

        // 3) Donut: technician workload status (idle / assigned / in_progress / resolved)
        $agents = \App\Models\User::query()
            ->where('role', 'agent')
            ->when($user->category_id, fn($q) => $q->where('category_id', $user->category_id))
            ->select('id')
            ->get();
        $agentIds = $agents->pluck('id')->values();

        $statusCounts = [
            'idle' => 0,
            'assigned' => 0,
            'in_progress' => 0,
            'resolved' => 0,
        ];

        if ($agentIds->isEmpty()) {
            // keep zeros
        } else {
            $latestByAgent = [];
            $rows = \App\Models\Ticket::query()
                ->whereIn('agent_id', $agentIds)
                ->whereNotNull('agent_id')
                ->whereIn('status', ['assigned', 'in_progress', 'resolved'])
                ->orderByDesc('updated_at')
                ->select(['agent_id', 'status', 'updated_at'])
                ->get();

            foreach ($rows as $row) {
                $aid = (int) $row->agent_id;
                if (!isset($latestByAgent[$aid])) {
                    $latestByAgent[$aid] = (string) $row->status;
                }
            }

            foreach ($agentIds as $aid) {
                $st = $latestByAgent[(int) $aid] ?? 'idle';
                if (!isset($statusCounts[$st])) {
                    $statusCounts[$st] = 0;
                }
                $statusCounts[$st]++;
            }
        }

        $adminTechnicianStatusLabels = collect(['idle', 'assigned', 'in_progress', 'resolved']);
        $adminTechnicianStatusValues = collect([
            (int) ($statusCounts['idle'] ?? 0),
            (int) ($statusCounts['assigned'] ?? 0),
            (int) ($statusCounts['in_progress'] ?? 0),
            (int) ($statusCounts['resolved'] ?? 0),
        ]);

        // 4) Pie: technician level counts (junior/intermediate/expert)
        $levelRows = \App\Models\User::query()
            ->where('role', 'agent')
            ->when($user->category_id, fn($q) => $q->where('category_id', $user->category_id))
            ->selectRaw("COALESCE(availability_status, 'junior') as level, COUNT(*) as total")
            ->groupBy('level')
            ->get();
        $levelMap = $levelRows->pluck('total', 'level')->map(fn($v) => (int) $v);
        $levelOrder = ['junior', 'intermediate', 'expert'];
        $adminTechnicianLevelLabels = collect($levelOrder);
        $adminTechnicianLevelValues = collect(array_map(fn($l) => (int) ($levelMap[$l] ?? 0), $levelOrder));
    }

    // Grafik: jumlah user per role
    $userCounts = [
        'admin' => \App\Models\User::where('role', 'admin')->count(),
        'agent' => \App\Models\User::where('role', 'agent')->count(),
        'customer' => \App\Models\User::where('role', 'customer')->count(),
    ];

    // Super Admin charts (admin/agent totals and per jobdesk)
    $adminTotal = null;
    $agentTotal = null;
    $adminJobdeskLabels = collect();
    $adminJobdeskValues = collect();
    $agentJobdeskLabels = collect();
    $agentJobdeskValues = collect();

    if ($role === 'super_admin') {
        $adminTotal = (int) \App\Models\User::where('role', 'admin')->count();
        $agentTotal = (int) \App\Models\User::where('role', 'agent')->count();

        $adminByJobdesk = \App\Models\User::query()
            ->where('users.role', 'admin')
            ->leftJoin('categories', 'users.category_id', '=', 'categories.id')
            ->selectRaw("COALESCE(categories.name, '-') as jobdesk, COUNT(*) as total")
            ->groupBy('jobdesk')
            ->orderByDesc('total')
            ->get();

        $agentByJobdesk = \App\Models\User::query()
            ->where('users.role', 'agent')
            ->leftJoin('categories', 'users.category_id', '=', 'categories.id')
            ->selectRaw("COALESCE(categories.name, '-') as jobdesk, COUNT(*) as total")
            ->groupBy('jobdesk')
            ->orderByDesc('total')
            ->get();

        $adminJobdeskLabels = $adminByJobdesk->pluck('jobdesk')->values();
        $adminJobdeskValues = $adminByJobdesk->pluck('total')->map(fn($v) => (int) $v)->values();
        $agentJobdeskLabels = $agentByJobdesk->pluck('jobdesk')->values();
        $agentJobdeskValues = $agentByJobdesk->pluck('total')->map(fn($v) => (int) $v)->values();
    }

    // Grafik: kategori yang paling sering dipilih customer (berdasarkan tickets)
    // NOTE: Kolom di DB adalah `category_id`, jadi perlu join ke tabel `categories`.
    $ticketsForCategory = \App\Models\Ticket::query();
    if ($role === 'admin') {
        if ($user->category_id) {
            $ticketsForCategory->where('tickets.category_id', $user->category_id);
        } else {
            $ticketsForCategory->whereRaw('1 = 0');
        }
    } elseif ($role === 'agent') {
        $ticketsForCategory->where('agent_id', $user->id);
    } elseif ($role !== 'super_admin') {
        $ticketsForCategory->where('customer_id', $user->id);
    }

    $topCategories = $ticketsForCategory
        ->whereNotNull('tickets.category_id')
        ->join('categories', 'tickets.category_id', '=', 'categories.id')
        ->select('categories.name as category', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
        ->groupBy('categories.name')
        ->orderByDesc('total')
        ->limit(5)
        ->get();

    $categoryLabels = $topCategories->pluck('category')->values();
    $categoryValues = $topCategories->pluck('total')->map(fn($v) => (int) $v)->values();

    // =============================================================
    // Floating notification: new incoming tickets (admin/agent)
    // =============================================================
    if (in_array($role, ['admin', 'agent', 'customer'], true)) {
        $sessionKey = 'dashboard_last_seen_at:' . $role . ':' . ((int) ($user->id ?? 0));
        $lastSeenRaw = session($sessionKey);

        try {
            $since = $lastSeenRaw ? \Illuminate\Support\Carbon::parse($lastSeenRaw) : \Illuminate\Support\Carbon::now()->startOfDay();
        } catch (\Throwable $e) {
            $since = \Illuminate\Support\Carbon::now()->startOfDay();
        }

        $newCount = 0;
        if ($role === 'admin') {
            $q = \App\Models\Ticket::query();
            if ($user->category_id) {
                $q->where('category_id', $user->category_id);
            } else {
                $q->whereRaw('1 = 0');
            }
            // Admin notification: jumlah tiket Open & belum ditugaskan (selalu tampil jika ada)
            $newCount = (int) $q
                ->where('status', 'open')
                ->whereNull('agent_id')
                ->count();
        } elseif ($role === 'agent') {
            // Agent reminder: tiket yang belum selesai (assigned/in_progress)
            $newCount = (int) \App\Models\Ticket::query()
                ->where('agent_id', $user->id)
                ->whereIn('status', ['assigned', 'in_progress'])
                ->count();
        } elseif ($role === 'customer') {
            $newCount = (int) \App\Models\Ticket::query()
                ->where('customer_id', $user->id)
                ->where('status', 'open')
                ->count();
        }

        session([$sessionKey => \Illuminate\Support\Carbon::now()->toISOString()]);

        if ($newCount > 0) {
            session()->flash('floating_notification', [
                'type' => $role === 'customer' ? 'warning' : 'info',
                'sound' => $role === 'agent' ? 'status' : null,
                'title' => $role === 'customer' ? 'Tiket Belum Diproses' : 'Tiket Baru',
                'message' => $role === 'customer'
                    ? ('Ada ' . $newCount . ' tiket Anda masih Open')
                    : ($role === 'agent'
                        ? ('Ada ' . $newCount . ' tiket belum diselesaikan')
                        : ('Ada ' . $newCount . ' tiket open belum ditugaskan')
                    ),
            ]);
        }
    }

    return view('dashboard', compact(
        'stats',
        'userCounts',
        'categoryLabels',
        'categoryValues',
        'adminTotal',
        'agentTotal',
        'adminJobdeskLabels',
        'adminJobdeskValues',
        'agentJobdeskLabels',
        'agentJobdeskValues',
        'adminTicketStatusLabels',
        'adminTicketStatusValues',
        'adminTicketPriorityLabels',
        'adminTicketPriorityValues',
        'adminTechnicianStatusLabels',
        'adminTechnicianStatusValues',
        'adminTechnicianLevelLabels',
        'adminTechnicianLevelValues',
        'agentStatusLabels',
        'agentStatusValues',
        'agentPriorityLabels',
        'agentPriorityValues',
        'customerStatusLabels',
        'customerStatusValues',
        'customerPriorityLabels',
        'customerPriorityValues'
    ));
})->middleware(['auth', 'verified'])->name('dashboard');

// =================================================================
// FIREBASE TEST ROUTE - Admin/Agent only (untuk cek koneksi Firebase)
// =================================================================

/**
 * Firebase Connection Test
 * Route: GET /firebase-test
 * Middleware: auth, role:admin,agent
 *
 * FUNGSI:
 * Endpoint untuk test koneksi ke Firebase Firestore & Storage
 * Berguna saat troubleshooting atau verifikasi setup Firebase
 *
 * RETURN:
 * JSON response dengan status koneksi Firestore dan Storage
 */
Route::get('/firebase-test', function () {
    try {
        $factory = FirebaseFactory::make();

        // Test Firestore connection
        $firestoreDb = $factory->createFirestore()->database();
        $firestoreOk = true;
        $firestoreProbe = [];

        try {
            $documents = $firestoreDb->collection('tickets')->limit(1)->documents();
            foreach ($documents as $doc) {
                $firestoreProbe = [
                    'collection' => 'tickets',
                    'sample_exists' => $doc->exists(),
                    'sample_id' => $doc->id(),
                ];
                break;
            }
        } catch (Throwable $e) {
            $firestoreOk = false;
            $firestoreProbe = ['error' => $e->getMessage()];
        }

        // Test Storage connection
        $storageOk = true;
        $storageProbe = [];
        try {
            $bucket = $factory->createStorage()->getBucket(config('firebase.storage_bucket'));
            $storageProbe = [
                'bucket' => (string) config('firebase.storage_bucket'),
                'bucket_exists' => $bucket->exists(),
            ];
        } catch (Throwable $e) {
            $storageOk = false;
            $storageProbe = ['error' => $e->getMessage()];
        }

        return response()->json([
            'ok' => $firestoreOk && $storageOk,
            'project_id' => (string) config('firebase.project_id'),
            'firestore' => array_merge(['ok' => $firestoreOk], $firestoreProbe),
            'storage' => array_merge(['ok' => $storageOk], $storageProbe),
        ]);
    } catch (Throwable $e) {
        return response()->json([
            'ok' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
})->middleware(['auth', 'role:admin,agent'])->name('firebase.test');

// =================================================================
// PROFILE ROUTES - Kelola profile user
// =================================================================

/**
 * Profile Management Routes
 * Middleware: auth (harus login)
 *
 * Routes:
 * - GET /profile - Tampilkan form edit profile
 * - PATCH /profile - Update profile (name, email)
 * - DELETE /profile - Hapus akun
 */
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// =================================================================
// SUPER ADMIN ROUTES - Hanya super_admin yang bisa akses
// =================================================================

/**
 * Super Admin Routes Group
 * Middleware: auth, role:super_admin
 *
 * FITUR SUPER ADMIN:
 * 1. User Management - CRUD users (terutama admin)
 * 2. Category Management - CRUD kategori tickets
 *
 * PERMISSION:
 * Semua route di grup ini HANYA bisa diakses oleh user dengan role 'super_admin'
 * Jika non-admin coba akses, akan di-redirect atau error 403 Forbidden
 */
Route::middleware(['auth', 'role:super_admin'])->group(function () {

    // ===== USER MANAGEMENT ROUTES =====
    // Admin bisa kelola semua users (view, create, edit, delete, change role)
    Route::get('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('admin.users.show');
    Route::get('/admin/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::patch('/admin/users/{user}/role', [\App\Http\Controllers\Admin\UserController::class, 'updateRole'])->name('admin.users.updateRole');

    // Exports for users (CSV/PDF/Print)
    Route::get('/admin/users/export/csv', [\App\Http\Controllers\Admin\UserController::class, 'exportCsv'])->name('admin.users.exportCsv');
    Route::get('/admin/users/export/pdf', [\App\Http\Controllers\Admin\UserController::class, 'exportPdf'])->name('admin.users.exportPdf');

    // ===== CATEGORY MANAGEMENT ROUTES =====
    // Admin bisa kelola kategori tickets (create, edit, delete)
    Route::get('/admin/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('admin.categories.index');
    Route::get('/admin/categories/create', [\App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('admin.categories.create');
    Route::post('/admin/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('/admin/categories/{category}/edit', [\App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::put('/admin/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/admin/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('admin.categories.destroy');

    // Category exports
    Route::get('/admin/categories/export/csv', [\App\Http\Controllers\Admin\CategoryController::class, 'exportCsv'])->name('admin.categories.exportCsv');
    Route::get('/admin/categories/export/pdf', [\App\Http\Controllers\Admin\CategoryController::class, 'exportPdf'])->name('admin.categories.exportPdf');

    // Super Admin Ticket Report (placeholder)
    Route::get('/admin/ticket-reports', [\App\Http\Controllers\Admin\ReportController::class, 'superIndex'])->name('admin.ticketReports.index');
    Route::get('/admin/ticket-reports/export/csv', [\App\Http\Controllers\Admin\ReportController::class, 'superExportCsv'])->name('admin.ticketReports.exportCsv');
    Route::get('/admin/ticket-reports/export/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'superExportPdf'])->name('admin.ticketReports.exportPdf');
    Route::get('/admin/ticket-reports/print', [\App\Http\Controllers\Admin\ReportController::class, 'superPrint'])->name('admin.ticketReports.print');
});

// =================================================================
// ADMIN ROUTES - Admin hanya kelola teknisi/operator untuk jobdesk (kategori)
// =================================================================
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/technicians', [\App\Http\Controllers\Admin\TechnicianController::class, 'index'])->name('admin.technicians.index');
    Route::get('/admin/technicians/tracking', [\App\Http\Controllers\Admin\TechnicianController::class, 'tracking'])->name('admin.technicians.tracking');
    Route::get('/admin/technicians/create', [\App\Http\Controllers\Admin\TechnicianController::class, 'create'])->name('admin.technicians.create');
    Route::post('/admin/technicians', [\App\Http\Controllers\Admin\TechnicianController::class, 'store'])->name('admin.technicians.store');
    Route::get('/admin/technicians/{user}/edit', [\App\Http\Controllers\Admin\TechnicianController::class, 'edit'])->name('admin.technicians.edit');
    Route::put('/admin/technicians/{user}', [\App\Http\Controllers\Admin\TechnicianController::class, 'update'])->name('admin.technicians.update');
    Route::delete('/admin/technicians/{user}', [\App\Http\Controllers\Admin\TechnicianController::class, 'destroy'])->name('admin.technicians.destroy');

    // Laporan (Jobdesk)
    Route::get('/admin/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/export/csv', [\App\Http\Controllers\Admin\ReportController::class, 'exportCsv'])->name('admin.reports.exportCsv');
    Route::get('/admin/reports/export/pdf', [\App\Http\Controllers\Admin\ReportController::class, 'exportPdf'])->name('admin.reports.exportPdf');
    Route::get('/admin/reports/print', [\App\Http\Controllers\Admin\ReportController::class, 'print'])->name('admin.reports.print');
});

// =================================================================
// TICKET ROUTES - Semua role bisa akses (dengan permission berbeda)
// =================================================================

/**
 * Ticket Routes Group
 * Middleware: auth, role:customer,admin,agent
 *
 * PERMISSION PER ROLE:
 * - Customer: Buat ticket baru, lihat & edit tickets milik mereka, comment di tickets mereka
 * - Agent: Lihat tickets yang di-assign ke mereka, update status, tambah comment
 * - Admin: Lihat semua tickets, assign ke agent, close tickets
 *
 * ROUTES:
 * - Resource routes (index, create, store, show, edit, update, destroy)
 * - Custom routes (assign, updateStatus, comments, download)
 */
Route::middleware(['auth', 'role:customer,admin,agent'])->group(function () {

    // ===== DOWNLOAD ATTACHMENT ROUTE =====
    /**
     * Download File Attachment
     * Route: GET /tickets/{ticket}/attachments/download/{path}
     * Middleware: signed (URL harus valid & tidak expired)
     *
     * KEAMANAN:
     * - URL di-sign dengan expiry time (default 1 jam)
     * - Hanya owner ticket, assigned agent, atau admin yang bisa download
     * - File path di-encode base64 untuk keamanan
     */
    Route::get('/tickets/{ticket}/attachments/download/{path}', [\App\Http\Controllers\TicketController::class, 'downloadAttachment'])
        ->name('tickets.attachments.download')
        ->middleware('signed');

    // ===== TICKET RESOURCE ROUTES =====
    /**
     * Standard CRUD operations untuk Tickets
     * - GET /tickets - List semua tickets (filtered by role)
     * - GET /tickets/create - Form buat ticket baru
     * - POST /tickets - Simpan ticket baru
     * - GET /tickets/{id} - Detail ticket
     * - GET /tickets/{id}/edit - Form edit ticket
     * - PUT /tickets/{id} - Update ticket
     * - DELETE /tickets/{id} - Hapus ticket
     */
    Route::resource('tickets', TicketController::class);

    // ===== COMMENT ROUTE =====
    /**
     * Tambah komentar di ticket
     * Route: POST /tickets/{ticket}/comments
     * Semua role bisa comment (dengan permission check di controller)
     */
    Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');

    // ===== ADMIN-ONLY ACTIONS =====
    /**
     * Assign Ticket ke Agent
     * Route: POST /tickets/{ticket}/assign
     * Middleware: role:admin (hanya admin)
     *
     * FLOW:
     * 1. Admin pilih agent dari dropdown
     * 2. POST data agent_id
     * 3. Ticket status berubah jadi 'assigned'
     * 4. Agent bisa mulai kerjakan ticket
     */
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assignAgent'])
        ->middleware('role:admin,super_admin')
        ->name('tickets.assign');

    // ===== ADMIN/AGENT ACTIONS =====
    /**
     * Update Status Ticket
     * Route: POST /tickets/{ticket}/status
     * Middleware: role:admin,agent
     *
     * FLOW STATUS:
     * - Agent: bisa ubah ke 'in_progress' atau 'resolved'
     * - Admin: bisa ubah ke 'assigned' atau 'closed'
     *
     * BUSINESS RULES:
     * - Agent tidak bisa langsung close ticket (harus resolved dulu)
     * - Admin hanya bisa close ticket yang sudah resolved
     * - Tidak bisa kembali ke status 'open' setelah assigned
     */
    Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])
        ->middleware('role:admin,agent,super_admin')
        ->name('tickets.updateStatus');

    // Customer can close their own ticket
    Route::post('/tickets/{ticket}/customer-close', [TicketController::class, 'customerClose'])
        ->middleware('role:customer')
        ->name('tickets.customerClose');

    // Barcode (QR) untuk ticket yang sudah resolved
    Route::get('/tickets/{ticket}/barcode', [TicketBarcodeController::class, 'show'])->name('tickets.barcode.show');
    Route::get('/tickets/{ticket}/barcode/download', [TicketBarcodeController::class, 'download'])->name('tickets.barcode.download');
    Route::get('/barcode/data/{ticket}', [TicketBarcodeController::class, 'data'])->name('barcode.data');
});

// =================================================================
// NOTIFICATION POLL - for real-time-ish updates (no refresh)
// =================================================================
Route::get('/notifications/poll', [\App\Http\Controllers\NotificationController::class, 'poll'])
    ->middleware(['auth', 'role:customer,admin,agent,super_admin'])
    ->name('notifications.poll');

Route::middleware(['auth', 'role:customer,admin,agent'])->group(function () {
    Route::get('/diskusi', [TicketDiscussionController::class, 'index'])->name('discussions.index');
    Route::get('/diskusi/{ticket}', [TicketDiscussionController::class, 'show'])->name('discussions.show');
});

Route::middleware(['auth', 'role:customer,admin,agent'])->group(function () {
    Route::get('/scan-barcode', [TicketBarcodeController::class, 'scan'])->name('barcode.scan');
});

// =================================================================
// AUTH ROUTES - Login, Register, Forgot Password, dll
// =================================================================
// Didefinisikan di routes/auth.php (Laravel Breeze default)
require __DIR__ . '/auth.php';
