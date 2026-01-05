# 📘 DOKUMENTASI ALUR CODING - CLOUD CUSTOMER SUPPORT

> **Dokumentasi ini menjelaskan alur program coding dari folder `resources/`, menjelaskan setiap file, fungsinya, dan bagaimana code saling terhubung.**

---

## 📁 STRUKTUR FOLDER RESOURCES

```
resources/
├── css/              → File CSS custom (Tailwind, dll)
├── js/               → File JavaScript custom
└── views/            → File Blade Template (HTML + PHP)
    ├── admin/        → Halaman khusus admin
    ├── auth/         → Halaman autentikasi (login, register)
    ├── barcodes/     → Halaman barcode/QR code
    ├── components/   → Komponen reusable
    ├── discussions/  → Halaman diskusi tiket
    ├── layouts/      → Template layout utama
    ├── profile/      → Halaman profile user
    ├── tickets/      → Halaman CRUD tiket
    ├── dashboard.blade.php  → Dashboard utama
    └── welcome.blade.php    → Landing page
```

---

## 🎯 ALUR PROGRAM UTAMA

### 1️⃣ **USER MEMBUKA APLIKASI** (`http://localhost`)

**FILE:** `resources/views/welcome.blade.php`

**ROUTE:** `routes/web.php` → `Route::get('/', ...)`

**ALUR CODE:**

```php
// routes/web.php
Route::get('/', function () {
    return view('welcome');  // ← Menampilkan welcome.blade.php
})->name('home');
```

**PENJELASAN:**

-   User membuka `http://localhost` atau `http://localhost:8000`
-   Laravel akan membaca `routes/web.php`
-   Route `/` mengarahkan ke view `welcome.blade.php`
-   File ini adalah **landing page** dengan informasi aplikasi
-   Ada tombol "Masuk" dan "Daftar" untuk akses aplikasi

**KLIK TOMBOL "MASUK":**

```blade
<!-- welcome.blade.php line ~75 -->
<a href="{{ route('login') }}" class="btn btn-primary">
    Masuk
</a>
```

→ Mengarah ke halaman login (`auth/login.blade.php`)

---

### 2️⃣ **USER KLIK "MASUK"** → LOGIN PAGE

**FILE:** `resources/views/auth/login.blade.php`

**ROUTE:** `routes/auth.php` → `Route::get('login', ...)`

**ALUR CODE:**

```php
// routes/auth.php
Route::get('login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');  // ← Menampilkan form login
```

**PENJELASAN:**

-   Route `login` memanggil method `create()` di `AuthenticatedSessionController`
-   Controller ini mengembalikan view `auth.login`
-   View ini menggunakan layout `@extends('layouts.bootstrap')` (tanpa navbar/sidebar)

**KOMPONEN PENTING:**

```blade
<!-- login.blade.php -->
<form method="POST" action="{{ route('login') }}">
    @csrf
    <input type="email" name="email" ...>
    <input type="password" name="password" ...>
    <button type="submit">Masuk</button>
</form>
```

**KLIK TOMBOL "MASUK" (SUBMIT FORM):**

```php
// routes/auth.php
Route::post('login', [AuthenticatedSessionController::class, 'store']);
```

→ Mengirim data ke method `store()` di controller

**PROSES AUTENTIKASI:**

```php
// app/Http/Controllers/Auth/AuthenticatedSessionController.php
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();  // ← Validasi email & password
    $request->session()->regenerate();
    return redirect()->intended(route('dashboard'));  // ← Sukses → Dashboard
}
```

**JIKA LOGIN GAGAL:**

-   `LoginRequest::authenticate()` throw `ValidationException`
-   Laravel redirect kembali ke form login
-   Error ditampilkan via **pop-up modal** (sudah kita tambahkan)

```blade
<!-- login.blade.php - Pop-up Modal Error -->
@if($errors->has('auth'))
<div class="modal fade alert-modal error" id="alertModal">
    <div class="modal-content">
        <div class="alert-icon-circle">
            <i class="fa-solid fa-exclamation"></i>
        </div>
        <h5>Login Gagal</h5>
        <p>{{ $errors->first('auth') }}</p>
        <button data-bs-dismiss="modal">OK, Mengerti!</button>
    </div>
</div>
@endif
```

