<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Category;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Services\Firebase\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * TicketController - Controller untuk mengelola Tickets
 *
 * ALUR SEDERHANA:
 * 1. Customer buat ticket (create/store)
 * 2. Admin lihat semua tickets & assign ke agent (index)
 * 3. Agent update status & tambah komentar (show/updateStatus)
 * 4. Semua pihak bisa lihat progress real-time
 */
class TicketController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        // Inject TicketService untuk akses Firestore & Laravel DB
        $this->ticketService = $ticketService;
    }

    /**
     * STEP 1: Tampilkan daftar tickets
     * ATURAN BARU:
     * - Admin: Lihat SEMUA tickets
     * - Agent: Lihat tickets yang DI-ASSIGN ke dia saja (assigned_agent_id = user->id)
     * - Customer: Lihat tickets MEREKA saja
     */
    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Cek role user untuk filter tickets
        if ($user->role === 'admin') {
            // Admin lihat SEMUA tickets
            $tickets = $this->ticketService->getAllTickets();
        } elseif ($user->role === 'agent') {
            // Agent HANYA lihat tickets yang DI-ASSIGN ke dia
            // Jadi kalau belum di-assign, agent tidak lihat
            $tickets = $this->ticketService->getTicketsByAgent($user->id);
        } else {
            // Customer hanya lihat milik dia
            $tickets = $this->ticketService->getTicketsByCustomer($user->id);
        }

        return view('tickets.index', compact('tickets'));
    }

    /**
     * STEP 2: Form buat ticket baru (Customer)
     */
    public function create()
    {
        // Ambil semua kategori untuk dropdown
        $categories = Category::orderBy('name')->get();
        return view('tickets.create', compact('categories'));
    }

    /**
     * STEP 3: Simpan ticket baru ke Firestore & Laravel DB
     * FLOW: Form → Validasi → Save to Firestore → Sync to Laravel DB → Upload Files
     */
    public function store(Request $request)
    {
        // Validasi input form
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'priority'    => 'required|in:low,medium,high',
            'attachments.*' => 'nullable|file|max:2048', // Max 2MB per file
        ]);

        // Siapkan data ticket
        $data = $request->only(['title', 'description', 'priority']);

        // Ambil nama kategori
        $cat = Category::find($request->input('category_id'));
        if ($cat) {
            $data['category_id'] = (string)$cat->id;
            $data['category'] = $cat->name;
        }

        // Set customer_id dari user yang login
        $data['customer_id'] = Auth::id();

        // Simpan ke Firestore (akan auto-sync ke Laravel DB)
        $ticketId = $this->ticketService->createTicket($data);

        // Upload attachments jika ada
        $files = $request->file('attachments', []);
        if (is_array($files) && count($files) > 0) {
            $attachments = $this->ticketService->uploadAttachments($files, $ticketId);
            if (count($attachments) > 0) {
                // Update ticket dengan info attachments
                $this->ticketService->updateTicket($ticketId, ['attachments' => $attachments]);
            }
        }

        return redirect()->route('tickets.show', $ticketId)->with('success', 'Ticket berhasil dibuat');
    }

    public function show(string $id)
    {
        $ticket = $this->ticketService->getTicket($id);
        if (!$ticket) {
            abort(404);
        }

        $comments = $this->ticketService->getComments($id);

        // Generate temporary URLs for attachments (if any)
        if (isset($ticket['attachments']) && is_array($ticket['attachments'])) {
            foreach ($ticket['attachments'] as &$att) {
                if (isset($att['path']) && is_string($att['path'])) {
                    $att['temp_url'] = $this->ticketService->getAttachmentTemporaryUrl($id, $att['path']);
                }
            }
        }

        // Generate temporary URLs for attachments that may be present in comments (evidence)
        if (isset($comments) && is_array($comments)) {
            foreach ($comments as &$c) {
                if (isset($c['attachments']) && is_array($c['attachments'])) {
                    foreach ($c['attachments'] as &$attc) {
                        if (isset($attc['path']) && is_string($attc['path'])) {
                            $attc['temp_url'] = $this->ticketService->getAttachmentTemporaryUrl($id, $attc['path']);
                        }
                    }
                }
            }
            unset($c, $attc);
        }

        // Ambil list agents untuk dropdown (admin only)
        $agents = [];
        if (Auth::user()->role === 'admin') {
            $agents = User::where('role', 'agent')->orderBy('name')->get();
        }

        // Ensure customer and agent names are available for the view
        try {
            if (empty($ticket['customer_name']) && !empty($ticket['customer_id'])) {
                $u = User::find($ticket['customer_id']);
                if ($u) {
                    $ticket['customer_name'] = $u->name;
                }
            }

            if (empty($ticket['agent_name']) && !empty($ticket['agent_id'])) {
                $a = User::find($ticket['agent_id']);
                if ($a) {
                    $ticket['agent_name'] = $a->name;
                }
            }
        } catch (\Throwable $e) {
            // ignore lookup failures - view will fallback to ids
        }

        return view('tickets.show', compact('ticket', 'comments', 'agents'));
    }

    /**
     * STEP 4: Edit ticket (HANYA CUSTOMER YANG BUAT & MASIH STATUS OPEN)
     * Admin/Agent TIDAK BOLEH edit ticket customer
     * Customer TIDAK BOLEH edit ticket yang sudah diproses (status bukan 'open')
     */
    public function edit(string $id)
    {
        $ticket = $this->ticketService->getTicket($id);
        if (!$ticket) {
            abort(404);
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // ATURAN 1: Hanya customer yang membuat ticket yang boleh edit
        if ((string)($ticket['customer_id'] ?? '') !== (string)$user->id) {
            return redirect()->route('tickets.show', $id)
                ->with('error', 'Anda tidak bisa mengedit ticket orang lain. Admin/Agent hanya bisa update status.');
        }

        // ATURAN 2: Hanya ticket dengan status 'open' yang boleh diedit
        // Kenapa? Karena jika sudah di-assign/dikerjakan, tidak boleh ubah konten
        // Analogi: Laporan sudah masuk ke manager, tidak bisa diubah lagi
        if (($ticket['status'] ?? 'open') !== 'open') {
            return redirect()->route('tickets.show', $id)
                ->with('error', 'Ticket yang sudah diproses tidak bisa diedit. Gunakan komentar untuk update tambahan.');
        }

        $categories = Category::orderBy('name')->get();
        return view('tickets.edit', compact('ticket', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'priority'    => 'required|in:low,medium,high',
        ]);

        $data = $request->only(['title', 'description', 'priority']);
        $cat = Category::find($request->input('category_id'));
        if ($cat) {
            $data['category_id'] = (string)$cat->id;
            $data['category'] = $cat->name;
        }

        $this->ticketService->updateTicket($id, $data);

        return redirect()->route('tickets.show', $id)->with('success', 'Ticket berhasil diupdate');
    }

    public function destroy(string $id)
    {
        $ticket = $this->ticketService->getTicket($id);
        if (!$ticket) {
            abort(404);
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Admin bisa hapus tiket dengan status apapun
        if ($user->role === 'admin') {
            $this->ticketService->deleteTicket($id);
            return redirect()->route('tickets.index')->with('success', 'Ticket berhasil dihapus');
        }

        // Customer hanya bisa hapus tiket mereka sendiri dan hanya jika status masih 'open'
        if ((string)($ticket['customer_id'] ?? '') !== (string)$user->id) {
            return redirect()->route('tickets.show', $id)
                ->with('error', 'Anda tidak bisa menghapus ticket orang lain.');
        }

        if (($ticket['status'] ?? 'open') !== 'open') {
            return redirect()->route('tickets.show', $id)
                ->with('error', 'Ticket yang sudah diproses tidak bisa dihapus.');
        }

        $this->ticketService->deleteTicket($id);
        return redirect()->route('tickets.index')->with('success', 'Ticket berhasil dihapus');
    }

    /**
     * ASSIGN TICKET KE AGENT (Admin only)
     * ALUR: Admin pilih agent dari dropdown → Klik "Tugaskan" → Ticket masuk ke agent
     */
    public function assignAgent(Request $request, string $id)
    {
        // Prevent re-assigning / reverting status once ticket is already finished
        try {
            $ticket = $this->ticketService->getTicket($id);
            $cur = $ticket['status'] ?? null;
            if (in_array($cur, ['resolved', 'closed'], true)) {
                return back()->with('error', 'Ticket yang sudah Resolved/Closed tidak bisa dikembalikan ke Assigned (Ditugaskan).');
            }
        } catch (\Throwable $e) {
            // ignore - validation below still applies
        }

        // Validasi: agent_id harus valid dan role = agent
        $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        // Cek apakah user yang dipilih benar-benar agent
        $agent = User::find($request->agent_id);
        if (!$agent || $agent->role !== 'agent') {
            return back()->with('error', 'User yang dipilih bukan agent.');
        }

        // Update ticket: assign to agent & set status to 'assigned'
        // 'assigned' is a special status indicating admin assigned the ticket to an agent.
        $this->ticketService->updateTicket($id, [
            'agent_id' => (int)$request->agent_id, // Field database: agent_id
            'status' => 'assigned',
        ]);

        return back()->with('success', "Ticket berhasil ditugaskan ke {$agent->name}");
    }

    public function updateStatus(Request $request, string $id)
    {
        // Basic validation for status field
        $request->validate([
            'status' => 'required|in:open,assigned,in_progress,resolved,closed',
        ]);

        $user = Auth::user();

        // Disallow reverting to 'open' once created/assigned
        if ($request->status === 'open') {
            return back()->with('error', 'Status tidak dapat dikembalikan ke Open.');
        }

        // Agents cannot set status to 'assigned'
        if ($user && $user->role === 'agent' && $request->status === 'assigned') {
            return back()->with('error', 'Agent tidak dapat mengubah status menjadi Assigned.');
        }

        // Load current status (from DB or Firestore)
        $currentStatus = null;
        try {
            $found = $this->ticketService->findTicket($id);
            if ($found instanceof \App\Models\Ticket) {
                $currentStatus = $found->status;
            } elseif (is_object($found) && method_exists($found, 'exists') && $found->exists()) {
                $data = $found->data();
                $currentStatus = $data['status'] ?? null;
            }
        } catch (\Throwable $e) {
            // ignore and proceed - safety checks below will use null
            $currentStatus = null;
        }

        // If ticket already closed, agents cannot change status at all
        if ($user && $user->role === 'agent' && $currentStatus === 'closed') {
            return back()->with('error', 'Ticket sudah ditutup; Anda tidak dapat mengubah status.');
        }

        // Disallow reverting from resolved/closed back to assigned
        if ($request->status === 'assigned' && in_array($currentStatus, ['resolved', 'closed'], true)) {
            return back()->with('error', 'Ticket yang sudah Resolved/Closed tidak bisa dikembalikan ke Assigned (Ditugaskan).');
        }

        // Business rules:
        // - Admin cannot set ticket to 'in_progress' or 'resolved'. Admin may set 'assigned' or 'closed'.
        // - Admin may set 'closed' only when current status is 'resolved' (agent resolved it first).
        // - Agent may set 'in_progress' and 'resolved', but not 'assigned' or 'open'.
        if ($user && $user->role === 'admin') {
            if (in_array($request->status, ['in_progress', 'resolved'])) {
                return back()->with('error', 'Admin tidak boleh mengubah status menjadi In Progress atau Resolved. Biarkan agent yang mengubahnya.');
            }
            if ($request->status === 'closed' && $currentStatus !== 'resolved') {
                return back()->with('error', 'Hanya ticket yang sudah diselesaikan oleh agent (Resolved) yang dapat ditutup oleh admin.');
            }
        }

        if ($user && $user->role === 'agent') {
            if (in_array($request->status, ['assigned', 'open'])) {
                return back()->with('error', 'Agent tidak dapat mengubah status menjadi Assigned atau Open.');
            }
            // Agent cannot directly close a ticket unless it is already marked as 'resolved'
            if ($request->status === 'closed' && $currentStatus !== 'resolved') {
                return back()->with('error', 'Agent tidak dapat menutup ticket secara langsung. Tandai sebagai Resolved terlebih dahulu.');
            }
        }

        // Other roles are not allowed to change status
        if ($user && !in_array($user->role, ['admin', 'agent'])) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengubah status.');
        }

        // If agent is resolving, require evidence and note
        if ($user && $user->role === 'agent' && $request->status === 'resolved') {
            $request->validate([
                'evidence_note' => 'required|string|max:2000',
                'evidence.*' => 'required|file|max:5120',
            ]);

            // Upload evidence files
            $files = $request->file('evidence', []);
            $attachments = [];
            if (is_array($files) && count($files) > 0) {
                $attachments = $this->ticketService->uploadAttachments($files, $id);
            }

            // Create a public TicketComment recording the evidence and note
            $ticketModel = Ticket::where('firebase_id', $id)->first();
            $commentData = [
                'ticket_id' => $ticketModel ? $ticketModel->id : null,
                'user_id' => $user->id,
                'comment' => $request->input('evidence_note'),
                'attachments' => $attachments,
                'is_internal' => false,
            ];
            try {
                TicketComment::create($commentData);
            } catch (\Throwable $e) {
                logger()->warning('Failed to create evidence comment: ' . $e->getMessage());
            }
        }

        $this->ticketService->updateTicket($id, [
            'status' => $request->status,
        ]);

        return back()->with('success', 'Status ticket berhasil diperbarui');
    }

    public function downloadAttachment(Request $request, string $ticketId, string $path)
    {
        // Signed route middleware will validate signature if route is signed, but we also
        // verify ticket and that attachment exists in ticket metadata.
        $decodedPath = base64_decode($path);
        $ticket = $this->ticketService->getTicket($ticketId);
        if (!$ticket) {
            abort(404);
        }

        $found = null;
        if (isset($ticket['attachments']) && is_array($ticket['attachments'])) {
            foreach ($ticket['attachments'] as $att) {
                if (($att['path'] ?? null) === $decodedPath) {
                    $found = $att;
                    break;
                }
            }
        }

        if (!$found) {
            abort(404);
        }

        // permission: only customer owner, assigned agent, or admin can download
        $user = Auth::user();
        if (!$user) abort(403);

        $isOwner = ((string)($ticket['customer_id'] ?? '') === (string)$user->id);
        $isAssigned = ((string)($ticket['assigned_agent_id'] ?? '') === (string)$user->id);
        if (!($isOwner || $isAssigned || in_array($user->role, ['admin', 'agent']))) {
            abort(403);
        }

        // Serve local file
        if (($found['storage'] ?? '') === 'local') {
            $disk = config('attachments.local_disk', 'local');
            $fullPath = Storage::disk($disk)->path($found['path']);
            if (!file_exists($fullPath)) {
                abort(404);
            }

            return response()->streamDownload(function () use ($fullPath) {
                readfile($fullPath);
            }, $found['name'] ?? basename($fullPath), ['Content-Type' => $found['content_type'] ?? 'application/octet-stream']);
        }

        // If firebase storage
        if (($found['storage'] ?? '') === 'firebase') {
            $temp = $this->ticketService->getAttachmentTemporaryUrl($ticketId, $found['path']);
            if ($temp) {
                return redirect($temp);
            }
        }

        abort(404);
    }
}
