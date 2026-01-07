<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function poll(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['serverNow' => now()->toISOString(), 'items' => []]);
        }

        $openUnassignedCount = null;
        $customerOpenCount = null;
        if (in_array($user->role, ['admin', 'super_admin'], true)) {
            $base = Ticket::query();
            if ($user->role === 'admin') {
                if ($user->category_id) {
                    $base->where('category_id', $user->category_id);
                } else {
                    $base->whereRaw('1=0');
                }
            }

            $openUnassignedCount = (int) (clone $base)
                ->where('status', 'open')
                ->whereNull('agent_id')
                ->count();
        } elseif ($user->role === 'customer') {
            $customerOpenCount = (int) Ticket::query()
                ->where('customer_id', $user->id)
                ->where('status', 'open')
                ->count();
        }

        $sinceRaw = (string) $request->query('since', '');
        try {
            $since = $sinceRaw !== '' ? Carbon::parse($sinceRaw) : now();
        } catch (\Throwable $e) {
            $since = now();
        }

        // Prevent very old timestamps from spamming notifications
        if ($since->lessThan(now()->subDays(2))) {
            $since = now()->subDays(2);
        }

        $query = Ticket::query()->select(['id', 'firebase_id', 'title', 'status', 'category_id', 'customer_id', 'agent_id', 'created_at', 'updated_at']);

        if ($user->role === 'super_admin') {
            // all tickets
        } elseif ($user->role === 'admin') {
            if ($user->category_id) {
                $query->where('category_id', $user->category_id);
            } else {
                $query->whereRaw('1=0');
            }
        } elseif ($user->role === 'agent') {
            $query->where('agent_id', $user->id);
        } else {
            // customer
            $query->where('customer_id', $user->id);
        }

        $query->where(function ($q) use ($since) {
            $q->where('created_at', '>', $since)->orWhere('updated_at', '>', $since);
        });

        $tickets = $query->orderByDesc('updated_at')->limit(10)->get();

        $items = [];
        foreach ($tickets as $t) {
            $isNew = $t->created_at && $t->created_at->gt($since);

            $ticketId = (string) ($t->firebase_id ?: $t->id);
            $status = (string) ($t->status ?? '');
            $agentId = $t->agent_id ? (string) $t->agent_id : null;
            $createdAt = $t->created_at ? $t->created_at->toISOString() : null;
            $updatedAt = $t->updated_at ? $t->updated_at->toISOString() : null;

            if ($isNew) {
                // Customer should not receive "Tiket Baru" notifications.
                if ((string) $user->role === 'customer') {
                    continue;
                }

                // Notifikasi "tiket baru" hanya berlaku selama ticket masih Open & belum ditugaskan.
                // Jika status sudah berubah, jangan tampilkan sebagai "tiket baru".
                $isStillOpenUnassigned = ((string) ($t->status ?? '') === 'open') && ($t->agent_id === null);
                if (!$isStillOpenUnassigned) {
                    $items[] = [
                        'event' => 'ticket_updated',
                        'type' => 'success',
                        'title' => (string) ($t->title ?? 'Ticket'),
                        'message' => 'Update: ' . ucfirst(str_replace('_', ' ', (string) ($t->status ?? '-'))),
                        'ticket_id' => $ticketId,
                        'status' => $status,
                        'agent_id' => $agentId,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ];
                    continue;
                }

                $countMsg = null;
                if ($openUnassignedCount !== null) {
                    $countMsg = 'Saat ini ada ' . $openUnassignedCount . ' tiket belum ditugaskan';
                }

                $items[] = [
                    'event' => 'ticket_created',
                    'type' => 'info',
                    'title' => 'Tiket Baru',
                    'message' => $countMsg ?: 'Tiket baru masuk',
                    'ticket_id' => $ticketId,
                    'status' => $status,
                    'agent_id' => $agentId,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
                continue;
            }

            // Update (most commonly status change)
            $items[] = [
                'event' => 'ticket_updated',
                'type' => 'success',
                'title' => (string) ($t->title ?? 'Ticket'),
                'message' => 'Update: ' . ucfirst(str_replace('_', ' ', (string) ($t->status ?? '-'))),
                'ticket_id' => $ticketId,
                'status' => $status,
                'agent_id' => $agentId,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];
        }

        // Admin summary: always show count of Open & belum ditugaskan
        if ($openUnassignedCount !== null && $openUnassignedCount > 0) {
            $items[] = [
                'event' => 'admin_open_summary',
                'type' => 'info',
                'title' => 'Tiket Open',
                'message' => 'Ada ' . $openUnassignedCount . ' tiket open belum ditugaskan',
                'ticket_id' => null,
                'status' => 'open',
                'agent_id' => null,
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        // Customer summary: remind open tickets
        if ($customerOpenCount !== null && $customerOpenCount > 0) {
            $items[] = [
                'event' => 'customer_open_summary',
                'type' => 'warning',
                'title' => 'Tiket Anda Masih Open',
                'message' => 'Ada ' . $customerOpenCount . ' tiket belum diproses',
                'ticket_id' => null,
                'status' => 'open',
                'agent_id' => null,
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        return response()->json([
            'serverNow' => now()->toISOString(),
            'items' => array_values($items),
        ]);
    }
}
