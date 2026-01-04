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

    // STEP 3: Hitung statistik berdasarkan role user

    if ($role === 'admin') {
        // ADMIN: Lihat SEMUA tickets (tanpa filter customer_id atau agent_id)
        $stats['open'] = \App\Models\Ticket::where('status', 'open')->count();
        $stats['assigned'] = \App\Models\Ticket::where('status', 'assigned')->count();
        $stats['in_progress'] = \App\Models\Ticket::where('status', 'in_progress')->count();
        $stats['resolved'] = \App\Models\Ticket::where('status', 'resolved')->count();
        $stats['closed'] = \App\Models\Ticket::where('status', 'closed')->count();
        $stats['total'] = \App\Models\Ticket::count();

        // Statistik khusus admin: tickets yang belum di-assign
        $stats['unassigned'] = \App\Models\Ticket::whereNull('agent_id')->count();
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
    } else {
        // CUSTOMER: Hanya lihat tickets MILIK dia (filter by customer_id)
        $stats['open'] = \App\Models\Ticket::where('customer_id', $user->id)->where('status', 'open')->count();
        $stats['assigned'] = \App\Models\Ticket::where('customer_id', $user->id)->where('status', 'assigned')->count();
        $stats['in_progress'] = \App\Models\Ticket::where('customer_id', $user->id)->where('status', 'in_progress')->count();
        $stats['resolved'] = \App\Models\Ticket::where('customer_id', $user->id)->where('status', 'resolved')->count();
        $stats['closed'] = \App\Models\Ticket::where('customer_id', $user->id)->where('status', 'closed')->count();
        $stats['total'] = \App\Models\Ticket::where('customer_id', $user->id)->count();
    }

    // Grafik: jumlah user per role
    $userCounts = [
        'admin' => \App\Models\User::where('role', 'admin')->count(),
        'agent' => \App\Models\User::where('role', 'agent')->count(),
        'customer' => \App\Models\User::where('role', 'customer')->count(),
    ];

    // Grafik: kategori yang paling sering dipilih customer (berdasarkan tickets)
    // NOTE: Kolom di DB adalah `category_id`, jadi perlu join ke tabel `categories`.
    $ticketsForCategory = \App\Models\Ticket::query();
    if ($role === 'agent') {
        $ticketsForCategory->where('agent_id', $user->id);
    } elseif ($role !== 'admin') {
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

    return view('dashboard', compact('stats', 'userCounts', 'categoryLabels', 'categoryValues'));
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
// ADMIN-ONLY ROUTES - Hanya admin yang bisa akses
// =================================================================

/**
 * Admin Routes Group
 * Middleware: auth, role:admin
 *
 * FITUR ADMIN:
 * 1. User Management - CRUD users (buat agent, customer baru, edit role, hapus)
 * 2. Category Management - CRUD kategori tickets
 *
 * PERMISSION:
 * Semua route di grup ini HANYA bisa diakses oleh user dengan role 'admin'
 * Jika non-admin coba akses, akan di-redirect atau error 403 Forbidden
 */
Route::middleware(['auth', 'role:admin'])->group(function () {

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

    // ===== CATEGORY MANAGEMENT ROUTES =====
    // Admin bisa kelola kategori tickets (create, edit, delete)
    Route::get('/admin/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('admin.categories.index');
    Route::get('/admin/categories/create', [\App\Http\Controllers\Admin\CategoryController::class, 'create'])->name('admin.categories.create');
    Route::post('/admin/categories', [\App\Http\Controllers\Admin\CategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('/admin/categories/{category}/edit', [\App\Http\Controllers\Admin\CategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::put('/admin/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/admin/categories/{category}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroy'])->name('admin.categories.destroy');
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
        ->middleware('role:admin')
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
        ->middleware('role:admin,agent')
        ->name('tickets.updateStatus');

    // Barcode (QR) untuk ticket yang sudah resolved
    Route::get('/tickets/{ticket}/barcode', [TicketBarcodeController::class, 'show'])->name('tickets.barcode.show');
    Route::get('/tickets/{ticket}/barcode/download', [TicketBarcodeController::class, 'download'])->name('tickets.barcode.download');
    Route::get('/barcode/data/{ticket}', [TicketBarcodeController::class, 'data'])->name('barcode.data');
});

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
