@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-qrcode me-2"></i>Barcode Ticket
@endsection

@section('title', 'Barcode Ticket')

@section('content')
@php
    $ticketId = $ticket['id'] ?? '';
    $title = $ticket['title'] ?? '';
    $category = $ticket['category'] ?? '';
    $customer = $ticket['customer_name'] ?? '-';
    $agent = $ticket['agent_name'] ?? ($ticket['agent_id'] ?? '-');
    $payload = $ticket['qr_payload'] ?? ('cloudticket:'.$ticketId);
@endphp

<style>
    .qr-frame { border: 1px solid #e5e7eb; border-radius: 16px; background: #fff; box-shadow: 0 10px 24px rgba(15,23,42,0.06); }
    .qr-canvas-wrap { display:flex; align-items:center; justify-content:center; padding: 18px; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h5 mb-1">Barcode (QR) untuk Ticket</h1>
            <div class="text-muted">Tempelkan di mesin, lalu scan untuk melihat data ticket.</div>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('tickets.show', $ticketId) }}">Kembali</a>
            <button class="btn btn-primary btn-sm" type="button" id="btn-download">
                <i class="fa-solid fa-download me-2"></i>Download Barcode
            </button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="qr-frame">
                <div class="p-3 border-bottom">
                    <div class="fw-semibold">Preview Barcode</div>
                    <div class="text-muted small">ID: {{ $ticketId }}</div>
                </div>
                <div class="qr-canvas-wrap">
                    <canvas id="qr-canvas" width="320" height="320" aria-label="QR Code"></canvas>
                </div>
                <div class="p-3 border-top text-muted small">
                    Scan hasil barcode untuk lihat: Judul, Kategori, Pelanggan, Agent.
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-circle-info me-2"></i>Data Ticket</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-4 text-muted">Judul</dt>
                        <dd class="col-8">{{ $title }}</dd>

                        <dt class="col-4 text-muted">Kategori</dt>
                        <dd class="col-8">{{ $category }}</dd>

                        <dt class="col-4 text-muted">Pelanggan</dt>
                        <dd class="col-8">{{ $customer }}</dd>

                        <dt class="col-4 text-muted">Agent</dt>
                        <dd class="col-8">{{ $agent }}</dd>
                    </dl>
                </div>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                Untuk scan di aplikasi ini, gunakan menu <strong>Scan Barcode</strong> (customer).
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.4/build/qrcode.min.js"></script>
<script>
(function(){
    const payload = @json($payload);
    const canvas = document.getElementById('qr-canvas');
    const btn = document.getElementById('btn-download');

    if (!canvas || !window.QRCode) return;

    window.QRCode.toCanvas(canvas, payload, {
        errorCorrectionLevel: 'M',
        margin: 1,
        width: 320,
        color: {
            dark: '#111827',
            light: '#ffffff'
        }
    }, function(err){
        if (err) {
            console.error(err);
        }
    });

    function downloadPng(){
        try {
            const a = document.createElement('a');
            a.href = canvas.toDataURL('image/png');
            a.download = 'barcode-ticket-' + {{ json_encode((string)$ticketId) }} + '.png';
            document.body.appendChild(a);
            a.click();
            a.remove();
        } catch (e) {
            console.error(e);
        }
    }

    if (btn) btn.addEventListener('click', downloadPng);

    const auto = @json((bool)($autoDownload ?? false));
    if (auto) {
        setTimeout(downloadPng, 350);
    }
})();
</script>
@endsection
@endsection
