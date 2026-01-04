<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Firebase\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

class TicketCommentController extends Controller
{
    public function __construct(private readonly TicketService $ticketService) {}

    /**
     * TAMBAH KOMENTAR DI TICKET
     * - Customer bisa comment di ticket mereka
     * - Admin/Agent bisa comment di semua ticket
     */
    public function store(Request $request, string $ticket)
    {
        // Validasi input
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $ticketData = $this->ticketService->getTicket($ticket);
        if (!$ticketData) {
            abort(404);
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Cek permission: customer hanya di ticket mereka, agent/admin bisa semua
        if ((string)($ticketData['customer_id'] ?? '') !== (string)$user->id && !in_array($user->role, ['admin', 'agent'])) {
            abort(403);
        }

        // Simpan komentar - PENTING: field database name-nya 'comment', bukan 'message'
        $this->ticketService->addComment($ticket, [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role' => $user->role,
            'comment' => $request->string('message')->toString(), // Form field: 'message', DB field: 'comment'
        ]);

        $redirectTo = $request->input('redirect_to');
        if (is_string($redirectTo) && $redirectTo !== '') {
            // Allow relative redirects (preferred)
            if (str_starts_with($redirectTo, '/')) {
                return redirect()->to($redirectTo)->with('success', 'Komentar berhasil dikirim');
            }

            // Allow absolute redirects only when they point to the same host
            if (filter_var($redirectTo, FILTER_VALIDATE_URL)) {
                $host = (string) parse_url($redirectTo, PHP_URL_HOST);
                if ($host !== '' && $host === $request->getHost()) {
                    return redirect()->to($redirectTo)->with('success', 'Komentar berhasil dikirim');
                }
            }
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Komentar berhasil dikirim');
    }
}