---

### 3️⃣ **LOGIN BERHASIL** → DASHBOARD

**FILE:** `resources/views/dashboard.blade.php`

**ROUTE:** `routes/web.php` → `Route::get('/dashboard', ...)`

**ALUR CODE:**

```php
// routes/web.php
Route::get('/dashboard', function () {
    $user = Auth::user();  // ← Ambil user yang login
    $role = $user->role;   // ← Cek role (admin/agent/customer)

    // Hitung statistik tiket berdasarkan role
    if ($role === 'admin') {
        $stats['total'] = Ticket::count();  // ← Admin lihat semua tiket
    } elseif ($role === 'agent') {
        $stats['total'] = Ticket::where('agent_id', $user->id)->count();  // ← Agent lihat tiket yang di-assign
    } else {
        $stats['total'] = Ticket::where('customer_id', $user->id)->count();  // ← Customer lihat tiket miliknya
    }

    return view('dashboard', compact('stats', 'userCounts', 'categoryLabels', 'categoryValues'));
})->middleware(['auth', 'verified'])->name('dashboard');
```

**PENJELASAN:**

-   Dashboard menggunakan layout `@extends('layouts.sidebar')` (dengan sidebar & navbar)
-   Menampilkan **statistik cards** (Total, Open, Assigned, In Progress, Resolved, Closed)
-   Menampilkan **grafik** (hanya untuk admin)
-   Menampilkan **tombol aksi** sesuai role

**KOMPONEN DASHBOARD:**

1. **Header & Role Badge:**

```blade
<div class="d-flex justify-content-between">
    <h1>Dashboard</h1>
    <div>Selamat datang, {{ auth()->user()->name }}</div>
    @if($role === 'admin')
        <span class="badge text-bg-danger">Admin</span>
    @endif
</div>
```

2. **Statistik Cards:**

```blade
<div class="row g-3">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="stat-label">Total</div>
            <h3>{{ $stats['total'] }}</h3>
        </div>
    </div>
    <!-- ... card lainnya ... -->
</div>
```

3. **Grafik (Admin Only):**

```blade
@if($role === 'admin')
<div class="row g-3">
    <div class="col-lg-4">
        <canvas id="chartStatusBar"></canvas>  <!-- Grafik User -->
    </div>
    <div class="col-lg-4">
        <canvas id="chartStatusDonut"></canvas>  <!-- Grafik Status -->
    </div>
    <div class="col-lg-4">
        <canvas id="chartStatusLine"></canvas>  <!-- Grafik Kategori -->
    </div>
</div>

<script>
    // Chart.js untuk render grafik
    new Chart(document.getElementById('chartStatusBar'), {
        type: 'bar',
        data: { labels: ['Admin', 'Agent', 'Customer'], ... }
    });
</script>
@endif
```

**KLIK TOMBOL DI DASHBOARD:**

-   **Admin:**

    -   "Tiket" → `tickets.index` (Daftar semua tiket)
    -   "Users" → `admin.users.index` (Kelola user)

-   **Agent:**

    -   "Tiket yang Harus Dikerjakan" → `tickets.index` (Tiket yang di-assign)

-   **Customer:**
    -   "Buat Tiket" → `tickets.create` (Form buat tiket)
    -   "Tiket Saya" → `tickets.index` (Tiket miliknya)

---

### 4️⃣ **HALAMAN TIKET** (Admin/Agent/Customer)

**FILE:** `resources/views/tickets/index.blade.php`

**ROUTE:** `routes/web.php` → `Route::resource('tickets', TicketController::class)`

**ALUR CODE:**

```php
// routes/web.php
Route::resource('tickets', TicketController::class);
// ↑ Menghasilkan routes:
// GET  /tickets         → index()   (daftar tiket)
// GET  /tickets/create  → create()  (form buat tiket)
// POST /tickets         → store()   (simpan tiket baru)
// GET  /tickets/{id}    → show()    (detail tiket)
// GET  /tickets/{id}/edit → edit()  (form edit tiket)
// PUT  /tickets/{id}    → update()  (update tiket)
// DELETE /tickets/{id}  → destroy() (hapus tiket)
```

