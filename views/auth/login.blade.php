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
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-8 shadow-2xl">
            @if (session('error'))
                <div
                    class="mb-6 p-4 rounded-xl bg-rose-500/20 border border-rose-500/50 text-rose-200 text-sm flex items-start gap-3">
                    <i class="fa fa-exclamation-circle text-rose-400 text-lg mt-0.5 flex-shrink-0"></i>
                    <div class="leading-relaxed">{{ session('error') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-500/20 border border-rose-500/50 text-rose-200 text-sm flex flex-col gap-1.5">
                    @foreach ($errors->all() as $err)
                        <div class="flex items-start gap-2">
                            <i class="fa fa-exclamation-circle text-rose-400 mt-0.5 flex-shrink-0"></i>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ plugin_route('login.submit') }}" class="space-y-5" autocomplete="on">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5 ml-1">Username / Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa fa-user text-slate-400"></i>
                        </div>
                        <input type="text" name="username" value="{{ old('username') }}" required autofocus
                            maxlength="100" autocomplete="username" spellcheck="false" autocapitalize="none"
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
                        <input type="password" name="password" required
                            maxlength="128" autocomplete="current-password" spellcheck="false"
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

                {{-- Geolocation Status Badge --}}
                <div id="geo-status-box" class="p-3 rounded-2xl bg-slate-800/60 border border-slate-700/80 text-xs transition-all">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span id="geo-icon" class="text-indigo-400 flex-shrink-0"><i class="fa fa-spinner fa-spin"></i></span>
                            <span id="geo-text" class="text-slate-300 font-medium truncate">Mendeteksi titik koordinat perangkat...</span>
                        </div>
                        <button type="button" id="btn-retry-geo" style="display: none;" 
                                class="px-2.5 py-1 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 hover:text-white border border-rose-500/40 text-[11px] font-bold transition flex items-center gap-1 cursor-pointer flex-shrink-0">
                            <i class="fa fa-refresh"></i>
                            <span>Coba Lagi</span>
                        </button>
                    </div>
                    <div id="geo-hint" class="mt-1.5 text-[11px] text-slate-400 hidden border-t border-white/5 pt-1.5 leading-relaxed">
                        Izin lokasi wajib diberikan untuk login. Silakan izinkan akses lokasi pada browser/perangkat Anda.
                    </div>
                </div>

                {{-- Submit Button (Disabled sampai koordinat berhasil didapatkan) --}}
                <button type="submit" id="btn-login-submit" disabled
                    class="w-full flex justify-center items-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 bg-slate-800 text-slate-400 opacity-60 cursor-not-allowed border border-slate-700">
                    <i id="btn-icon" class="fa fa-lock text-xs text-rose-400"></i>
                    <span id="btn-text">Menunggu Izin Lokasi...</span>
                </button>
            </form>

            <div class="mt-8 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} {{ config('e-office.title', 'e-Arsip') }}. All rights reserved.
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.querySelector('form');
            const inputLat = document.getElementById('login_lat');
            const inputLng = document.getElementById('login_lng');
            const inputAcc = document.getElementById('login_accuracy');
            const inputLocName = document.getElementById('login_location_name');
            const geoBox = document.getElementById('geo-status-box');
            const geoIcon = document.getElementById('geo-icon');
            const geoText = document.getElementById('geo-text');
            const geoHint = document.getElementById('geo-hint');
            const btnRetry = document.getElementById('btn-retry-geo');
            const btnSubmit = document.getElementById('btn-login-submit');
            const btnIcon = document.getElementById('btn-icon');
            const btnText = document.getElementById('btn-text');

            let isLocationLocked = false;
            let isSubmitting = false;

            function setLoginAllowed(allowed) {
                if (!btnSubmit || isSubmitting) return;
                isLocationLocked = allowed;

                if (allowed) {
                    btnSubmit.disabled = false;
                    btnSubmit.className = 'w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-xl shadow-lg shadow-indigo-600/30 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-slate-900 transition-all transform hover:scale-[1.02] active:scale-[0.98] cursor-pointer';
                    if (btnIcon) btnIcon.className = 'fa fa-arrow-right text-xs';
                    if (btnText) btnText.textContent = 'Masuk ke Sistem';
                } else {
                    btnSubmit.disabled = true;
                    btnSubmit.className = 'w-full flex justify-center items-center gap-2 py-3 px-4 rounded-xl text-sm font-semibold transition-all duration-200 bg-slate-800 text-slate-400 opacity-60 cursor-not-allowed border border-slate-700';
                    if (btnIcon) btnIcon.className = 'fa fa-lock text-xs text-rose-400';
                    if (btnText) btnText.textContent = 'Lokasi Belum Diizinkan';
                }
            }

            function updateGeoStatus(type, message, showHint = false) {
                if (!geoBox) return;

                if (type === 'success') {
                    geoBox.className = 'p-3 rounded-2xl bg-emerald-500/15 border border-emerald-500/40 text-xs transition-all text-emerald-300';
                    geoIcon.innerHTML = '<i class="fa fa-map-marker text-emerald-400 text-sm"></i>';
                    geoText.textContent = message;
                    if (btnRetry) btnRetry.style.display = 'none';
                    if (geoHint) geoHint.classList.add('hidden');
                    setLoginAllowed(true);
                } else if (type === 'error' || type === 'warn') {
                    geoBox.className = 'p-3 rounded-2xl bg-rose-500/15 border border-rose-500/40 text-xs transition-all text-rose-300';
                    geoIcon.innerHTML = '<i class="fa fa-exclamation-triangle text-rose-400 text-sm"></i>';
                    geoText.textContent = message;
                    if (btnRetry) btnRetry.style.display = 'inline-flex';
                    if (geoHint) {
                        geoHint.classList.remove('hidden');
                        geoHint.textContent = 'Izin lokasi wajib diberikan untuk login. Silakan klik "Coba Lagi" atau izinkan akses lokasi pada browser Anda.';
                    }
                    setLoginAllowed(false);
                } else {
                    geoBox.className = 'p-3 rounded-2xl bg-slate-800/60 border border-slate-700/80 text-xs transition-all text-slate-300';
                    geoIcon.innerHTML = '<i class="fa fa-spinner fa-spin text-indigo-400 text-sm"></i>';
                    geoText.textContent = message;
                    if (btnRetry) btnRetry.style.display = 'none';
                    if (geoHint) geoHint.classList.add('hidden');
                    if (btnIcon) btnIcon.className = 'fa fa-spinner fa-spin text-xs';
                    if (btnText) btnText.textContent = 'Menunggu Izin Lokasi...';
                }
            }

            function requestLocation() {
                updateGeoStatus('loading', 'Meminta izin & mendeteksi titik lokasi...');

                if (!('geolocation' in navigator)) {
                    updateGeoStatus('error', 'Browser tidak mendukung pelacakan GPS/Geolokasi.');
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    async function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const acc = position.coords.accuracy;

                        if (typeof lat !== 'number' || isNaN(lat) || typeof lng !== 'number' || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                            updateGeoStatus('error', 'Koordinat GPS tidak valid.', true);
                            return;
                        }

                        inputLat.value = lat.toFixed(7);
                        inputLng.value = lng.toFixed(7);
                        inputAcc.value = typeof acc === 'number' && !isNaN(acc) ? acc.toFixed(1) : '';

                        // Tombol login seketika aktif begitu koordinat GPS terkunci
                        updateGeoStatus('success', 'Titik koordinat terkunci (' + lat.toFixed(4) + ', ' + lng.toFixed(4) + ')');

                        // Reverse geocoding via Nominatim di latar belakang
                        try {
                            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            if (res.ok) {
                                const data = await res.json();
                                if (data && data.display_name) {
                                    inputLocName.value = (data.display_name || '').substring(0, 255);
                                    const shortName = data.address?.suburb || data.address?.city || data.address?.town || data.address?.county || data.display_name.split(',')[0];
                                    updateGeoStatus('success', 'Lokasi: ' + shortName + ' (Terkunci)');
                                }
                            }
                        } catch (e) {
                            console.log('Reverse geocoding error:', e);
                        }
                    },
                    function(err) {
                        console.warn('Geolocation error:', err);
                        let errMsg = 'Izin lokasi belum diberikan / ditolak.';
                        if (err.code === 1) { // PERMISSION_DENIED
                            errMsg = 'Izin akses lokasi ditolak oleh browser.';
                        } else if (err.code === 2) { // POSITION_UNAVAILABLE
                            errMsg = 'Sinyal lokasi/GPS tidak tersedia saat ini.';
                        } else if (err.code === 3) { // TIMEOUT
                            errMsg = 'Waktu permintaan lokasi habis (timeout).';
                        }
                        updateGeoStatus('error', errMsg, true);
                    },
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
                );
            }

            if (btnRetry) {
                btnRetry.addEventListener('click', function(e) {
                    e.preventDefault();
                    requestLocation();
                });
            }

            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    if (isSubmitting) {
                        e.preventDefault();
                        return;
                    }

                    const lat = parseFloat(inputLat.value);
                    const lng = parseFloat(inputLng.value);

                    if (isNaN(lat) || isNaN(lng) || !inputLat.value || !inputLng.value || !isLocationLocked || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
                        e.preventDefault();
                        alert('Akses lokasi wajib diizinkan untuk masuk ke sistem. Silakan izinkan akses lokasi pada browser Anda.');
                        requestLocation();
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

            // Jalankan permintaan lokasi otomatis saat halaman dibuka
            requestLocation();
        });
    </script>
</body>

</html>