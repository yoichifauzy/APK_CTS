<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Firebase\TicketService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

class TicketDiscussionController extends Controller
{
    public function __construct(private readonly TicketService $ticketService) {}

    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role === 'admin') {
            $tickets = $this->ticketService->getAllTickets();
        } elseif ($user->role === 'agent') {
            $tickets = $this->ticketService->getTicketsByAgentAllStatuses((int) $user->id);
        } else {
            $tickets = $this->ticketService->getTicketsByCustomer((string) $user->id);
        }

        return view('discussions.index', compact('tickets'));
    }

    public function show(string $ticket)
    {
        $ticketData = $this->ticketService->getTicket($ticket);
        if (!$ticketData) {
            abort(404);
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Authorization by role
        if ($user->role === 'customer') {
            if ((string) ($ticketData['customer_id'] ?? '') !== (string) $user->id) {
                abort(403);
            }
        } elseif ($user->role === 'agent') {
            if ((string) ($ticketData['agent_id'] ?? '') !== (string) $user->id) {
                abort(403);
            }
        } elseif ($user->role !== 'admin') {
            abort(403);
        }

        $comments = $this->ticketService->getComments($ticket);

        // Generate temporary URLs for ticket attachments (if any)
        if (isset($ticketData['attachments']) && is_array($ticketData['attachments'])) {
            foreach ($ticketData['attachments'] as &$att) {
                if (isset($att['path']) && is_string($att['path'])) {
                    $att['temp_url'] = $this->ticketService->getAttachmentTemporaryUrl($ticket, $att['path']);
                }
            }
            unset($att);
        }

        // Generate temporary URLs for attachments that may be present in comments
        if (isset($comments) && is_array($comments)) {
            foreach ($comments as &$c) {
                if (isset($c['attachments']) && is_array($c['attachments'])) {
                    foreach ($c['attachments'] as &$attc) {
                        if (isset($attc['path']) && is_string($attc['path'])) {
                            $attc['temp_url'] = $this->ticketService->getAttachmentTemporaryUrl($ticket, $attc['path']);
                        }
                    }
                }
            }
            unset($c, $attc);
        }

        // Ensure customer and agent names are available for the view
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
            // ignore lookup failures
        }

        $ticket = $ticketData;

        return view('discussions.show', compact('ticket', 'comments'));
    }
}