**CONTROLLER:**

```php
// app/Http/Controllers/TicketController.php
public function index()
{
    $user = Auth::user();

    if ($user->role === 'admin') {
        $tickets = $this->ticketService->getAllTickets();  // ← Semua tiket
    } elseif ($user->role === 'agent') {
        $tickets = $this->ticketService->getTicketsByAgent($user->id);  // ← Tiket yang di-assign
    } else {
        $tickets = $this->ticketService->getTicketsByCustomer($user->id);  // ← Tiket miliknya
    }

    return view('tickets.index', compact('tickets'));
}
```

**VIEW - KOMPONEN PENTING:**

1. **Header dengan Search & Filter (Admin & Agent):**

```blade
@if(auth()->user()->role === 'admin' || auth()->user()->role === 'agent')
<div class="d-flex gap-2 mb-3">
    <input type="text" id="search-ticket" placeholder="Cari tiket...">
    <select id="filter-status">
        <option value="">Semua Status</option>
        @if(auth()->user()->role === 'admin')
            <!-- Admin: 5 status -->
            <option value="open">Open</option>
            <option value="assigned">Assigned</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
        @else
            <!-- Agent: 2 status -->
            <option value="assigned">Assigned</option>
            <option value="in_progress">In Progress</option>
        @endif
    </select>
</div>
@endif
```

**KENAPA AGENT HANYA 2 STATUS?**

-   Agent hanya mengerjakan tiket yang **sudah di-assign** atau **in progress**
-   Tiket "open", "resolved", "closed" tidak relevan untuk agent

2. **Tabel Tiket:**

```blade
<table class="table">
    <thead>
        <tr>
            <th>No</th>
            <th>Judul</th>
            <th>Kategori</th>
            <th>Prioritas</th>
            <th>Status</th>
            @if(auth()->user()->role === 'admin')
                <th>Customer</th>  <!-- Hanya admin lihat customer -->
            @endif
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tickets as $t)
        <tr class="ticket-row"
            data-status="{{ $t['status'] }}"
            data-title="{{ $t['title'] }}">
            <td>{{ $loop->iteration }}</td>
            <td>{{ $t['title'] }}</td>
            <td>{{ $t['category'] }}</td>
            <td>
                <span class="badge">{{ strtoupper($t['priority'][0]) }}</span>
            </td>
            <td>
                <span class="badge text-bg-{{ $statusColors[$t['status']] }}">
                    {{ ucfirst($t['status']) }}
                </span>
            </td>
            <td>
                <a href="{{ route('tickets.show', $t['id']) }}" class="btn btn-sm">
                    Lihat
                </a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
```

3. **JavaScript Live Search:**

```javascript
// Di bagian bawah tickets/index.blade.php
document.getElementById("search-ticket").addEventListener("input", function () {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll(".ticket-row");

    rows.forEach((row) => {
        const title = row.dataset.title;
        const category = row.dataset.category;
        const customer = row.dataset.customer;

        const match =
            title.includes(searchTerm) ||
            category.includes(searchTerm) ||
            customer.includes(searchTerm);

        row.style.display = match ? "" : "none"; // ← Sembunyikan yang tidak match
    });
});
```

**KLIK "LIHAT" PADA TIKET:**

```blade
<a href="{{ route('tickets.show', $t['id']) }}">Lihat</a>
```

→ Mengarah ke `tickets/show.blade.php`

---

### 5️⃣ **DETAIL TIKET**

**FILE:** `resources/views/tickets/show.blade.php`

**ROUTE:** `GET /tickets/{id}` → `TicketController@show`

**ALUR CODE:**

```php
// app/Http/Controllers/TicketController.php
public function show(Ticket $ticket)
{
    // Validasi permission
    $user = Auth::user();

    if ($user->role === 'customer' && $ticket->customer_id != $user->id) {
        abort(403);  // ← Customer hanya bisa lihat tiketnya sendiri
    }

    if ($user->role === 'agent' && $ticket->agent_id != $user->id) {
        abort(403);  // ← Agent hanya bisa lihat tiket yang di-assign ke dia
    }

    // Ambil detail tiket dari Firestore
    $ticketData = $this->ticketService->getTicketById($ticket->firestore_id);

    // Ambil daftar agent (untuk admin assign)
    $agents = User::where('role', 'agent')->get();

    return view('tickets.show', compact('ticket', 'ticketData', 'agents'));
}
```

