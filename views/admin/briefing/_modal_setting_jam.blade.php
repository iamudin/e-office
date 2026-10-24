@if(!empty($isKadis))
{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{--        MODAL PENGATURAN JAM PERINGATAN WA OLEH KEPALA DINAS            --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div id="modalSettingJamPeringatan" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto" style="display: none;">
    <div class="relative w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-8 flex flex-col transform transition-all animate-in fade-in zoom-in-95 duration-200">
        {{-- Header Modal --}}
        <div class="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-400/30 flex items-center justify-center text-amber-300 text-lg shadow-inner shrink-0">
                    <i class="fa fa-clock-o"></i>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold leading-tight text-white flex items-center gap-2">
                        Atur Jam &amp; Status Peringatan
                    </h3>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Konfigurasi jam batas peringatan WA &amp; opsi libur oleh Pimpinan.
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeModalSettingJam()" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer" title="Tutup">
                <i class="fa fa-times text-sm"></i>
            </button>
        </div>

        {{-- Form --}}
        <form action="{{ plugin_route('e-office.briefing.setting-jam') }}" method="POST" id="formSettingJamPeringatan">
            @csrf
            <input type="hidden" name="is_full_setting" value="1">

            <div class="p-6 space-y-4">

                {{-- SECTION STATUS PERINGATAN (SAKELAR UTAMA & PILIHAN MODE) --}}
                <div class="bg-slate-50/90 border-2 border-slate-200 rounded-2xl p-4 space-y-3.5 shadow-xs">
                    {{-- HEADER SAKELAR DENGAN SAKELAR FISIK JELAS --}}
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200/80 pb-3">
                        <div>
                            <span class="block text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fa fa-toggle-on text-indigo-600"></i> SAKELAR STATUS PERINGATAN
                            </span>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                Geser sakelar untuk aktifkan / liburkan peringatan
                            </p>
                        </div>

                        {{-- KOMPONEN SAKELAR FISIK / TOGGLE SWITCH --}}
                        <label for="modalToggleWarningEnabled" style="display: inline-flex; align-items: center; cursor: pointer; user-select: none; gap: 8px;">
                            <span id="modalToggleLabel" style="font-size: 11px; font-weight: 800; font-family: monospace; padding: 2px 8px; border-radius: 9999px; {{ ($warningEnabled ?? true) ? 'background-color: #d1fae5; color: #065f46;' : 'background-color: #ffe4e6; color: #9f1239;' }}">
                                {{ ($warningEnabled ?? true) ? 'ON (AKTIF)' : 'OFF (LIBUR)' }}
                            </span>
                            <div style="position: relative; width: 56px; height: 30px;">
                                <input type="checkbox" id="modalToggleWarningEnabled" name="briefing_warning_enabled" value="1" {{ ($warningEnabled ?? true) ? 'checked' : '' }} onchange="syncWarningState(this.checked)" style="opacity: 0; width: 0; height: 0; position: absolute;">
                                <div id="modalToggleTrack" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: {{ ($warningEnabled ?? true) ? '#059669' : '#cbd5e1' }}; border-radius: 9999px; transition: background-color 0.25s ease; box-shadow: inset 0 2px 4px rgba(0,0,0,0.15);"></div>
                                <div id="modalToggleThumb" style="position: absolute; top: 3px; left: 3px; width: 24px; height: 24px; background-color: #ffffff; border-radius: 9999px; transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 2px 5px rgba(0,0,0,0.25); {{ ($warningEnabled ?? true) ? 'transform: translateX(26px);' : 'transform: translateX(0px);' }}"></div>
                            </div>
                        </label>
                    </div>

                    {{-- DUA KARTU PILIHAN BESAR (BISA LANGSUNG DIKLIK SEBAGAI OPSI ALTERNATIF) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- Kartu 1: Peringatan Aktif -->
                        <div id="cardModeAktif" onclick="syncWarningState(true)" 
                            class="p-3 rounded-xl border-2 transition-all cursor-pointer flex items-start gap-2.5 select-none {{ ($warningEnabled ?? true) ? 'bg-emerald-50 border-emerald-500 shadow-xs' : 'bg-white border-slate-200 hover:border-slate-300 opacity-60' }}">
                            <div id="radioIndicatorAktif" class="w-5 h-5 rounded-full border-2 mt-0.5 flex items-center justify-center shrink-0 {{ ($warningEnabled ?? true) ? 'border-emerald-600 bg-emerald-600' : 'border-slate-300 bg-white' }}">
                                <div id="radioDotAktif" class="w-2 h-2 rounded-full bg-white {{ ($warningEnabled ?? true) ? '' : 'hidden' }}"></div>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <i class="fa fa-bell text-emerald-600"></i> Peringatan Aktif
                                </p>
                                <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                    Hari kerja normal. Notifikasi WA &amp; dashboard beroperasi.
                                </p>
                            </div>
                        </div>

                        <!-- Kartu 2: Nonaktif (Hari Libur) -->
                        <div id="cardModeLibur" onclick="syncWarningState(false)" 
                            class="p-3 rounded-xl border-2 transition-all cursor-pointer flex items-start gap-2.5 select-none {{ !($warningEnabled ?? true) ? 'bg-rose-50 border-rose-500 shadow-xs' : 'bg-white border-slate-200 hover:border-slate-300 opacity-60' }}">
                            <div id="radioIndicatorLibur" class="w-5 h-5 rounded-full border-2 mt-0.5 flex items-center justify-center shrink-0 {{ !($warningEnabled ?? true) ? 'border-rose-600 bg-rose-600' : 'border-slate-300 bg-white' }}">
                                <div id="radioDotLibur" class="w-2 h-2 rounded-full bg-white {{ !($warningEnabled ?? true) ? '' : 'hidden' }}"></div>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                                    <i class="fa fa-bell-slash text-rose-600"></i> Nonaktifkan (Libur)
                                </p>
                                <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                                    Hari libur / cuti. Pesan WA &amp; dashboard dijeda.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Checkbox Auto-Weekend (Sabtu & Minggu) --}}
                    <div class="pt-2 border-t border-slate-200/80 flex items-center justify-between text-xs">
                        <label for="modalCheckAutoWeekend" class="flex items-center gap-2 cursor-pointer text-slate-700 hover:text-slate-900 select-none">
                            <input type="checkbox" name="briefing_warning_auto_weekend" id="modalCheckAutoWeekend" value="1" {{ ($autoWeekend ?? true) ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 border-slate-300">
                            <span>Otomatis libur di akhir pekan (<strong>Sabtu &amp; Minggu</strong>)</span>
                        </label>
                        <span class="text-[10px] text-slate-400 font-mono">Auto-Weekend</span>
                    </div>
                </div>

                {{-- BANNER SAAT NONAKTIF --}}
                <div id="bannerStatusLibur" class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-xs text-rose-800 flex items-center gap-2.5 {{ ($warningEnabled ?? true) ? 'hidden' : '' }}">
                    <i class="fa fa-info-circle text-rose-600 text-sm shrink-0"></i>
                    <span><strong>Mode Libur Aktif:</strong> Peringatan WA otomatis dan banner peringatan di dashboard tidak akan dikirimkan sampai sakelar diaktifkan kembali.</span>
                </div>

                {{-- CONTAINER INPUT JAM (DAPAT DI-DIM SAAT NONAKTIF) --}}
                <div id="containerJamInputs" class="space-y-4 transition-all duration-200 {{ ($warningEnabled ?? true) ? '' : 'opacity-40 pointer-events-none' }}">
                    {{-- Field 1: Jam Batas Memberi Tugas --}}
                    <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4">
                        <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span><i class="fa fa-bullhorn mr-1.5 text-amber-600"></i> Batas Waktu Memberikan Tugas</span>
                            <span class="text-[10px] bg-amber-200/80 text-amber-800 px-2 py-0.5 rounded-full font-mono font-bold">Sekretaris &amp; Kabid</span>
                        </label>
                        <input type="time" name="briefing_warning_time_forward" id="inputJamForward" 
                            value="{{ $jamForward ?? '09:00' }}" required
                            class="w-full bg-white border border-amber-300 rounded-xl px-3 py-2.5 text-slate-800 text-sm font-bold font-mono focus:outline-none focus:ring-2 focus:ring-amber-500 shadow-xs">
                        <p class="text-[11px] text-amber-800 mt-2 leading-relaxed">
                            Jika pejabat struktural 1 tingkat di bawah pimpinan <strong>belum membuat atau meneruskan arahan tugas</strong> kepada staf bawahan hingga jam ini, sistem akan otomatis mengirimkan notifikasi peringatan WhatsApp ke pejabat tersebut.
                        </p>
                    </div>

                    {{-- Field 2: Jam Batas Merespon / Tindak Lanjut Tugas --}}
                    <div class="bg-blue-50/70 border border-blue-200/80 rounded-2xl p-4">
                        <label class="block text-xs font-bold text-blue-900 uppercase tracking-wider mb-1.5 flex items-center justify-between">
                            <span><i class="fa fa-tasks mr-1.5 text-blue-600"></i> Batas Waktu Merespon / Tindak Lanjut</span>
                            <span class="text-[10px] bg-blue-200/80 text-blue-800 px-2 py-0.5 rounded-full font-mono font-bold">Staf Bawahan</span>
                        </label>
                        <input type="time" name="briefing_warning_time_action" id="inputJamAction" 
                            value="{{ $jamAction ?? '14:00' }}" required
                            class="w-full bg-white border border-blue-300 rounded-xl px-3 py-2.5 text-slate-800 text-sm font-bold font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs">
                        <p class="text-[11px] text-blue-800 mt-2 leading-relaxed">
                            Jika staf bawahan yang telah menerima instruksi <strong>belum membuka atau belum melakukan aksi/tanggapan</strong> hingga jam ini, sistem akan otomatis mengirimkan notifikasi peringatan WhatsApp ke bawahan tersebut.
                        </p>
                    </div>
                </div>

                {{-- Catatan Info --}}
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-600 flex items-start gap-2.5">
                    <i class="fa fa-info-circle text-indigo-500 mt-0.5 shrink-0"></i>
                    <span>Pengaturan jam &amp; status ini langsung berlaku secara realtime untuk seluruh notifikasi WhatsApp otomatis dan banner peringatan pada dashboard pejabat.</span>
                </div>
            </div>

            {{-- Footer Modal --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-slate-50 border-t border-slate-100 flex-shrink-0">
                <button type="button" onclick="closeModalSettingJam()" class="px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 text-xs font-semibold transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" id="btnSimpanJamPeringatan" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-md shadow-indigo-600/20 cursor-pointer flex items-center gap-2">
                    <i class="fa fa-save"></i>
                    <span>Simpan Pengaturan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    window.openModalSettingJam = function() {
        const modal = document.getElementById('modalSettingJamPeringatan');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModalSettingJam = function() {
        const modal = document.getElementById('modalSettingJamPeringatan');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
    };

    window.syncWarningState = function(enable) {
        const checkbox = document.getElementById('modalToggleWarningEnabled');
        if (checkbox) checkbox.checked = enable;

        const track = document.getElementById('modalToggleTrack');
        const thumb = document.getElementById('modalToggleThumb');
        const label = document.getElementById('modalToggleLabel');
        const bannerLibur = document.getElementById('bannerStatusLibur');
        const containerJam = document.getElementById('containerJamInputs');

        const cardAktif = document.getElementById('cardModeAktif');
        const radioIndicatorAktif = document.getElementById('radioIndicatorAktif');
        const radioDotAktif = document.getElementById('radioDotAktif');

        const cardLibur = document.getElementById('cardModeLibur');
        const radioIndicatorLibur = document.getElementById('radioIndicatorLibur');
        const radioDotLibur = document.getElementById('radioDotLibur');

        if (enable) {
            if (track) track.style.backgroundColor = '#059669';
            if (thumb) thumb.style.transform = 'translateX(26px)';
            if (label) {
                label.innerText = 'ON (AKTIF)';
                label.style.backgroundColor = '#d1fae5';
                label.style.color = '#065f46';
            }
            if (bannerLibur) bannerLibur.classList.add('hidden');
            if (containerJam) {
                containerJam.classList.remove('opacity-40', 'pointer-events-none');
            }

            if (cardAktif) cardAktif.className = "p-3 rounded-xl border-2 transition-all cursor-pointer flex items-start gap-2.5 select-none bg-emerald-50 border-emerald-500 shadow-xs";
            if (radioIndicatorAktif) radioIndicatorAktif.className = "w-5 h-5 rounded-full border-2 mt-0.5 flex items-center justify-center shrink-0 border-emerald-600 bg-emerald-600";
            if (radioDotAktif) radioDotAktif.classList.remove('hidden');

            if (cardLibur) cardLibur.className = "p-3 rounded-xl border-2 transition-all cursor-pointer flex items-start gap-2.5 select-none bg-white border-slate-200 hover:border-slate-300 opacity-60";
            if (radioIndicatorLibur) radioIndicatorLibur.className = "w-5 h-5 rounded-full border-2 mt-0.5 flex items-center justify-center shrink-0 border-slate-300 bg-white";
            if (radioDotLibur) radioDotLibur.classList.add('hidden');
        } else {
            if (track) track.style.backgroundColor = '#cbd5e1';
            if (thumb) thumb.style.transform = 'translateX(0px)';
            if (label) {
                label.innerText = 'OFF (LIBUR)';
                label.style.backgroundColor = '#ffe4e6';
                label.style.color = '#9f1239';
            }
            if (bannerLibur) bannerLibur.classList.remove('hidden');
            if (containerJam) {
                containerJam.classList.add('opacity-40', 'pointer-events-none');
            }

            if (cardAktif) cardAktif.className = "p-3 rounded-xl border-2 transition-all cursor-pointer flex items-start gap-2.5 select-none bg-white border-slate-200 hover:border-slate-300 opacity-60";
            if (radioIndicatorAktif) radioIndicatorAktif.className = "w-5 h-5 rounded-full border-2 mt-0.5 flex items-center justify-center shrink-0 border-slate-300 bg-white";
            if (radioDotAktif) radioDotAktif.classList.add('hidden');

            if (cardLibur) cardLibur.className = "p-3 rounded-xl border-2 transition-all cursor-pointer flex items-start gap-2.5 select-none bg-rose-50 border-rose-500 shadow-xs";
            if (radioIndicatorLibur) radioIndicatorLibur.className = "w-5 h-5 rounded-full border-2 mt-0.5 flex items-center justify-center shrink-0 border-rose-600 bg-rose-600";
            if (radioDotLibur) radioDotLibur.classList.remove('hidden');
        }
    };

    // Auto open modal if URL has ?setting=1 or hash #setting
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('setting') === '1' || window.location.hash === '#setting') {
            openModalSettingJam();
        }
    });

    // Close on backdrop click
    document.getElementById('modalSettingJamPeringatan')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModalSettingJam();
        }
    });

    // Handle ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModalSettingJam();
        }
    });

    document.getElementById('formSettingJamPeringatan')?.addEventListener('submit', function() {
        const btn = document.getElementById('btnSimpanJamPeringatan');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menyimpan...';
        }
    });
</script>
@endif
