# Firebase Token-Based API Authentication

## Overview

Implementasi token-based authentication menggunakan Firebase ID Token untuk API endpoints. Sistem ini:

1. **Secure**: Password disimpan di Firebase Auth (tidak di DB/Firestore)
2. **Stateless**: Token dapat di-verify tanpa query DB (JWT)
3. **Auditable**: Setiap token generation disimpan di Firestore untuk audit trail

---

## Architecture

```
┌─────────────────┐
│   Client App    │
└────────┬────────┘
         │
         ├─► POST /api/token (email + password)
         │   ↓
         ├─► Firebase Auth (verify credentials)
         │   ↓
         ├─ Get: id_token, refresh_token, uid
         │
         ├─ Save token to Firestore `tokens` collection
         │
         ◄─ Response with tokens
         │
         ├─► All API requests with Authorization: Bearer <id_token>
         │
         └─► Middleware TokenAuth validates & extracts claims
```

---

## API Endpoints

### 1. Generate Token (Login)

```http
POST /api/token
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

**Success Response (200)**

```json
{
    "success": true,
    "data": {
        "user_id": 1,
        "name": "Admin",
        "email": "admin@example.com",
        "role": "admin",
        "id_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjA2ZjU2NWZlYTcyYjgyYjc1ZjE2ZTg4OWFlZmMwM2I2YmY1MDhlMDQiLCJ0eXAiOiJKV1QifQ...",
        "refresh_token": "AMf-vBxj5L2bXZB...",
        "token_type": "Bearer",
        "expires_in": 3600
    }
}
```

**Error Response (422)**

```json
{
    "message": "The email field is required.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

---

### 2. Verify Token (Check Validity)

```http
GET /api/token/verify
Authorization: Bearer <id_token>
```

**Success Response (200)**

```json
{
    "success": true,
    "data": {
        "valid": true,
        "uid": "abc123xyz",
        "email": "admin@example.com",
        "expires_at": 1735982400,
        "issued_at": 1735979000
    }
}
```

**Error Response (401) - Invalid/Expired Token**

```json
{
    "error": "Unauthorized",
    "message": "Invalid or expired token"
}
```

---

### 3. Revoke Token (Logout)

```http
POST /api/token/revoke
Authorization: Bearer <id_token>
```

**Success Response (200)**

```json
{
    "success": true,
    "message": "Token telah di-revoke. Gunakan email+password untuk login kembali."
}
```

---

## Usage Examples

### cURL

```bash
# 1. Get token
curl -X POST http://localhost:8000/api/token \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Save id_token from response, e.g:
TOKEN="eyJhbGciOiJSUzI1NiIs..."

# 2. Verify token
curl -X GET http://localhost:8000/api/token/verify \
  -H "Authorization: Bearer $TOKEN"

# 3. Revoke token
curl -X POST http://localhost:8000/api/token/revoke \
  -H "Authorization: Bearer $TOKEN"
```

### JavaScript/Fetch

```javascript
// 1. Get token
async function login(email, password) {
    const res = await fetch("/api/token", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
    });

    const data = await res.json();
    if (res.ok) {
        localStorage.setItem("token", data.data.id_token);
        return data.data;
    }

    throw new Error(data.message);
}

// 2. Make API request with token
async function apiCall(endpoint, method = "GET") {
    const token = localStorage.getItem("token");

    const res = await fetch(endpoint, {
        method,
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
    });

    if (res.status === 401) {
        // Token expired or invalid
        localStorage.removeItem("token");
        // Redirect to login
    }

    return res.json();
}

// 3. Usage
const user = await login("admin@example.com", "password");
console.log("Logged in:", user);

const verified = await apiCall("/api/token/verify");
console.log("Token valid:", verified.data.valid);
```

### Python

```python
import requests
import json

# 1. Get token
response = requests.post('http://localhost:8000/api/token', json={
    'email': 'admin@example.com',
    'password': 'password'
})

data = response.json()
token = data['data']['id_token']

# 2. Make API request with token
headers = {'Authorization': f'Bearer {token}'}
response = requests.get('http://localhost:8000/api/token/verify', headers=headers)
print(response.json())
```

---

## Token Storage & Audit

Setiap kali token di-generate, metadata disimpan ke Firestore `tokens` collection:

```firestore
Collection: tokens
├─ Document: <auto-id>
│  ├─ firebase_uid: "abc123xyz"
│  ├─ email: "admin@example.com"
│  ├─ token_prefix: "eyJhbGciOiJSUzI1Ni..." (first 20 chars only)
│  ├─ issued_at: Timestamp(2026-01-05 12:00:00)
│  ├─ expires_at: Timestamp(2026-01-05 13:00:00)
│  ├─ ip_address: "192.168.1.100"
│  └─ user_agent: "Mozilla/5.0..."
```

**Keuntungan:**

-   Audit trail: Siapa login kapan dari mana
-   Device tracking: Detect login dari device baru
-   Session management: Logout semua device (revoke by UID)
-   Analytics: Peak login times, device distribution, etc

---

## Token Lifespan

-   **ID Token**: ~1 jam (3600 detik, di-set oleh Firebase)
-   **Refresh Token**: Long-lived (gunakan untuk get new ID Token)
-   **Ekspirasi**: Token invalid setelah masa berlaku, harus re-login

---

## Security Best Practices

1. **HTTPS Only**: Selalu gunakan HTTPS di production
2. **Token Storage**: Simpan di secure storage (tidak di localStorage untuk sensitive data)
3. **Token Rotation**: Implement refresh token flow untuk auto-refresh
4. **CORS**: Configure CORS untuk trusted domains saja
5. **Rate Limiting**: Limit login attempts untuk prevent brute force
6. **Token Prefix**: Hanya prefix token disimpan di Firestore (tidak full token)

---

## Extending with Protected Routes

Untuk membuat API endpoint yang require token:

```php
// In routes/api.php
Route::middleware(['token-auth'])->group(function () {
    Route::get('/users/profile', [UserController::class, 'profile'])
        ->name('api.users.profile');

    Route::put('/users/profile', [UserController::class, 'updateProfile'])
        ->name('api.users.update');
});

// In controller, access token claims:
public function profile(Request $request) {
    $uid = $request->get('token_uid');      // Firebase UID
    $email = $request->get('token_email');  // Email
    $claims = $request->get('token_claims'); // All JWT claims

    return response()->json([
        'uid' => $uid,
        'email' => $email,
        'claims' => $claims
    ]);
}
```

---

## Troubleshooting

### Token verification fails

-   Pastikan Firebase config sudah benar di `.env`
-   Check token belum expired
-   Verify format: `Authorization: Bearer <token>` (perhatian spasi & kapital)

### "Firebase Auth not available"

-   Set `FIREBASE_FIRESTORE_ENABLED=true` di `.env`
-   Set `FIREBASE_CREDENTIALS` path to valid service account JSON
-   Set `FIREBASE_PROJECT_ID`

### Double prefix `api/api` di routes

-   Pastikan di `routes/api.php` tidak gunakan `Route::prefix('api')`
-   Laravel 12 sudah auto-prefix dengan `/api` untuk file ini