**VIEW - KOMPONEN PENTING:**

1. **Header Tiket:**

```blade
<div class="card-header">
    <h5>{{ $ticketData['title'] }}</h5>
    <span class="badge text-bg-{{ $statusColors[$ticketData['status']] }}">
        {{ ucfirst($ticketData['status']) }}
    </span>
</div>
```

2. **Detail Tiket:**

```blade
<div class="card-body">
    <div class="row">
        <div class="col-md-6">
            <strong>Kategori:</strong> {{ $ticketData['category'] }}
        </div>
        <div class="col-md-6">
            <strong>Prioritas:</strong> {{ ucfirst($ticketData['priority']) }}
        </div>
        <div class="col-md-6">
            <strong>Customer:</strong> {{ $ticketData['customer_name'] }}
        </div>
        @if($ticketData['agent_name'])
        <div class="col-md-6">
            <strong>Agent:</strong> {{ $ticketData['agent_name'] }}
        </div>
        @endif
    </div>

    <hr>

    <div>
        <strong>Deskripsi:</strong>
        <p>{{ $ticketData['description'] }}</p>
    </div>

    @if(!empty($ticketData['attachments']))
    <div>
        <strong>Lampiran:</strong>
        @foreach($ticketData['attachments'] as $att)
        <a href="{{ $att['url'] }}" target="_blank">
            📎 {{ $att['name'] }}
        </a>
        @endforeach
    </div>
    @endif
</div>
```

3. **Form Assign Agent (Admin Only):**

```blade
@if(auth()->user()->role === 'admin' && $ticketData['status'] === 'open')
<form method="POST" action="{{ route('tickets.assign', $ticket) }}">
    @csrf
    <select name="agent_id" class="form-select">
        <option value="">Pilih Agent</option>
        @foreach($agents as $agent)
        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary">Assign</button>
</form>
@endif
```

**KLIK "ASSIGN":**

```php
// routes/web.php
Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assignAgent'])
    ->middleware('role:admin');

// TicketController.php
public function assignAgent(Request $request, Ticket $ticket)
{
    $request->validate(['agent_id' => 'required|exists:users,id']);

    // Update di Firestore
    $this->ticketService->assignTicketToAgent(
        $ticket->firestore_id,
        $request->agent_id
    );

    // Update di Laravel DB
    $ticket->update([
        'agent_id' => $request->agent_id,
        'status' => 'assigned'
    ]);

    return redirect()->back()->with('success', 'Tiket berhasil di-assign');
}
```

4. **Update Status (Agent):**

```blade
@if(auth()->user()->role === 'agent')
<form method="POST" action="{{ route('tickets.updateStatus', $ticket) }}">
    @csrf
    <select name="status" class="form-select">
        <option value="in_progress">In Progress</option>
        <option value="resolved">Resolved</option>
    </select>
    <button type="submit" class="btn btn-warning">Update Status</button>
</form>
@endif
```

5. **Comments Section:**

```blade
<div class="comments">
    <h6>Komentar</h6>
    @foreach($ticketData['comments'] ?? [] as $comment)
    <div class="comment-item">
        <strong>{{ $comment['user_name'] }}</strong>
        <small>{{ $comment['created_at'] }}</small>
        <p>{{ $comment['text'] }}</p>
    </div>
    @endforeach

    <!-- Form Tambah Komentar -->
    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}">
        @csrf
        <textarea name="comment" class="form-control" required></textarea>
        <button type="submit" class="btn btn-primary">Kirim</button>
    </form>
</div>
```

**KLIK "KIRIM KOMENTAR":**

