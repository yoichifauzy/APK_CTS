@extends('layouts.sidebar')

@section('page-title')
    <i class="fa-solid fa-qrcode me-2"></i>Scan Barcode
@endsection

@section('title', 'Scan Barcode')

@section('content')
<style>
    .scan-shell { border: 1px solid #e5e7eb; border-radius: 16px; background: #fff; box-shadow: 0 10px 24px rgba(15,23,42,0.06); overflow: hidden; }
    .scan-stage { position: relative; aspect-ratio: 1 / 1; width: 100%; max-width: 520px; margin: 0 auto; background: #0b1220; height: 520px; }
    .scan-overlay { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; pointer-events:none; }
    .scan-frame { width: 74%; height: 74%; border-radius: 18px; border: 2px solid rgba(255,255,255,0.65); position: relative; box-shadow: 0 0 0 9999px rgba(0,0,0,0.45); }
    .scan-corner { position:absolute; width: 28px; height: 28px; border-color: rgba(255,255,255,0.92); border-style: solid; }
    .c-tl { top: -2px; left: -2px; border-width: 4px 0 0 4px; border-top-left-radius: 16px; }
    .c-tr { top: -2px; right: -2px; border-width: 4px 4px 0 0; border-top-right-radius: 16px; }
    .c-bl { bottom: -2px; left: -2px; border-width: 0 0 4px 4px; border-bottom-left-radius: 16px; }
    .c-br { bottom: -2px; right: -2px; border-width: 0 4px 4px 0; border-bottom-right-radius: 16px; }

    .scan-line { position:absolute; left: 10px; right: 10px; height: 2px; background: rgba(59, 130, 246, 0.95); opacity: 0.9; box-shadow: 0 0 10px rgba(59,130,246,0.8); animation: scanMove 2.2s linear infinite; }
    @keyframes scanMove {
        0% { top: 10px; }
        50% { top: calc(100% - 12px); }
        100% { top: 10px; }
    }

    .scan-video { position:absolute; inset:0; }
    #reader { width: 100%; height: 100%; }
    #reader video { width: 100% !important; height: 100% !important; object-fit: cover !important; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h5 mb-1">Scan Barcode (QR)</h1>
            <div class="text-muted">Arahkan kamera ke barcode yang ditempel di mesin.</div>
        </div>
        <div>
            <button class="btn btn-primary btn-sm" id="btn-start"><i class="fa-solid fa-camera me-2"></i>Scan Barcode</button>
            <button class="btn btn-outline-secondary btn-sm" id="btn-stop" style="display:none;"><i class="fa-solid fa-stop me-2"></i>Stop</button>
        </div>
    </div>

    <div class="mb-3" style="max-width: 520px; margin: 0 auto;">
        <label class="form-label small text-muted" for="camera-select">Pilih Kamera</label>
        <select class="form-select form-select-sm" id="camera-select" disabled>
            <option value="">Memuat daftar kamera...</option>
        </select>
        <div class="form-text" id="diag" style="opacity:.85"></div>
    </div>

    <div class="scan-shell p-3">
        <div class="scan-stage mb-3">
            <div class="scan-video" id="reader"></div>
            <div class="scan-overlay">
                <div class="scan-frame">
                    <div class="scan-corner c-tl"></div>
                    <div class="scan-corner c-tr"></div>
                    <div class="scan-corner c-bl"></div>
                    <div class="scan-corner c-br"></div>
                    <div class="scan-line" id="scan-line" style="display:none;"></div>
                </div>
            </div>
        </div>

        <div class="alert alert-info mb-3" id="hint">
            Klik <strong>Scan Barcode</strong> untuk mulai.
        </div>

        <div class="card" id="result" style="display:none;">
            <div class="card-header"><i class="fa-solid fa-circle-info me-2"></i>Hasil Scan</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-4 text-muted">Judul</dt>
                    <dd class="col-8" id="r-title">-</dd>

                    <dt class="col-4 text-muted">Kategori</dt>
                    <dd class="col-8" id="r-category">-</dd>

                    <dt class="col-4 text-muted">Pelanggan</dt>
                    <dd class="col-8" id="r-customer">-</dd>

                    <dt class="col-4 text-muted">Agent</dt>
                    <dd class="col-8" id="r-agent">-</dd>

                    <dt class="col-4 text-muted">Resolved Pada</dt>
                    <dd class="col-8" id="r-resolved-at">-</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script src="{{ asset('vendor/html5-qrcode/html5-qrcode.min.js') }}"></script>
<script>
(function(){
    const barcodeDataUrlTemplate = @json(route('barcode.data', ['ticket' => '___TICKET___'], false));

    const btnStart = document.getElementById('btn-start');
    const btnStop = document.getElementById('btn-stop');
    const cameraSelect = document.getElementById('camera-select');
    const hint = document.getElementById('hint');
    const result = document.getElementById('result');
    const scanLine = document.getElementById('scan-line');

    const setHint = (type, html) => {
        hint.className = 'alert alert-' + type + ' mb-3';
        hint.innerHTML = html;
    };

    const diag = document.getElementById('diag');
    if (diag) {
        diag.textContent = 'Origin: ' + window.location.origin + ' | Secure Context: ' + (window.isSecureContext ? 'YES' : 'NO');
    }

    const setResult = (data) => {
        document.getElementById('r-title').textContent = data.title || '-';
        document.getElementById('r-category').textContent = data.category || '-';
        document.getElementById('r-customer').textContent = data.customer || '-';
        document.getElementById('r-agent').textContent = data.agent || '-';
        document.getElementById('r-resolved-at').textContent = data.resolved_at_label || data.resolved_at || '-';
        result.style.display = 'block';
    };

    const parseTicketId = (text) => {
        if (!text) return null;
        const raw = String(text).trim();
        if (raw.toLowerCase().startsWith('cloudticket:')) {
            return raw.substring('cloudticket:'.length);
        }

        // Also accept URLs like /tickets/{id}/barcode or /diskusi/{id}
        try {
            const u = new URL(raw, window.location.origin);
            const parts = u.pathname.split('/').filter(Boolean);
            const idx = parts.indexOf('tickets');
            if (idx >= 0 && parts[idx+1]) return parts[idx+1];
            const idx2 = parts.indexOf('diskusi');
            if (idx2 >= 0 && parts[idx2+1]) return parts[idx2+1];
        } catch (e) {
            // ignore
        }

        return null;
    };

    let qr = null;

    async function requestCameraPermission(){
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('getUserMedia not supported');
        }
        // Trigger permission prompt early; stop immediately after
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false
        });
        stream.getTracks().forEach(t => t.stop());
    }

    async function loadCameras(){
        if (!window.Html5Qrcode || !Html5Qrcode.getCameras) return;
        try {
            const cams = await Html5Qrcode.getCameras();
            cameraSelect.innerHTML = '';
            if (!cams || cams.length === 0) {
                cameraSelect.innerHTML = '<option value="">Tidak ada kamera</option>';
                cameraSelect.disabled = true;
                return;
            }

            cams.forEach((c) => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.label || ('Camera ' + c.id);
                cameraSelect.appendChild(opt);
            });

            // Try to auto-pick back camera if label hints it
            const preferIdx = cams.findIndex(c => /back|rear|environment/i.test(c.label || ''));
            if (preferIdx >= 0) cameraSelect.selectedIndex = preferIdx;

            cameraSelect.disabled = false;
        } catch (e) {
            cameraSelect.innerHTML = '<option value="">Tidak bisa membaca daftar kamera</option>';
            cameraSelect.disabled = true;
        }
    }

    async function stop(){
        try {
            if (qr) {
                await qr.stop();
                await qr.clear();
            }
        } catch (e) {
            // ignore
        }
        scanLine.style.display = 'none';
        btnStop.style.display = 'none';
        btnStart.disabled = false;
    }

    async function start(){
        if (!window.Html5Qrcode) {
            setHint('danger', 'Library scanner gagal dimuat.');
            return;
        }

        if (!window.isSecureContext) {
            setHint('danger', 'Browser memblok kamera karena halaman bukan <strong>secure context</strong>. Gunakan <strong>http://localhost:8000</strong> atau HTTPS.');
            return;
        }

        result.style.display = 'none';
        setHint('info', 'Meminta izin kamera...');

        btnStart.disabled = true;
        btnStop.style.display = 'inline-block';
        scanLine.style.display = 'block';

        try {
            await requestCameraPermission();
        } catch (e) {
            console.error(e);
            const msg = (e && (e.name || e.message)) ? (e.name || e.message) : 'UnknownError';
            setHint('danger', 'Tidak bisa mengakses kamera (<code>' + msg + '</code>). Pastikan izin kamera aktif dan buka lewat <strong>http://localhost:8000</strong>.');
            await stop();
            return;
        }

        await loadCameras();
        setHint('info', 'Memulai kamera...');

        qr = new Html5Qrcode('reader');

        const selectedCameraId = cameraSelect && !cameraSelect.disabled ? cameraSelect.value : '';

        try {
            await qr.start(
                (selectedCameraId ? { deviceId: { exact: selectedCameraId } } : { facingMode: 'environment' }),
                { fps: 10, qrbox: { width: 260, height: 260 } },
                async (decodedText) => {
                    const ticketId = parseTicketId(decodedText);
                    if (!ticketId) {
                        setHint('warning', 'QR terdeteksi, tapi format tidak dikenali.');
                        return;
                    }

                    setHint('success', 'Barcode terdeteksi. Mengambil data ticket...');
                    await stop();

                    try {
                        const res = await fetch(barcodeDataUrlTemplate.replace('___TICKET___', encodeURIComponent(ticketId)), {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (!res.ok) {
                            setHint('danger', (data && data.message) ? data.message : 'Gagal mengambil data ticket.');
                            return;
                        }
                        setResult(data);
                        Swal.fire({
                            icon: 'success',
                            title: 'Barcode Terdeteksi',
                            html:
                                '<div class="text-start">' +
                                '<div><strong>Judul:</strong> ' + (data.title || '-') + '</div>' +
                                '<div><strong>Kategori:</strong> ' + (data.category || '-') + '</div>' +
                                '<div><strong>Pelanggan:</strong> ' + (data.customer || '-') + '</div>' +
                                '<div><strong>Agent:</strong> ' + (data.agent || '-') + '</div>' +
                                '<div><strong>Resolved Pada:</strong> ' + (data.resolved_at_label || data.resolved_at || '-') + '</div>' +
                                '</div>',
                            confirmButtonText: 'Tutup'
                        });
                        setHint('success', 'Berhasil! Data ticket ditampilkan.');
                    } catch (e) {
                        setHint('danger', 'Gagal mengambil data ticket.');
                    }
                },
                () => {}
            );

            setHint('info', 'Arahkan kamera ke barcode...');
        } catch (e) {
            console.error(e);
            const msg = (e && (e.name || e.message)) ? (e.name || e.message) : 'UnknownError';
            setHint('danger', 'Gagal menjalankan scanner (<code>' + msg + '</code>). Coba ganti pilihan kamera, lalu klik Scan Barcode lagi.');
            await stop();
        }
    }

    if (btnStart) btnStart.addEventListener('click', start);
    if (btnStop) btnStop.addEventListener('click', stop);

    // Load cameras list after user grants permission on first scan.
})();
</script>
@endsection
@endsection
