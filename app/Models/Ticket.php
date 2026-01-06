<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * TICKET MODEL - Model untuk data tiket customer support
 *
 * PENJELASAN KE DOSEN:
 * Ticket adalah inti dari sistem ini. Setiap customer yang punya masalah akan membuat ticket.
 * Ticket berisi deskripsi masalah, kategori, prioritas, dan status pengerjaannya.
 *
 * LIFECYCLE TICKET:
 * 1. OPEN - Customer baru buat ticket
 * 2. ASSIGNED - Admin sudah assign ke agent tertentu
 * 3. IN_PROGRESS - Agent sedang mengerjakan
 * 4. RESOLVED - Agent sudah selesai, perlu konfirmasi admin
 * 5. CLOSED - Admin tutup ticket setelah verifikasi resolved
 *
 * FIELD PENTING:
 * - firebase_id: ID di Firestore (untuk real-time sync)
 * - title: Judul singkat masalah
 * - description: Penjelasan detail masalah
 * - customer_id: User yang buat ticket (foreign key ke users)
 * - agent_id: Agent yang ditugaskan (null jika belum di-assign)
 * - category_id: Kategori masalah (foreign key ke categories)
 * - status: Status saat ini (open/assigned/in_progress/resolved/closed)
 * - priority: Tingkat urgent (low/medium/high)
 * - attachments: File pendukung (foto/dokumen) dalam format JSON array
 *
 * SOFT DELETE:
 * Ticket tidak benar-benar dihapus, hanya di-mark deleted_at
 * Berguna untuk audit trail dan recovery
 */
class Ticket extends Model
{
    use SoftDeletes;

    /**
     * Field yang boleh diisi secara mass-assignment
     * Untuk keamanan, hanya field ini yang bisa di-fill langsung
     */
    protected $fillable = [
        'firebase_id',
        'title',
        'description',
        'location',
        'customer_id',
        'agent_id',
        'category_id',
        'status',
        'priority',
        'attachments',
    ];

    /**
     * Cast otomatis tipe data
     * - attachments jadi array otomatis saat diakses
     * - timestamps jadi Carbon instance untuk manipulasi tanggal mudah
     */
    protected $casts = [
        'attachments' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================
    // Relasi ke tabel lain untuk join data

    /**
     * Relasi ke User (sebagai Customer)
     * Ticket belongsTo Customer (1 ticket punya 1 customer)
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Relasi ke User (sebagai Agent)
     * Ticket belongsTo Agent (1 ticket punya 1 agent, bisa null)
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Relasi ke Category
     * Ticket belongsTo Category (1 ticket punya 1 kategori)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke TicketComment
     * Ticket hasMany Comments (1 ticket bisa punya banyak komentar)
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    // ==================== QUERY SCOPES ====================
    // Helper untuk filter query dengan mudah

    /**
     * Scope untuk filter ticket berdasarkan status 'open'
     * Contoh: Ticket::open()->get()
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope untuk filter ticket berdasarkan status 'in_progress'
     * Contoh: Ticket::inProgress()->get()
     */
    /**
     * Scope untuk filter ticket berdasarkan status 'in_progress'
     * Contoh: Ticket::inProgress()->get()
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope untuk filter ticket berdasarkan status 'resolved'
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope untuk filter ticket berdasarkan status 'closed'
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope untuk filter ticket berdasarkan priority
     * Contoh: Ticket::byPriority('high')->get()
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope untuk filter ticket yang di-assign ke agent tertentu
     * Contoh: Ticket::assignedTo($agentId)->get()
     */
    public function scopeAssignedTo($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope untuk filter ticket milik customer tertentu
     * Contoh: Ticket::byCustomer($customerId)->get()
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    // ==================== HELPER METHODS ====================
    // Method boolean untuk cek status dengan mudah

    /**
     * Cek apakah ticket masih open
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Cek apakah ticket sedang in progress
     * @return bool
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Cek apakah ticket sudah resolved
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Cek apakah ticket sudah closed
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Cek apakah ticket sudah di-assign ke agent
     * @return bool
     */
    public function isAssigned(): bool
    {
        return !is_null($this->agent_id);
    }

    /**
     * Dapatkan class CSS untuk badge status (untuk UI)
     * Dipakai di view untuk tampilkan warna badge yang sesuai
     * @return string - Class Tailwind CSS
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'open' => 'bg-blue-100 text-blue-800',
            'assigned' => 'bg-indigo-100 text-indigo-800',
            'in_progress' => 'bg-yellow-100 text-yellow-800',
            'resolved' => 'bg-green-100 text-green-800',
            'closed' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Dapatkan class CSS untuk badge priority (untuk UI)
     * @return string - Class Tailwind CSS
     */
    public function getPriorityBadgeClass(): string
    {
        return match ($this->priority) {
            'low' => 'bg-gray-100 text-gray-800',
            'medium' => 'bg-blue-100 text-blue-800',
            'high' => 'bg-orange-100 text-orange-800',
            'critical' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