```php
// routes/web.php
Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store']);

// TicketCommentController.php
public function store(Request $request, Ticket $ticket)
{
    $request->validate(['comment' => 'required|string']);

    // Simpan ke Firestore
    $this->ticketService->addComment(
        $ticket->firestore_id,
        Auth::user()->name,
        $request->comment
    );

    // Simpan ke Laravel DB
    TicketComment::create([
        'ticket_id' => $ticket->id,
        'user_id' => Auth::id(),
        'comment' => $request->comment
    ]);

    return redirect()->back()->with('success', 'Komentar berhasil ditambahkan');
}
```

---

### 6️⃣ **HALAMAN ADMIN - KELOLA USER**

**FILE:** `resources/views/admin/users/index.blade.php`

**ROUTE:** `routes/web.php` → `Route::get('/admin/users', ...)`

**ALUR CODE:**

```php
// routes/web.php (dalam middleware role:admin)
Route::get('/admin/users', [UserController::class, 'index'])
    ->name('admin.users.index');

// app/Http/Controllers/Admin/UserController.php
public function index()
{
    $users = User::orderBy('created_at', 'desc')->get();
    return view('admin.users.index', compact('users'));
}
```

**VIEW:**

```blade
<table class="table">
    <thead>
        <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td>
                <span class="badge text-bg-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'agent' ? 'warning' : 'success') }}">
                    {{ ucfirst($user->role) }}
                </span>
            </td>
            <td>
                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-warning">
                    Edit
                </a>
                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" style="display:inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<a href="{{ route('admin.users.create') }}" class="btn btn-primary">
    Tambah User Baru
</a>
```

**KLIK "TAMBAH USER":**
→ `admin/users/create.blade.php`

**KLIK "EDIT":**
→ `admin/users/edit.blade.php`

---

### 7️⃣ **LAYOUT SYSTEM**

**FILE:** `resources/views/layouts/sidebar.blade.php`

**FUNGSI:** Template utama untuk halaman yang butuh sidebar & navbar

**STRUKTUR:**

```blade
<!DOCTYPE html>
<html>
<head>
    <title>@yield('title') - Cloud Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-ticket"></i>
            Cloud Ticket
        </div>

        <nav class="sidebar-menu">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-line"></i>
                Dashboard
            </a>

            <!-- Tickets -->
            <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                <i class="fa-solid fa-ticket"></i>
                Tiket
            </a>

            <!-- Admin Only Menu -->
            @if(auth()->user()->role === 'admin')
            <a href="{{ route('admin.users.index') }}" class="nav-link">
                <i class="fa-solid fa-users"></i>
                Kelola User
            </a>
            <a href="{{ route('admin.categories.index') }}" class="nav-link">
                <i class="fa-solid fa-tags"></i>
                Kategori
            </a>
            @endif

            <!-- Profile -->
            <a href="{{ route('profile.edit') }}" class="nav-link">
                <i class="fa-solid fa-user"></i>
                Profile
            </a>

            <!-- Logout -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link btn-logout">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    Logout
                </button>
            </form>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- NAVBAR -->
        <nav class="navbar">
            <div class="navbar-left">
                <button class="btn-toggle-sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h5 class="page-title">@yield('page-title')</h5>
            </div>
            <div class="navbar-right">
                <span>{{ auth()->user()->name }}</span>
                <span class="badge text-bg-{{ auth()->user()->role === 'admin' ? 'danger' : (auth()->user()->role === 'agent' ? 'warning' : 'success') }}">
                    {{ ucfirst(auth()->user()->role) }}
                </span>
            </div>
        </nav>

        <!-- PAGE CONTENT -->
        <div class="content-wrapper">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
```

**PENJELASAN MENU AKTIF:**

```blade
<a href="{{ route('tickets.index') }}"
   class="nav-link {{ request()->routeIs('tickets.*') ? 'active' : '' }}">
```

-   `request()->routeIs('tickets.*')` mengecek apakah route sekarang adalah `tickets.index`, `tickets.show`, dll
-   Jika ya, tambahkan class `active` untuk highlight menu

---

### 8️⃣ **LAYOUT BOOTSTRAP** (Tanpa Sidebar)

**FILE:** `resources/views/layouts/bootstrap.blade.php`

**FUNGSI:** Template untuk halaman tanpa sidebar (login, register, landing page)

**DIGUNAKAN OLEH:**

