<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Category;

/**
 * USER MODEL - Model untuk data pengguna sistem
 *
 * PENJELASAN KE DOSEN:
 * Model ini merepresentasikan pengguna sistem dengan 3 role berbeda:
 * 1. ADMIN   - Mengelola sistem, assign tickets ke agent
 * 2. AGENT   - Menangani tickets yang di-assign, update status
 * 3. CUSTOMER - Membuat tickets baru, melihat progress tickets mereka
 *
 * FIELD PENTING:
 * - name: Nama lengkap user
 * - email: Email untuk login (unique)
 * - password: Password yang sudah di-hash
 * - role: Jenis user (admin/agent/customer)
 *
 * KEAMANAN:
 * - Password otomatis di-hash sebelum disimpan
 * - Role-based access control untuk setiap fitur
 * - Middleware RoleMiddleware mengecek permission di setiap route
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Helper method untuk cek role user
     * Contoh: if ($user->hasRole('admin')) { ... }
     *
     * @param string $role - Role yang mau dicek (admin/agent/customer)
     * @return bool - True jika user punya role tersebut
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'category_id',
        'availability_status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
