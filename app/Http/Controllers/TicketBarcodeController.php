<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Firebase\TicketService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

class TicketBarcodeController extends Controller
{
    public function __construct(private readonly TicketService $ticketService) {}

    private function formatJakartaLabel(?string $isoLike): string
    {
        if (!$isoLike) {
            return '';
        }

        // Our services often produce "*_at_iso" as a Jakarta-local datetime string
        // via Carbon->setTimezone('Asia/Jakarta')->toDateTimeString(), which has no offset.
        // Treat that format as already-local to avoid adding +7 hours again.
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $isoLike) === 1) {
            try {
                return Carbon::createFromFormat('Y-m-d H:i:s', $isoLike, 'Asia/Jakarta')
                    ->format('d/m/Y H:i');
            } catch (\Throwable $e) {
                return $isoLike;
            }
        }

        try {
            return Carbon::parse($isoLike)->setTimezone('Asia/Jakarta')->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return $isoLike;
        }
    }

    private function resolvedAtFromTicket(array $ticketData): ?string
    {
        $history = $ticketData['status_history'] ?? null;
        if (is_array($history)) {
            $resolvedIso = null;
            foreach ($history as $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (((string)($row['status'] ?? '')) === 'resolved' && !empty($row['changed_at_iso'])) {
                    $resolvedIso = (string) $row['changed_at_iso'];
                }
            }
            if ($resolvedIso) {
                return $resolvedIso;
            }
        }

        $status = (string)($ticketData['status'] ?? '');
        if (in_array($status, ['resolved', 'closed'], true) && !empty($ticketData['updated_at_iso'])) {
            return (string) $ticketData['updated_at_iso'];
        }

        return null;
    }

    /**
     * @return array{ticket: array<string,mixed>, user: \App\Models\User}
     */
    private function authorizeResolvedTicket(string $ticket)
    {
        $ticketData = $this->ticketService->getTicket($ticket);
        if (!$ticketData) {
            abort(404);
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        // Permission: admin/super_admin can view all, agent only assigned, customer only owner
        if (($user->role ?? null) === 'customer') {
            if ((string) ($ticketData['customer_id'] ?? '') !== (string) $user->id) {
                abort(403);
            }
        } elseif (($user->role ?? null) === 'agent') {
            if ((string) ($ticketData['agent_id'] ?? '') !== (string) $user->id) {
                abort(403);
            }
        } elseif (!in_array(($user->role ?? null), ['admin', 'super_admin'], true)) {
            abort(403);
        }

        // Only available after resolved (or closed)
        $status = (string) ($ticketData['status'] ?? 'open');
        if (!in_array($status, ['resolved', 'closed'], true)) {
            abort(422, 'Barcode hanya tersedia ketika ticket sudah Resolved (Selesai).');
        }

        return ['ticket' => $ticketData, 'user' => $user];
    }

    public function scan()
    {
        return view('barcodes.scan');
    }

    public function show(Request $request, string $ticket)
    {
        $auth = $this->authorizeResolvedTicket($ticket);
        $ticketData = $auth['ticket'];

        // Ensure customer/agent names are available
        try {
            if (empty($ticketData['customer_name']) && !empty($ticketData['customer_id'])) {
                $u = User::find($ticketData['customer_id']);
                if ($u) {
                    $ticketData['customer_name'] = $u->name;
                }
            }
            if (empty($ticketData['agent_name']) && !empty($ticketData['agent_id'])) {
                $a = User::find($ticketData['agent_id']);
                if ($a) {
                    $ticketData['agent_name'] = $a->name;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $ticketData['qr_payload'] = 'cloudticket:' . ($ticketData['id'] ?? $ticket);
        $autoDownload = $request->boolean('download');

        $ticket = $ticketData;

        return view('barcodes.show', compact('ticket', 'autoDownload'));
    }

    public function download(string $ticket)
    {
        $auth = $this->authorizeResolvedTicket($ticket);
        $ticketData = $auth['ticket'];

        $ticketId = (string) ($ticketData['id'] ?? $ticket);
        $payload = 'cloudticket:' . $ticketId;

        // Use a lightweight external QR PNG generator and stream it as a download
        $url = 'https://api.qrserver.com/v1/create-qr-code/?size=640x640&ecc=M&margin=1&data=' . urlencode($payload);
        $png = @file_get_contents($url);
        if ($png === false || $png === '') {
            abort(502, 'Gagal membuat barcode.');
        }

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="barcode-ticket-' . $ticketId . '.png"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function data(string $ticket)
    {
        $ticketData = $this->ticketService->getTicket($ticket);
        if (!$ticketData) {
            abort(404);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Only allow data access after resolved/closed
        $status = (string) ($ticketData['status'] ?? 'open');
        if (!in_array($status, ['resolved', 'closed'], true)) {
            return response()->json(['message' => 'Barcode belum tersedia untuk ticket ini.'], 422);
        }

        // Permission: admin/super_admin can view all, agent only assigned, customer only owner
        if (($user->role ?? null) === 'customer') {
            if ((string) ($ticketData['customer_id'] ?? '') !== (string) $user->id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        } elseif (($user->role ?? null) === 'agent') {
            if ((string) ($ticketData['agent_id'] ?? '') !== (string) $user->id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        } elseif (!in_array(($user->role ?? null), ['admin', 'super_admin'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Ensure names
        try {
            if (empty($ticketData['customer_name']) && !empty($ticketData['customer_id'])) {
                $u = User::find($ticketData['customer_id']);
                if ($u) {
                    $ticketData['customer_name'] = $u->name;
                }
            }
            if (empty($ticketData['agent_name']) && !empty($ticketData['agent_id'])) {
                $a = User::find($ticketData['agent_id']);
                if ($a) {
                    $ticketData['agent_name'] = $a->name;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $resolvedAtIso = $this->resolvedAtFromTicket($ticketData);
        $resolvedAtLabel = $this->formatJakartaLabel($resolvedAtIso);

        return response()->json([
            'id' => (string) ($ticketData['id'] ?? $ticket),
            'title' => (string) ($ticketData['title'] ?? ''),
            'category' => (string) ($ticketData['category'] ?? ''),
            'customer' => (string) ($ticketData['customer_name'] ?? ''),
            'agent' => (string) ($ticketData['agent_name'] ?? ''),
            'status' => (string) ($ticketData['status'] ?? ''),
            'resolved_at' => $resolvedAtIso,
            'resolved_at_label' => $resolvedAtLabel,
        ]);
    }
}
