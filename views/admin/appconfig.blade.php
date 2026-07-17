@extends('cms::backend.layout.app', ['title' => 'Pengaturan'])

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0 font-weight-bold"><i class="fa fa-cogs text-primary mr-2"></i> Pengaturan Sistem E-Arsip</h5>
                    <small class="text-muted">Konfigurasi URL, tampilan, dan API WhatsApp.</small>
                </div>
                
                <form action="{{ route('e-office.pengaturan.index') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="card-body">
                        <h6 class="font-weight-bold text-uppercase border-bottom pb-2 mb-3">Informasi Aplikasi</h6>
                        
                        <div class="form-group">
                            <label class="font-weight-bold">Domain Khusus (Custom Domain)</label>
                            <input type="text" name="custom_domain" value="{{ old('custom_domain', $customDomain) }}" placeholder="contoh: arsip.desa.id" class="form-control">
                            <small class="text-muted">Jika diisi, semua akses E-Arsip akan diarahkan ke domain ini. <b>Pastikan domain ini sudah diarahkan (DNS A Record) ke server ini.</b></small>
                        </div>
                        
                        <div class="form-group">
                            <label class="font-weight-bold">URL Sistem E-Arsip</label>
                            <input type="url" name="url" value="{{ config('e-office.url') }}" required class="form-control">
                            <small class="text-muted">URL asli / default untuk sistem arsip ini jika tidak memakai custom domain.</small>
                        </div>
                        
                        <div class="form-group">
                            <label class="font-weight-bold">URL Logo Organisasi</label>
                            <input type="url" name="logo" value="{{ config('e-office.logo') }}" required class="form-control">
                        </div>
                        
                        <h6 class="font-weight-bold text-uppercase border-bottom pb-2 mb-3 mt-4">WhatsApp API (WA Sender)</h6>
                        
                        <div class="form-group">
                            <label class="font-weight-bold">API URL</label>
                            <input type="url" name="api_url" value="{{ config('e-office.api.wa_sender.url') }}" required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label class="font-weight-bold">API Session Name</label>
                            <input type="text" name="api_session" value="{{ config('e-office.api.wa_sender.session') }}" required class="form-control">
                        </div>

                        @if(config('e-office.api.wa_sender.url') && config('e-office.api.wa_sender.session'))
                        <div class="form-group mt-4">
                            <button type="button" class="btn btn-success" data-toggle="modal" data-bs-toggle="modal" data-target="#modalSession" data-bs-target="#modalSession">
                                <i class="fa fa-qrcode mr-1"></i> Scan WhatsApp Session
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="card-footer bg-light text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save mr-1"></i> Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
            
            @if(config('e-office.api.wa_sender.url') && config('e-office.api.wa_sender.session'))
            <div class="modal fade" id="modalSession" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title font-weight-bold">Scan QR WhatsApp</h5>
                            <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true" class="d-none d-sm-inline">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <p id="statusBadge" class="font-weight-bold text-warning mb-3">🟡 Waiting QR</p>
                            <div id="qrContainer" class="d-flex justify-content-center align-items-center" style="min-height: 250px; background: #f8f9fa; border-radius: 10px;">
                                <img src="" class="qrImage img-fluid" alt="QR Code" style="display: none; max-width: 250px;">
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Tutup</button>
                            <button type="button" class="btn btn-success" id="btnKirimSession">
                                <i class="fa fa-sync-alt mr-1"></i> Refresh QR
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if(config('e-office.api.wa_sender.url') && config('e-office.api.wa_sender.session'))
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
    let intervalQR = null;
    
    document.getElementById('btnKirimSession').addEventListener('click', function () {
        let session = document.querySelector('input[name="api_session"]').value;
        let url = document.querySelector('input[name="api_url"]').value;
    
        if (intervalQR) clearInterval(intervalQR);
        getQR(url, session);
    });
    
    function setStatus(text, colorClass = 'text-warning') {
        let el = document.getElementById('statusBadge');
        el.className = 'font-weight-bold mb-3 ' + colorClass;
        el.innerText = text;
    }
    
    function getQR(url, session) {
        let qrContainer = document.getElementById('qrContainer');
        
        fetch(url + '/session/start', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session: session })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status && data.status === 'connected') {
                setStatus('🟢 Connected', 'text-success');
                qrContainer.innerHTML = '<div class="text-success mt-4"><i class="fa fa-check-circle fa-4x mb-3"></i><br><h5>Device sudah terhubung</h5></div>';
                clearInterval(intervalQR);
                return;
            }
    
            if (data.qr) {
                setStatus('🟡 Waiting QR', 'text-warning');
                QRCode.toDataURL(data.qr, function (err, urlImg) {
                    if (!err) {
                        $('.qrImage').attr('src', urlImg).show();
                    }
                });
            } else {
                setStatus('QR tidak ditemukan, reset session...', 'text-danger');
                logoutSession(url, session, () => {
                    setTimeout(() => getQR(url, session), 1000);
                });
            }
        })
        .catch(err => {
            setStatus('Error koneksi...', 'text-danger');
            console.error(err);
        });
    }
    
    function logoutSession(baseUrl, session, callback = null) {
        let logoutUrl = baseUrl.replace(/\/$/, '') + '/session/logout';
        fetch(logoutUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ session: session })
        })
        .then(res => res.json())
        .then(res => {
            if (callback) callback();
        })
        .catch(err => console.error('Logout gagal:', err));
    }

    $(document).ready(function() {
        $('#modalSession').on('shown.bs.modal', function () {
            $('#btnKirimSession').click();
        });
        
        $('#modalSession').on('hidden.bs.modal', function () {
            if (intervalQR) clearInterval(intervalQR);
        });
    });
</script>
@endif
@endpush