-   `welcome.blade.php`
-   `auth/login.blade.php`
-   `auth/register.blade.php`

---

## 🔄 ALUR LENGKAP FITUR UTAMA

### ✅ **FITUR: CUSTOMER BUAT TIKET BARU**

1. **Customer login** → Dashboard
2. **Klik "Buat Tiket"** → `tickets/create.blade.php`

    ```blade
    <a href="{{ route('tickets.create') }}" class="btn btn-primary">
        Buat Tiket
    </a>
    ```

3. **Isi form** → Submit

    ```blade
    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
        @csrf
        <input type="text" name="title" required>
        <textarea name="description" required></textarea>
        <select name="category_id" required>
            @foreach($categories as $cat)
            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
        <select name="priority" required>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
        </select>
        <input type="file" name="attachments[]" multiple>
        <button type="submit">Kirim</button>
    </form>
    ```

4. **Controller process:**

    ```php
    public function store(Request $request)
    {
        // Validasi
        $request->validate([...]);

        // Upload file ke Firebase Storage
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $url = $this->ticketService->uploadAttachment($file);
                $attachments[] = ['name' => $file->getClientOriginalName(), 'url' => $url];
            }
        }

        // Simpan ke Firestore
        $firestoreId = $this->ticketService->createTicket([
            'title' => $request->title,
            'description' => $request->description,
            'customer_id' => Auth::id(),
            'status' => 'open',
            'attachments' => $attachments,
        ]);

        // Simpan ke Laravel DB
        Ticket::create([
            'firestore_id' => $firestoreId,
            'customer_id' => Auth::id(),
            'status' => 'open',
        ]);

        return redirect()->route('tickets.index')->with('success', 'Tiket berhasil dibuat');
    }
    ```

5. **Redirect ke** `tickets/index.blade.php` dengan success message

---

### ✅ **FITUR: ADMIN ASSIGN TIKET KE AGENT**

1. **Admin login** → Dashboard
2. **Klik "Tiket"** → `tickets/index.blade.php` (lihat semua tiket)
3. **Klik "Lihat" pada tiket status "open"** → `tickets/show.blade.php`
4. **Pilih agent** → Submit form assign

    ```blade
    <form method="POST" action="{{ route('tickets.assign', $ticket) }}">
        @csrf
        <select name="agent_id">
            @foreach($agents as $agent)
            <option value="{{ $agent->id }}">{{ $agent->name }}</option>
            @endforeach
        </select>
        <button type="submit">Assign</button>
    </form>
    ```

5. **Controller update:**

    ```php
    public function assignAgent(Request $request, Ticket $ticket)
    {
        // Update Firestore
        $this->ticketService->assignTicketToAgent($ticket->firestore_id, $request->agent_id);

        // Update Laravel DB
        $ticket->update(['agent_id' => $request->agent_id, 'status' => 'assigned']);

        return redirect()->back()->with('success', 'Tiket berhasil di-assign');
    }
    ```

6. **Status tiket berubah** dari "open" → "assigned"
7. **Agent sekarang bisa lihat tiket ini** di halaman tickets mereka

---

### ✅ **FITUR: AGENT UPDATE STATUS TIKET**

1. **Agent login** → Dashboard
2. **Lihat "Tiket yang Harus Dikerjakan"** → `tickets/index.blade.php` (hanya tiket assigned/in_progress)
3. **Klik "Lihat"** → `tickets/show.blade.php`
4. **Update status** → Submit

    ```blade
    <form method="POST" action="{{ route('tickets.updateStatus', $ticket) }}">
        @csrf
        <select name="status">
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
        </select>
        <button type="submit">Update</button>
    </form>
    ```

5. **Controller:**
    ```php
    public function updateStatus(Request $request, Ticket $ticket)
    {
        // Update Firestore
        $this->ticketService->updateTicketStatus($ticket->firestore_id, $request->status);

        // Update Laravel DB
        $ticket->update(['status' => $request->status]);

        return redirect()->back()->with('success', 'Status diupdate');
    }
    ```

---

## 📊 RANGKUMAN FLOW CHART

