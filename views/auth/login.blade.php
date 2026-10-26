<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title>Login &mdash; {{ config('e-office.title', 'e-Arsip') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                }
            }
        }
    </script>
</head>

<body
    class="bg-slate-900 min-h-screen flex items-center justify-center p-4 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] relative">

    <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/80 via-slate-900/90 to-purple-900/80"></div>

    <div class="relative w-full max-w-md">
        <!-- Logo Header -->
        <div class="text-center mb-8">
            <div
                class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-xl shadow-indigo-500/30 mb-4">
                <i class="fa fa-archive text-white text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">{{ config('e-office.title', 'e-Arsip') }}</h1>
            <p class="text-slate-400 mt-2">{{ config('e-office.description', 'Sistem Arsip Elektronik') }}</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-6 sm:p-8 shadow-2xl">
            @if (session('error'))
                <div
                    class="mb-5 p-4 rounded-xl bg-rose-500/20 border border-rose-500/50 text-rose-200 text-sm flex items-start gap-3">
                    <i class="fa fa-exclamation-circle text-rose-400 text-lg mt-0.5 flex-shrink-0"></i>
                    <div class="leading-relaxed">{{ session('error') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div
                    class="mb-5 p-4 rounded-xl bg-rose-500/20 border border-rose-500/50 text-rose-200 text-sm flex flex-col gap-1.5">
                    @foreach ($errors->all() as $err)
                        <div class="flex items-start gap-2">
                            <i class="fa fa-exclamation-circle text-rose-400 mt-0.5 flex-shrink-0"></i>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Warning Banner jika Akses Melalui HTTP Biasa di Chrome --}}
            <div id="http-warning-banner"
                class="hidden mb-5 p-3.5 rounded-xl bg-amber-500/20 border border-amber-500/50 text-amber-200 text-xs">
                <div class="flex items-start gap-2.5">
                    <i class="fa fa-exclamation-triangle text-amber-400 text-base mt-0.5 flex-shrink-0"></i>
                    <div>
                        <strong class="font-semibold block mb-0.5">Koneksi HTTP Terdeteksi</strong>
                        Browser Chrome & Edge memblokir permintaan izin lokasi pada koneksi HTTP biasa. Pastikan web
                        diakses melalui <strong>HTTPS</strong> atau <strong>localhost</strong> agar Chrome dapat
                        memunculkan popup izin lokasi.
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ plugin_route('login.submit') }}" class="space-y-4" autocomplete="on">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5 ml-1">Username / Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa fa-user text-slate-400"></i>
                        </div>
                        <input type="text" name="username" id="input_username" value="{{ old('username') }}" required
                            autofocus maxlength="100" autocomplete="username" spellcheck="false" autocapitalize="none"
                            class="block w-full pl-11 pr-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all sm:text-sm"
                            placeholder="Masukkan username atau email">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5 ml-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa fa-lock text-slate-400"></i>
                        </div>
                        <input type="password" name="password" id="input_password" required maxlength="128"
                            autocomplete="current-password" spellcheck="false"
                            class="block w-full pl-11 pr-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all sm:text-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <div>
                    {!! captcha_field() !!}
                </div>

                {{-- Hidden Geolocation Inputs --}}
                <input type="hidden" name="latitude" id="login_lat">
                <input type="hidden" name="longitude" id="login_lng">
                <input type="hidden" name="accuracy" id="login_accuracy">
                <input type="hidden" name="location_name" id="login_location_name">

                {{-- Geolocation Status Badge & User-Gesture Action Box --}}
                <div id="geo-status-box"
                    class="p-3.5 rounded-2xl bg-slate-800/70 border border-slate-700/80 text-xs transition-all">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span id="geo-icon" class="text-indigo-400 flex-shrink-0 text-base">
                                <i class="fa fa-map-marker"></i>
                            </span>
                            <div class="min-w-0">
                                <span id="geo-text" class="text-slate-300 font-medium block truncate">Memeriksa izin
                                    lokasi perangkat...</span>
                                <span id="geo-subtext" class="text-[11px] text-slate-400 block truncate">Wajib diizinkan
                                    untuk verifikasi kehadiran.</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <button type="button" id="btn-guide-geo" style="display: none;"
                                class="px-2 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-[11px] font-medium transition cursor-pointer flex items-center gap-1">
                                <i class="fa fa-question-circle"></i>
                                <span>Panduan</span>
                            </button>
                            <button type="button" id="btn-retry-geo" style="display: none;"
                                class="px-2.5 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 hover:text-white border border-rose-500/40 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer">
                                <i class="fa fa-refresh"></i>
                                <span>Coba Lagi</span>
                            </button>
                        </div>
                    </div>

                    {{-- Tombol Explicit User Gesture: Memicu prompt Chrome secara langsung saat di-klik/tap --}}
                    <div id="geo-prompt-container" class="mt-2.5 pt-2 border-t border-white/10">
                        <button type="button" id="btn-request-geo"
                            class="w-full py-2.5 px-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold text-xs flex items-center justify-center gap-2 shadow-lg shadow-indigo-600/30 transition-all transform hover:scale-[1.01] active:scale-[0.99] cursor-pointer">
                            <i class="fa fa-location-arrow text-sm animate-pulse"></i>
                            <span>Klik di Sini untuk Mengizinkan Lokasi</span>
                        </button>
                    </div>

                    <div id="geo-hint"
                        class="mt-2 text-[11px] text-slate-400 hidden border-t border-white/5 pt-1.5 leading-relaxed">
                        Izin lokasi wajib diberikan untuk login. Silakan izinkan akses lokasi pada browser/perangkat
                        Anda.
                    </div>
                </div>

                {{-- Submit Button (Disabled sampai koordinat berhasil didapatkan) --}}
                <button type="submit" id="btn-login-submit" disabled
                    class="w-full flex justify-center items-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 bg-slate-800 text-slate-400 opacity-60 cursor-not-allowed border border-slate-700">
                    <i id="btn-icon" class="fa fa-lock text-xs text-rose-400"></i>
                    <span id="btn-text">Menunggu Izin Lokasi...</span>
                </button>
            </form>

            <div class="mt-6 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} {{ e_office_app_name() }}. All rights reserved.
            </div>
        </div>
    </div>

    {{-- ══════════════ MODAL PANDUAN BUKA BLOKIR CHROME ══════════════ --}}
    <div id="modal-chrome-guide"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm hidden">
        <div
            class="bg-slate-900 border border-slate-700 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative text-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                <div class="flex items-center gap-2 text-indigo-400 font-bold text-base">
                    <i class="fa fa-chrome text-lg"></i>
                    <span>Panduan Mengaktifkan Lokasi di Chrome</span>
                </div>
                <button type="button" id="btn-close-guide" class="text-slate-400 hover:text-white text-lg p-1">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="space-y-4 text-xs leading-relaxed max-h-[70vh] overflow-y-auto pr-1">
                {{-- Desktop Chrome --}}
                <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700">
                    <div class="font-bold text-white text-sm flex items-center gap-2 mb-2">
                        <i class="fa fa-desktop text-indigo-400"></i>
                        <span>1. Untuk Komputer / Laptop (Chrome Desktop)</span>
                    </div>
                    <ol class="list-decimal list-inside space-y-1.5 text-slate-300 ml-1">
                        <li>Lihat bilah alamat (address bar) di bagian atas Chrome.</li>
                        <li>Klik ikon <strong>Pengaturan Situs</strong> ( <i class="fa fa-sliders text-indigo-300"></i>
                            atau ikon gembok 🔒 ) di sebelah kiri alamat web.</li>
                        <li>Cari baris <strong>Lokasi (Location)</strong>, lalu ubah dari <span
                                class="text-rose-400 font-semibold">Blokir</span> menjadi <span
                                class="text-emerald-400 font-semibold">Izinkan (Allow)</span>.</li>
                        <li>Tutup menu tersebut, lalu tekan tombol <strong>Refresh (F5)</strong> atau tombol "Coba
                            Lagi".</li>
                    </ol>
                </div>

                {{-- Mobile Chrome --}}
                <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700">
                    <div class="font-bold text-white text-sm flex items-center gap-2 mb-2">
                        <i class="fa fa-mobile-phone text-indigo-400 text-base"></i>
                        <span>2. Untuk HP Android / iPhone (Chrome Mobile)</span>
                    </div>
                    <ol class="list-decimal list-inside space-y-1.5 text-slate-300 ml-1">
                        <li>Tap ikon titik tiga ( <strong class="text-white">⋮</strong> ) di pojok kanan atas Chrome,
                            atau tap ikon 🔒 / 🎛️ di kiri address bar.</li>
                        <li>Pilih <strong>Setelan Situs (Site Settings)</strong> &rarr; <strong>Lokasi
                                (Location)</strong>.</li>
                        <li>Ubah izin untuk situs ini menjadi <strong>Izinkan (Allow)</strong>.</li>
                        <li><strong>PENTING:</strong> Pastikan juga fitur <strong>GPS / Layanan Lokasi</strong> pada
                            pengaturan perangkat HP Anda dalam kondisi <strong>AKTIF</strong>.</li>
                        <li>Kembali ke halaman ini dan tekan tombol "Coba Lagi" atau muat ulang halaman.</li>
                    </ol>
                </div>

                {{-- Secure Context Note --}}
                <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-[11px]">
                    <i class="fa fa-info-circle mr-1"></i>
                    <strong>Catatan:</strong> Google Chrome mewajibkan protokol <strong>HTTPS</strong> untuk fitur GPS
                    geolokasi. Jika web diakses via HTTP IP lokal, Chrome otomatis menolak izin lokasi.
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-800 flex justify-end gap-2">
                <button type="button" id="btn-close-guide-footer"
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition cursor-pointer">
                    Saya Mengerti
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const loginForm = document.querySelector('form');
            const inputUsername = document.getElementById('input_username');
            const inputPassword = document.getElementById('input_password');
            const inputLat = document.getElementById('login_lat');
            const inputLng = document.getElementById('login_lng');
            const inputAcc = document.getElementById('login_accuracy');
            const inputLocName = document.getElementById('login_location_name');
            const geoBox = document.getElementById('geo-status-box');
            const geoIcon = document.getElementById('geo-icon');
            const geoText = document.getElementById('geo-text');
            const geoSubtext = document.getElementById('geo-subtext');
            const geoHint = document.getElementById('geo-hint');
            const btnRetry = document.getElementById('btn-retry-geo');
            const btnGuide = document.getElementById('btn-guide-geo');
            const btnRequestGeo = document.getElementById('btn-request-geo');
            const geoPromptContainer = document.getElementById('geo-prompt-container');
            const btnSubmit = document.getElementById('btn-login-submit');
            const btnIcon = document.getElementById('btn-icon');
            const btnText = document.getElementById('btn-text');
            const httpWarning = document.getElementById('http-warning-banner');

            const modalGuide = document.getElementById('modal-chrome-guide');
            const btnCloseGuide = document.getElementById('btn-close-guide');
            const btnCloseGuideFooter = document.getElementById('btn-close-guide-footer');

            let isLocationLocked = false;
            let isSubmitting = false;
            let isRequesting = false;

            // 1. Cek Secure Context (Chrome memblokir Geolocation pada HTTP biasa selain localhost)
            const isLocal = ['localhost', '127.0.0.1', '::1'].includes(window.location.hostname);
            const isSecure = window.isSecureContext || isLocal || window.location.protocol === 'https:';

            if (!isSecure && httpWarning) {
                httpWarning.classList.remove('hidden');
            }

            function setLoginAllowed(allowed) {
                if (!btnSubmit || isSubmitting) return;
                isLocationLocked = allowed;

                if (allowed) {
                    btnSubmit.disabled = false;
                    btnSubmit.className = 'w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-xl shadow-lg shadow-indigo-600/30 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-slate-900 transition-all transform hover:scale-[1.02] active:scale-[0.98] cursor-pointer';
                    if (btnIcon) btnIcon.className = 'fa fa-arrow-right text-xs';
                    if (btnText) btnText.textContent = 'Masuk ke Sistem';
                    if (geoPromptContainer) geoPromptContainer.style.display = 'none';
                } else {
                    btnSubmit.disabled = true;
                    btnSubmit.className = 'w-full flex justify-center items-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 bg-slate-800 text-slate-400 opacity-60 cursor-not-allowed border border-slate-700';
                    if (btnIcon) btnIcon.className = 'fa fa-lock text-xs text-rose-400';
                    if (btnText) btnText.textContent = 'Lokasi Belum Diizinkan';
                }
            }

            function updateGeoStatus(type, message, subtext = '', showRetry = false, showGuide = false) {
                if (!geoBox) return;

                if (type === 'success') {
                    geoBox.className = 'p-3.5 rounded-2xl bg-emerald-500/15 border border-emerald-500/40 text-xs transition-all text-emerald-300';
                    geoIcon.innerHTML = '<i class="fa fa-check-circle text-emerald-400 text-base"></i>';
                    geoText.textContent = message;
                    if (geoSubtext) geoSubtext.textContent = subtext || 'Lokasi terverifikasi';
                    if (btnRetry) btnRetry.style.display = 'none';
                    if (btnGuide) btnGuide.style.display = 'none';
                    if (geoHint) geoHint.classList.add('hidden');
                    if (geoPromptContainer) geoPromptContainer.style.display = 'none';
                    setLoginAllowed(true);
                } else if (type === 'error' || type === 'warn') {
                    geoBox.className = 'p-3.5 rounded-2xl bg-rose-500/15 border border-rose-500/40 text-xs transition-all text-rose-300';
                    geoIcon.innerHTML = '<i class="fa fa-exclamation-triangle text-rose-400 text-base"></i>';
                    geoText.textContent = message;
                    if (geoSubtext) geoSubtext.textContent = subtext;
                    if (btnRetry) btnRetry.style.display = showRetry ? 'inline-flex' : 'none';
                    if (btnGuide) btnGuide.style.display = showGuide ? 'inline-flex' : 'none';
                    if (geoPromptContainer) geoPromptContainer.style.display = 'block';
                    if (geoHint) {
                        geoHint.classList.remove('hidden');
                        geoHint.textContent = 'Izin lokasi wajib diberikan untuk login. Silakan klik tombol di atas atau izinkan akses lokasi pada browser Anda.';
                    }
                    setLoginAllowed(false);
                } else {
                    geoBox.className = 'p-3.5 rounded-2xl bg-slate-800/70 border border-slate-700/80 text-xs transition-all text-slate-300';
                    geoIcon.innerHTML = '<i class="fa fa-spinner fa-spin text-indigo-400 text-base"></i>';
                    geoText.textContent = message;
                    if (geoSubtext) geoSubtext.textContent = subtext || 'Menghubungkan ke sensor lokasi...';
                    if (btnRetry) btnRetry.style.display = 'none';
                    if (btnGuide) btnGuide.style.display = 'none';
                    if (geoHint) geoHint.classList.add('hidden');
                    if (btnIcon) btnIcon.className = 'fa fa-spinner fa-spin text-xs';
                    if (btnText) btnText.textContent = 'Menunggu Izin Lokasi...';
                }
            }

            function handlePositionSuccess(position) {
                isRequesting = false;
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const acc = position.coords.accuracy;

                if (typeof lat !== 'number' || isNaN(lat) || typeof lng !== 'number' || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                    updateGeoStatus('error', 'Koordinat GPS tidak valid.', 'Nilai koordinat di luar jangkauan.', true, false);
                    return;
                }

                inputLat.value = lat.toFixed(7);
                inputLng.value = lng.toFixed(7);
                inputAcc.value = typeof acc === 'number' && !isNaN(acc) ? acc.toFixed(1) : '';

                updateGeoStatus('success', 'Titik koordinat terkunci (' + lat.toFixed(4) + ', ' + lng.toFixed(4) + ')', 'Akurasi: ±' + Math.round(acc) + ' meter');

                // Reverse geocoding via Nominatim di latar belakang
                fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(res => res.ok ? res.json() : null)
                    .then(data => {
                        if (data && data.display_name) {
                            inputLocName.value = (data.display_name || '').substring(0, 255);
                            const shortName = data.address?.suburb || data.address?.city || data.address?.town || data.address?.county || data.display_name.split(',')[0];
                            updateGeoStatus('success', 'Lokasi: ' + shortName, lat.toFixed(4) + ', ' + lng.toFixed(4) + ' (Terkunci)');
                        }
                    })
                    .catch(e => {
                        console.log('Reverse geocoding notice:', e);
                    });
            }

            function handlePositionError(err) {
                isRequesting = false;
                console.warn('Geolocation error:', err);

                let errMsg = 'Izin lokasi belum diberikan.';
                let subMsg = 'Klik tombol izin lokasi di bawah.';
                let showGuide = false;

                if (err.code === 1) { // PERMISSION_DENIED
                    errMsg = 'Izin lokasi ditolak / diblokir di Chrome.';
                    subMsg = 'Buka pengaturan Chrome untuk mengizinkan lokasi.';
                    showGuide = true;
                } else if (err.code === 2) { // POSITION_UNAVAILABLE
                    errMsg = 'Sinyal lokasi / GPS tidak terdeteksi.';
                    subMsg = 'Pastikan GPS perangkat Anda aktif.';
                    showGuide = true;
                } else if (err.code === 3) { // TIMEOUT
                    errMsg = 'Waktu permintaan lokasi habis (timeout).';
                    subMsg = 'Silakan coba lagi atau cek koneksi internet.';
                }

                if (!isSecure) {
                    errMsg = 'Akses lokasi diblokir oleh Chrome (Non-HTTPS).';
                    subMsg = 'Buka melalui HTTPS atau localhost.';
                    showGuide = true;
                }

                updateGeoStatus('error', errMsg, subMsg, true, showGuide);
            }

            // Fungsi cerdas: Coba Standard Accuracy terlebih dahulu (cepat di laptop & desktop), fallback High Accuracy jika mobile
            function executeGeolocation() {
                if (isLocationLocked) return;
                isRequesting = true;
                updateGeoStatus('loading', 'Meminta izin & mendeteksi koordinat...', 'Browser akan memunculkan konfirmasi izin lokasi.');

                if (!('geolocation' in navigator)) {
                    isRequesting = false;
                    updateGeoStatus('error', 'Browser tidak mendukung geolokasi GPS.', '', false, false);
                    return;
                }

                // Coba standard accuracy dahulu agar tidak hang/timeout di Chrome laptop/desktop
                const standardOptions = { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 };
                const highAccuracyOptions = { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 };

                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        handlePositionSuccess(pos);
                    },
                    function (err) {
                        // Jika gagal karena timeout atau signal unavailable, coba sekali lagi dengan high accuracy
                        if (err.code === 2 || err.code === 3) {
                            navigator.geolocation.getCurrentPosition(
                                handlePositionSuccess,
                                handlePositionError,
                                highAccuracyOptions
                            );
                        } else {
                            handlePositionError(err);
                        }
                    },
                    standardOptions
                );
            }

            // Fungsi utama pemanggil izin lokasi
            function requestLocation(isUserGesture = false) {
                if (isLocationLocked) return;

                // Periksa status izin jika browser mendukung Permissions API
                if (navigator.permissions && navigator.permissions.query) {
                    navigator.permissions.query({ name: 'geolocation' }).then(function (result) {
                        if (result.state === 'granted') {
                            executeGeolocation();
                        } else if (result.state === 'denied') {
                            updateGeoStatus('error', 'Akses lokasi diblokir di Chrome.', 'Klik "Panduan" untuk membuka blokir di address bar.', true, true);
                        } else {
                            // Status 'prompt'
                            if (isUserGesture) {
                                executeGeolocation();
                            } else {
                                updateGeoStatus('loading', 'Perlu konfirmasi izin lokasi...', 'Silakan klik tombol di bawah untuk memunculkan izin.');
                            }
                        }

                        // Deteksi perubahan izin secara realtime jika user mengubah di Chrome settings
                        result.onchange = function () {
                            if (this.state === 'granted') {
                                executeGeolocation();
                            } else if (this.state === 'denied') {
                                updateGeoStatus('error', 'Akses lokasi diblokir di Chrome.', 'Klik "Panduan" untuk membuka blokir di address bar.', true, true);
                            }
                        };
                    }).catch(function () {
                        executeGeolocation();
                    });
                } else {
                    executeGeolocation();
                }
            }

            // User Gesture: Klik tombol "Klik di Sini untuk Mengizinkan Lokasi"
            if (btnRequestGeo) {
                btnRequestGeo.addEventListener('click', function (e) {
                    e.preventDefault();
                    executeGeolocation();
                });
            }

            // User Gesture: Klik tombol Coba Lagi
            if (btnRetry) {
                btnRetry.addEventListener('click', function (e) {
                    e.preventDefault();
                    executeGeolocation();
                });
            }

            // User Gesture Tambahan: Saat user fokus / klik input username atau password, otomatis trigger permintaan izin jika belum terkunci
            if (inputUsername) {
                inputUsername.addEventListener('focus', function () {
                    if (!isLocationLocked && !isRequesting) {
                        executeGeolocation();
                    }
                }, { once: true });
            }

            if (inputPassword) {
                inputPassword.addEventListener('focus', function () {
                    if (!isLocationLocked && !isRequesting) {
                        executeGeolocation();
                    }
                }, { once: true });
            }

            // Modal Panduan Buka Blokir
            function openGuide() {
                if (modalGuide) modalGuide.classList.remove('hidden');
            }
            function closeGuide() {
                if (modalGuide) modalGuide.classList.add('hidden');
            }

            if (btnGuide) btnGuide.addEventListener('click', openGuide);
            if (btnCloseGuide) btnCloseGuide.addEventListener('click', closeGuide);
            if (btnCloseGuideFooter) btnCloseGuideFooter.addEventListener('click', closeGuide);
            if (modalGuide) {
                modalGuide.addEventListener('click', function (e) {
                    if (e.target === modalGuide) closeGuide();
                });
            }

            // Validasi sebelum submit form
            if (loginForm) {
                loginForm.addEventListener('submit', function (e) {
                    if (isSubmitting) {
                        e.preventDefault();
                        return;
                    }

                    const lat = parseFloat(inputLat.value);
                    const lng = parseFloat(inputLng.value);

                    if (isNaN(lat) || isNaN(lng) || !inputLat.value || !inputLng.value || !isLocationLocked || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                        e.preventDefault();
                        alert('Akses lokasi wajib diizinkan untuk masuk ke sistem. Silakan klik tombol "Klik di Sini untuk Mengizinkan Lokasi".');
                        executeGeolocation();
                        return;
                    }

                    // Cegah double submission
                    isSubmitting = true;
                    btnSubmit.disabled = true;
                    btnSubmit.classList.add('opacity-75', 'cursor-not-allowed');
                    if (btnIcon) btnIcon.className = 'fa fa-spinner fa-spin text-xs';
                    if (btnText) btnText.textContent = 'Memverifikasi Keamanan...';
                });
            }

            // Jalankan pengecekan izin awal saat halaman dimuat
            requestLocation(false);
        });
    </script>
</body>

</html>