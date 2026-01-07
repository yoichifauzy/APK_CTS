<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function superIndex(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $status = trim((string) $request->query('status', ''));

        $tickets = Ticket::query()
            ->with(['customer:id,name', 'agent:id,name', 'category:id,name', 'assignedBy:id,name'])
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.super', compact('tickets', 'from', 'to', 'status'));
    }

    public function superExportCsv(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $status = trim((string) $request->query('status', ''));

        $query = Ticket::query()
            ->with(['customer:id,name', 'agent:id,name', 'category:id,name', 'assignedBy:id,name'])
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->orderByDesc('created_at');

        $filename = 'laporan-tiket-superadmin-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['No', 'Judul', 'Customer', 'Teknisi', 'Tanggal & Jam', 'Prioritas', 'Kategori', 'Status', 'Lokasi', 'Lampiran']);
            $i = 1;
            foreach ($query->cursor() as $t) {
                $hasAtt = is_array($t->attachments) && count($t->attachments) > 0;
                fputcsv($out, [
                    $i++,
                    (string) ($t->title ?? '-'),
                    (string) optional($t->customer)->name,
                    (string) optional($t->agent)->name,
                    optional($t->created_at)->format('Y-m-d H:i:s'),
                    (string) ($t->priority ?? '-'),
                    (string) optional($t->category)->name,
                    (string) ($t->status ?? '-'),
                    (string) ($t->location ?? '-'),
                    $hasAtt ? 'Ada' : 'Tidak',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function superExportPdf(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $status = trim((string) $request->query('status', ''));

        $tickets = Ticket::query()
            ->with(['customer:id,name', 'agent:id,name', 'category:id,name', 'assignedBy:id,name'])
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        $html = view('admin.reports.super_export', compact('tickets'))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'laporan-tiket-superadmin-' . now()->format('Ymd-His') . '.pdf';
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));
        $q = trim((string) $request->query('q', ''));

        $tickets = Ticket::query()
            ->with(['customer:id,name', 'agent:id,name', 'category:id,name'])
            ->when($user->category_id, fn($qb) => $qb->where('category_id', $user->category_id), fn($qb) => $qb->whereRaw('1 = 0'))
            ->when($status !== '', fn($qb) => $qb->where('status', $status))
            ->when($priority !== '', fn($qb) => $qb->where('priority', $priority))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', '%' . $q . '%')
                        ->orWhere('location', 'like', '%' . $q . '%')
                        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', '%' . $q . '%'))
                        ->orWhereHas('agent', fn($a) => $a->where('name', 'like', '%' . $q . '%'));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.index', compact('tickets', 'status', 'priority', 'q'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));
        $q = trim((string) $request->query('q', ''));

        $query = Ticket::query()
            ->with(['customer:id,name', 'agent:id,name', 'category:id,name'])
            ->when($user->category_id, fn($qb) => $qb->where('category_id', $user->category_id), fn($qb) => $qb->whereRaw('1 = 0'))
            ->when($status !== '', fn($qb) => $qb->where('status', $status))
            ->when($priority !== '', fn($qb) => $qb->where('priority', $priority))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', '%' . $q . '%')
                        ->orWhere('location', 'like', '%' . $q . '%')
                        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', '%' . $q . '%'))
                        ->orWhereHas('agent', fn($a) => $a->where('name', 'like', '%' . $q . '%'));
                });
            })
            ->orderByDesc('created_at');

        $filename = 'laporan-tiket-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['No', 'Prioritas', 'Lokasi', 'Status', 'Judul', 'Nama Customer', 'Lampiran', 'Tanggal Masuk', 'Agent']);

            $i = 1;
            foreach ($query->cursor() as $t) {
                $hasAtt = is_array($t->attachments) && count($t->attachments) > 0;
                fputcsv($out, [
                    $i++,
                    (string) ($t->priority ?? '-'),
                    (string) ($t->location ?? '-'),
                    (string) ($t->status ?? '-'),
                    (string) ($t->title ?? '-'),
                    (string) optional($t->customer)->name,
                    $hasAtt ? 'Ada' : 'Tidak',
                    optional($t->created_at)->format('Y-m-d H:i:s'),
                    optional($t->agent)->name,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));
        $q = trim((string) $request->query('q', ''));

        $tickets = Ticket::query()
            ->with(['customer:id,name', 'agent:id,name', 'category:id,name'])
            ->when($user->category_id, fn($qb) => $qb->where('category_id', $user->category_id), fn($qb) => $qb->whereRaw('1 = 0'))
            ->when($status !== '', fn($qb) => $qb->where('status', $status))
            ->when($priority !== '', fn($qb) => $qb->where('priority', $priority))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', '%' . $q . '%')
                        ->orWhere('location', 'like', '%' . $q . '%')
                        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', '%' . $q . '%'))
                        ->orWhereHas('agent', fn($a) => $a->where('name', 'like', '%' . $q . '%'));
                });
            })
            ->orderByDesc('created_at')
            ->get();

        $html = view('admin.reports.pdf', [
            'tickets' => $tickets,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'laporan-tiket-' . now()->format('Ymd-His') . '.pdf';
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function print(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));
        $q = trim((string) $request->query('q', ''));

        $tickets = Ticket::query()
            ->with(['customer:id,name', 'agent:id,name', 'category:id,name'])
            ->when($user->category_id, fn($qb) => $qb->where('category_id', $user->category_id), fn($qb) => $qb->whereRaw('1 = 0'))
            ->when($status !== '', fn($qb) => $qb->where('status', $status))
            ->when($priority !== '', fn($qb) => $qb->where('priority', $priority))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', '%' . $q . '%')
                        ->orWhere('location', 'like', '%' . $q . '%')
                        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', '%' . $q . '%'))
                        ->orWhereHas('agent', fn($a) => $a->where('name', 'like', '%' . $q . '%'));
                });
            })
            ->orderByDesc('created_at')
            ->get();

        return view('admin.reports.print', [
            'tickets' => $tickets,
        ]);
    }

    public function superPrint(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $status = trim((string) $request->query('status', ''));

        $tickets = Ticket::query()
            ->with(['customer:id,name', 'agent:id,name', 'category:id,name', 'assignedBy:id,name'])
            ->when($from, fn($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('created_at', '<=', $to))
            ->when($status !== '', fn($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.reports.super_export', [
            'tickets' => $tickets,
        ]);
    }
}