```
START
  ↓
[Landing Page] welcome.blade.php
  ↓ (Klik "Masuk")
[Login] auth/login.blade.php
  ↓ (Submit form → AuthenticatedSessionController@store)
  ↓ (Validasi email & password)
  ↓ (Sukses)
[Dashboard] dashboard.blade.php
  │
  ├─ Role: Customer
  │   ├─ Klik "Buat Tiket" → tickets/create.blade.php
  │   │   └─ Submit → TicketController@store → Firestore + DB
  │   │       └─ Redirect → tickets/index.blade.php
  │   │
  │   └─ Klik "Tiket Saya" → tickets/index.blade.php
  │       └─ Klik "Lihat" → tickets/show.blade.php
  │           └─ Tambah komentar → TicketCommentController@store
  │
  ├─ Role: Agent
  │   └─ Klik "Tiket yang Harus Dikerjakan" → tickets/index.blade.php
  │       ├─ [Search & Filter: Assigned / In Progress]
  │       └─ Klik "Lihat" → tickets/show.blade.php
  │           ├─ Update status → TicketController@updateStatus
  │           └─ Tambah komentar → TicketCommentController@store
  │
  └─ Role: Admin
      ├─ Klik "Tiket" → tickets/index.blade.php
      │   ├─ [Search & Filter: All Status]
      │   └─ Klik "Lihat" → tickets/show.blade.php
      │       ├─ Assign ke agent → TicketController@assignAgent
      │       └─ Close tiket → TicketController@updateStatus
      │
      ├─ Klik "Users" → admin/users/index.blade.php
      │   ├─ Klik "Tambah User" → admin/users/create.blade.php
      │   ├─ Klik "Edit" → admin/users/edit.blade.php
      │   └─ Klik "Hapus" → UserController@destroy
      │
      └─ Klik "Kategori" → admin/categories/index.blade.php
          ├─ Klik "Tambah" → admin/categories/create.blade.php
          └─ Klik "Edit" → admin/categories/edit.blade.php
```

---

## 🎨 STYLING & ASSETS

### CSS Files (`resources/css/`)

-   **app.css**: File CSS custom aplikasi
-   Tailwind CSS diload via CDN di layout

### JavaScript Files (`resources/js/`)

-   **bootstrap.js**: Bootstrap JS bundle
-   **app.js**: File JS custom aplikasi

---

## 🔒 MIDDLEWARE & PERMISSION

**Middleware di Routes:**

```php
// Hanya user yang sudah login
Route::middleware(['auth'])->group(function() { ... });

// Hanya admin
Route::middleware(['auth', 'role:admin'])->group(function() { ... });

// Admin & Agent
Route::middleware(['auth', 'role:admin,agent'])->group(function() { ... });

// Semua role
Route::middleware(['auth', 'role:customer,admin,agent'])->group(function() { ... });
```

**Check di Controller:**

```php
if (auth()->user()->role !== 'admin') {
    abort(403, 'Unauthorized');
}
```

**Check di View:**

```blade
@if(auth()->user()->role === 'admin')
    <!-- Hanya admin lihat ini -->
@endif
```

---

## 📝 KESIMPULAN

**Alur Program Utama:**

1. User buka web → `welcome.blade.php`
2. Klik masuk → `auth/login.blade.php` → Submit → Controller validasi
3. Login sukses → `dashboard.blade.php` (tampilkan stats & grafik)
4. Akses menu sesuai role via sidebar (`layouts/sidebar.blade.php`)
5. Setiap halaman menggunakan layout yang sama (konsisten)
6. Setiap aksi (create, update, delete) memanggil controller
7. Controller berinteraksi dengan Firestore & Laravel DB
8. Redirect kembali dengan success/error message
9. View menampilkan data dari controller via Blade syntax

**Key Files:**

-   **Layouts**: `sidebar.blade.php`, `bootstrap.blade.php`
-   **Dashboard**: `dashboard.blade.php`
-   **Tickets**: `tickets/index.blade.php`, `tickets/show.blade.php`
-   **Admin**: `admin/users/*`, `admin/categories/*`
-   **Auth**: `auth/login.blade.php`, `auth/register.blade.php`

---

✅ **Dokumentasi ini menjelaskan alur coding dari folder resources secara lengkap!**
