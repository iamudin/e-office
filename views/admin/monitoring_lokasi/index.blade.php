@extends('e-office::layout.app')

@push('styles')
{{-- Leaflet CSS --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<style>
    /* Custom Leaflet pulse marker animation */
    .leaflet-custom-marker {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pulse-ring {
        position: absolute;
        top: -6px;
        left: -6px;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background-color: rgba(16, 185, 129, 0.4);
        animation: marker-pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        pointer-events: none;
    }
    @keyframes marker-pulse {
        0% { transform: scale(0.8); opacity: 0.9; }
        50% { transform: scale(1.4); opacity: 0.2; }
        100% { transform: scale(1.6); opacity: 0; }
    }
    .leaflet-popup-content-wrapper {
        border-radius: 1.25rem !important;
        padding: 0 !important;
        overflow: hidden;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
    }
    .leaflet-popup-content {
        margin: 0 !important;
        line-height: 1.4 !important;
    }
    .leaflet-popup-tip {
        background: white !important;
    }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="monitoringLokasiApp(@js($initialSubordinates))" x-cloak>

    {{-- ═══════════ HEADER & STATISTIK ═══════════ --}}
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-teal-600 text-white flex items-center justify-center text-xl shadow-md shadow-indigo-500/20">
                    <i class="fa fa-map-marker"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        Monitoring Lokasi Bawahan
                        @if($isKadis)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                Pimpinan Utama
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                Atasan Langsung
                            </span>
                        @endif
                    </h2>
                    <p class="text-xs text-slate-500">
                        Pantau koordinat GPS login terakhir, posisi terkini, dan aktivitas dalam aplikasi secara realtime.
                    </p>
                </div>
            </div>
        </div>

        {{-- Statistik Badges & Tombol Refresh --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="px-3.5 py-2 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-3 shadow-2xs">
                <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa fa-users"></i>
                </div>
                <div>
                    <div class="text-[10px] uppercase font-bold text-slate-400">Total Bawahan</div>
                    <div class="text-sm font-extrabold text-slate-900" x-text="stats.total">{{ $totalBawahan }}</div>
                </div>
            </div>

            <div class="px-3.5 py-2 rounded-2xl bg-emerald-50/60 border border-emerald-200/80 flex items-center gap-3 shadow-2xs">
                <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-sm font-bold relative">
                    <i class="fa fa-circle text-[9px] animate-ping absolute"></i>
                    <i class="fa fa-bolt"></i>
                </div>
                <div>
                    <div class="text-[10px] uppercase font-bold text-emerald-800">Sedang Aktif</div>
                    <div class="text-sm font-extrabold text-emerald-950" x-text="stats.online">{{ $totalOnline }}</div>
                </div>
            </div>

            <div class="px-3.5 py-2 rounded-2xl bg-teal-50/60 border border-teal-200/80 flex items-center gap-3 shadow-2xs">
                <div class="w-8 h-8 rounded-xl bg-teal-500 text-white flex items-center justify-center text-sm font-bold">
                    <i class="fa fa-crosshairs"></i>
                </div>
                <div>
                    <div class="text-[10px] uppercase font-bold text-teal-800">Ada Titik GPS</div>
                    <div class="text-sm font-extrabold text-teal-950" x-text="stats.with_coords">{{ $totalWithLocation }}</div>
                </div>
            </div>

            <button type="button" 
                    @click="fetchData(true)" 
                    :disabled="loading"
                    class="px-3.5 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition flex items-center gap-2 shadow-sm shadow-indigo-600/30 cursor-pointer disabled:opacity-50">
                <i class="fa fa-refresh" :class="loading ? 'fa-spin' : ''"></i>
                <span class="hidden sm:inline">Perbarui</span>
            </button>
        </div>
    </div>

    {{-- ═══════════ MAIN CONTENT: PETA & DAFTAR BAWAHAN ═══════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {{-- SISI KIRI: PETA INTERAKTIF LEAFLET (8 Cols) --}}
        <div class="lg:col-span-8 bg-white rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-100 flex flex-col">
            {{-- Toolbar Peta --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa fa-globe text-teal-600"></i> Peta Sebaran Lokasi Realtime (OpenStreetMap)
                    </span>
                    <span class="text-[11px] text-slate-400" x-show="lastUpdateTime" x-text="'Diperbarui: ' + lastUpdateTime"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="fitMapBounds()" 
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5 cursor-pointer"
                            title="Tampilkan seluruh posisi bawahan">
                        <i class="fa fa-expand"></i> Pusatkan Semua
                    </button>
                </div>
            </div>

            {{-- Container Peta Leaflet --}}
            <div class="relative w-full rounded-2xl overflow-hidden border border-slate-200 h-[500px] sm:h-[580px] lg:h-[620px] bg-slate-100">
                <div id="leafletMap" class="w-full h-full z-10"></div>

                {{-- Loading Overlay di atas peta --}}
                <div x-cloak x-show="loading" class="absolute inset-0 bg-white/60 backdrop-blur-xs flex items-center justify-center z-20 transition" style="display: none;">
                    <div class="bg-white px-4 py-2.5 rounded-2xl shadow-xl border border-slate-200 text-xs font-bold text-slate-700 flex items-center gap-2.5">
                        <i class="fa fa-spinner fa-spin text-indigo-600 text-base"></i>
                        <span>Memuat data posisi bawahan...</span>
                    </div>
                </div>

                {{-- Empty Coordinates Floating Notice --}}
                <div x-cloak x-show="!loading && stats.with_coords === 0" 
                     class="absolute bottom-5 left-1/2 -translate-x-1/2 bg-white/95 backdrop-blur-md px-5 py-3 rounded-2xl border border-amber-200 shadow-xl z-20 text-center max-w-sm"
                     style="display: none;">
                    <div class="text-amber-600 text-lg mb-1"><i class="fa fa-exclamation-triangle"></i></div>
                    <p class="text-xs font-bold text-slate-800">Belum Ada Titik Koordinat Terdeteksi</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Bawahan belum memberikan izin lokasi GPS saat login atau belum mengakses sistem.</p>
                </div>
            </div>

            {{-- Legenda Marker --}}
            <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-3">
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block shadow-2xs"></span>
                        <strong class="text-slate-700">Online</strong> (&le; 5 menit)
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-amber-400 inline-block shadow-2xs"></span>
                        <strong class="text-slate-700">Baru Saja</strong> (&le; 1 jam)
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-slate-400 inline-block shadow-2xs"></span>
                        <strong class="text-slate-700">Offline</strong> (&gt; 1 jam)
                    </span>
                </div>
                <span class="text-[11px] text-slate-400">
                    <i class="fa fa-info-circle mr-1"></i>Klik pada pin peta untuk info lengkap
                </span>
            </div>
        </div>

        {{-- SISI KANAN: DAFTAR KARTU BAWAHAN (4 Cols) --}}
        <div class="lg:col-span-4 bg-white rounded-3xl p-5 shadow-sm border border-slate-100 flex flex-col h-full max-h-[750px]">
            <div class="mb-4">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa fa-users text-indigo-600"></i> Daftar Bawahan
                    </h3>
                    <span class="text-xs font-extrabold px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700" x-text="filteredSubordinates.length + ' Orang'"></span>
                </div>

                {{-- Search Box --}}
                <div class="relative mb-3">
                    <i class="fa fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Cari nama, jabatan, lokasi..." 
                           class="w-full pl-9 pr-3 py-2 text-xs rounded-xl bg-slate-50 border border-slate-200 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 outline-none transition">
                </div>

                {{-- Filter Tabs --}}
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                    <button type="button" 
                            @click="filterStatus = 'all'" 
                            :class="filterStatus === 'all' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-2.5 py-1 rounded-lg transition whitespace-nowrap cursor-pointer">
                        Semua (<span x-text="subordinates.length"></span>)
                    </button>
                    <button type="button" 
                            @click="filterStatus = 'online'" 
                            :class="filterStatus === 'online' ? 'bg-emerald-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-2.5 py-1 rounded-lg transition whitespace-nowrap cursor-pointer">
                        Online (<span x-text="stats.online"></span>)
                    </button>
                    <button type="button" 
                            @click="filterStatus = 'coords'" 
                            :class="filterStatus === 'coords' ? 'bg-teal-600 text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            class="px-2.5 py-1 rounded-lg transition whitespace-nowrap cursor-pointer">
                        Di Peta (<span x-text="stats.with_coords"></span>)
                    </button>
                </div>
            </div>

            {{-- List Bawahan Scrollable --}}
            <div class="overflow-y-auto space-y-3 flex-1 pr-1" style="max-height: 580px;">
                <template x-for="sub in filteredSubordinates" :key="sub.id">
                    <div class="p-3.5 rounded-2xl border transition-all cursor-pointer relative group"
                         :class="selectedId === sub.id ? 'bg-indigo-50/70 border-indigo-300 ring-2 ring-indigo-200' : 'bg-slate-50/50 hover:bg-white hover:border-slate-300 border-slate-200/80 shadow-2xs'"
                         @click="focusSubordinate(sub)">
                        
                        <div class="flex items-start justify-between gap-2.5 mb-2">
                            <div class="flex items-center gap-2.5">
                                {{-- Avatar Inisial dengan status badge --}}
                                <div class="relative flex-shrink-0">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs text-white shadow-2xs"
                                         :class="sub.status === 'online' ? 'bg-gradient-to-br from-emerald-500 to-teal-600' : (sub.status === 'recent' ? 'bg-gradient-to-br from-amber-500 to-orange-600' : 'bg-gradient-to-br from-slate-400 to-slate-600')">
                                        <span x-text="sub.avatar_letter"></span>
                                    </div>
                                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white"
                                          :class="sub.status === 'online' ? 'bg-emerald-500' : (sub.status === 'recent' ? 'bg-amber-400' : 'bg-slate-300')"></span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-slate-900 group-hover:text-indigo-600 transition truncate" x-text="sub.nama"></h4>
                                    <p class="text-[11px] text-slate-500 truncate" x-text="sub.jabatan"></p>
                                </div>
                            </div>

                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold flex-shrink-0"
                                  :class="sub.status === 'online' ? 'bg-emerald-100 text-emerald-800' : (sub.status === 'recent' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600')"
                                  x-text="sub.status === 'online' ? 'Online' : sub.last_active_human"></span>
                        </div>

                        {{-- Lokasi Terakhir --}}
                        <div class="mb-2 p-2 rounded-xl bg-white border border-slate-100 text-[11px] space-y-1">
                            <div class="flex items-start gap-1.5 text-slate-700">
                                <i class="fa fa-map-marker text-rose-500 mt-0.5 flex-shrink-0"></i>
                                <span class="line-clamp-2" x-text="sub.location_name"></span>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-slate-50">
                                <span><i class="fa fa-history mr-1"></i>Aktivitas: <strong class="text-slate-600" x-text="sub.last_activity"></strong></span>
                            </div>
                        </div>

                        {{-- Actions Bar --}}
                        <div class="flex items-center justify-between gap-2 pt-1">
                            <button type="button" 
                                    @click.stop="focusSubordinate(sub)"
                                    class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                <i class="fa fa-crosshairs"></i> Lihat di Peta
                            </button>
                            <button type="button" 
                                    @click.stop="openHistoryModal(sub)"
                                    class="text-[11px] font-bold text-slate-500 hover:text-teal-600 flex items-center gap-1">
                                <i class="fa fa-history"></i> Riwayat Jejak
                            </button>
                        </div>
                    </div>
                </template>

                <div x-show="filteredSubordinates.length === 0" class="text-center py-10 bg-slate-50 rounded-2xl border border-dashed border-slate-200 p-4">
                    <i class="fa fa-user-times text-slate-300 text-2xl mb-2"></i>
                    <p class="text-xs font-semibold text-slate-600">Tidak ada bawahan ditemukan</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Coba ubah kata kunci pencarian atau filter.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ MODAL RIWAYAT JEJAK LOKASI ═══════════ --}}
    <div x-show="historyModalOpen" 
         x-transition:enter="transition ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="transition ease-in duration-150" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto">
        <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-8 max-h-[90vh] flex flex-col"
             @click.away="historyModalOpen = false">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-indigo-700 to-teal-700 text-white flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center text-white text-lg">
                        <i class="fa fa-history"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold leading-tight" x-text="'Riwayat Lokasi: ' + (historyPejabat?.nama || 'Bawahan')"></h3>
                        <p class="text-xs text-indigo-100 mt-0.5" x-text="historyPejabat?.jabatan || 'Staf'"></p>
                    </div>
                </div>
                <button type="button" @click="historyModalOpen = false" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer">
                    <i class="fa fa-times text-sm"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto flex-1 space-y-4 bg-slate-50/50">
                <div x-show="historyLoading" class="text-center py-12">
                    <i class="fa fa-spinner fa-spin text-2xl text-indigo-600 mb-2"></i>
                    <p class="text-xs font-semibold text-slate-500">Mengambil riwayat log lokasi...</p>
                </div>

                <div x-show="!historyLoading && historyLogs.length === 0" class="text-center py-10 bg-white rounded-2xl border border-slate-200 p-6">
                    <i class="fa fa-map-marker text-3xl text-slate-300 mb-2"></i>
                    <h4 class="text-xs font-bold text-slate-700">Belum Ada Riwayat Tercatat</h4>
                    <p class="text-[11px] text-slate-400 mt-1">Bawahan ini belum memiliki log riwayat lokasi tersimpan di sistem.</p>
                </div>

                <div x-show="!historyLoading && historyLogs.length > 0" class="space-y-3">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                        <span>Menampilkan <strong class="text-slate-800" x-text="historyLogs.length"></strong> dari <strong class="text-slate-800" x-text="historyTotal"></strong> Riwayat Jejak:</span>
                        <span class="text-[11px] font-medium text-slate-400 normal-case" x-show="historyHasMore">5 terbaru dimuat</span>
                    </div>

                    <div class="relative pl-6 space-y-4 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-indigo-200">
                        <template x-for="(log, idx) in historyLogs" :key="log.id">
                            <div class="relative bg-white p-3.5 rounded-2xl border border-slate-200/80 shadow-2xs hover:border-indigo-300 transition">
                                {{-- Bullet --}}
                                <div class="absolute -left-[27px] top-4 w-3.5 h-3.5 rounded-full border-2 border-white shadow-xs"
                                     :class="idx === 0 ? 'bg-emerald-500 ring-4 ring-emerald-100' : 'bg-indigo-500'"></div>

                                <div class="flex items-start justify-between gap-2 mb-1.5">
                                    <span class="text-xs font-bold text-slate-900" x-text="log.activity"></span>
                                    <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md" x-text="log.time_human"></span>
                                </div>

                                <div class="text-[11px] text-slate-600 flex items-start gap-1.5 mb-2">
                                    <i class="fa fa-map-marker text-rose-500 mt-0.5"></i>
                                    <span x-text="log.location_name"></span>
                                </div>

                                <div class="flex flex-wrap items-center justify-between text-[10px] text-slate-400 pt-1.5 border-t border-slate-50 gap-2">
                                    <span>Koordinat: <strong class="text-slate-600" x-text="log.lat.toFixed(5) + ', ' + log.lng.toFixed(5)"></strong></span>
                                    <div class="flex items-center gap-3">
                                        <span>Waktu: <strong class="text-slate-600" x-text="log.time"></strong></span>
                                        <a :href="'https://www.openstreetmap.org/?mlat=' + log.lat + '&mlon=' + log.lng + '#map=17/' + log.lat + '/' + log.lng" target="_blank" class="text-teal-600 hover:text-teal-800 font-bold flex items-center gap-1">
                                            <i class="fa fa-map-o"></i> OpenStreetMap
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Tombol Load More (Lihat Riwayat Sebelumnya) --}}
                    <div x-show="historyHasMore" class="pt-3 pb-1 text-center">
                        <button type="button" 
                                @click="loadMoreHistory()" 
                                :disabled="historyLoadingMore"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 hover:text-indigo-800 text-xs font-bold transition border border-indigo-200 shadow-2xs cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
                            <span x-show="historyLoadingMore" class="inline-flex items-center gap-2">
                                <i class="fa fa-spinner fa-spin text-sm"></i>
                                <span>Memuat riwayat sebelumnya...</span>
                            </span>
                            <span x-show="!historyLoadingMore" class="inline-flex items-center gap-2">
                                <i class="fa fa-chevron-down text-xs"></i>
                                <span>Lihat Riwayat Sebelumnya (Muat 5 Lagi)</span>
                            </span>
                        </button>
                    </div>

                    <div x-show="!historyHasMore && historyLogs.length > 5" class="pt-2 text-center text-[11px] text-slate-400">
                        <i class="fa fa-check-circle text-emerald-500 mr-1"></i> Seluruh riwayat jejak lokasi telah ditampilkan (<span x-text="historyTotal"></span> log).
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3 bg-white border-t border-slate-200 flex items-center justify-end flex-shrink-0">
                <button type="button" @click="historyModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 border border-slate-200 transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
{{-- Leaflet JS via CDNJS --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<script>
    // Variabel Leaflet di luar Alpine reactivity Proxy agar tidak crash
    let leafletMapInstance = null;
    let leafletMarkersLayer = null;
    let osmTileLayer = null;

    function monitoringLokasiApp(initialSubs = []) {
        return {
            subordinates: Array.isArray(initialSubs) ? initialSubs : [],
            stats: {
                total: {{ $totalBawahan }},
                online: {{ $totalOnline }},
                with_coords: {{ $totalWithLocation }}
            },
            searchQuery: '',
            filterStatus: 'all',
            selectedId: null,
            loading: false,
            lastUpdateTime: '{{ now()->format("H:i:s") }}',
            historyModalOpen: false,
            historyLoading: false,
            historyLoadingMore: false,
            historyPejabat: null,
            historyLogs: [],
            historyPage: 1,
            historyHasMore: false,
            historyTotal: 0,

            init() {
                this.$nextTick(() => {
                    this.initLeafletMap();
                    this.renderMapMarkers();

                    // Re-calculate layout map setelah DOM render penuh
                    setTimeout(() => {
                        if (leafletMapInstance) {
                            leafletMapInstance.invalidateSize();
                        }
                    }, 300);

                    // Auto-refresh data setiap 30 detik di latar belakang
                    setInterval(() => {
                        this.fetchData(false);
                    }, 30000);
                });
            },

            initLeafletMap() {
                const mapEl = document.getElementById('leafletMap');
                if (!mapEl || leafletMapInstance) return;

                // Center awal default (Indonesia tengah / Riau default)
                const defaultCenter = [0.5071, 101.4478];
                const defaultZoom = 12;

                leafletMapInstance = L.map('leafletMap', {
                    center: defaultCenter,
                    zoom: defaultZoom,
                    zoomControl: true,
                    attributionControl: false
                });

                // Attribution control ringkas di pojok kanan bawah
                L.control.attribution({ position: 'bottomright', prefix: '&copy; OpenStreetMap' }).addTo(leafletMapInstance);

                // Tile Layer OpenStreetMap Standard
                osmTileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                });

                osmTileLayer.addTo(leafletMapInstance);
                leafletMarkersLayer = L.featureGroup().addTo(leafletMapInstance);
            },

            async fetchData(showSpinner = false) {
                if (showSpinner) this.loading = true;

                try {
                    const res = await fetch("{{ plugin_route('e-office.monitoring-lokasi.data') }}", {
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await res.json();

                    if (json.success) {
                        this.subordinates = json.data || [];
                        this.stats.total = json.total || 0;
                        this.stats.online = json.online || 0;
                        this.stats.with_coords = json.with_coords || 0;

                        const now = new Date();
                        this.lastUpdateTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                        this.renderMapMarkers();
                    }
                } catch (e) {
                    console.error('Error fetching live location data:', e);
                } finally {
                    if (showSpinner) this.loading = false;
                }
            },

            renderMapMarkers() {
                if (!leafletMapInstance || !leafletMarkersLayer) return;

                leafletMarkersLayer.clearLayers();
                let validCoords = [];

                this.subordinates.forEach(sub => {
                    if (sub.lat && sub.lng && !isNaN(sub.lat) && !isNaN(sub.lng)) {
                        const latLng = [parseFloat(sub.lat), parseFloat(sub.lng)];
                        validCoords.push(latLng);

                        const isOnline = sub.status === 'online';
                        const isRecent = sub.status === 'recent';
                        const bgColor = isOnline ? '#10b981' : (isRecent ? '#f59e0b' : '#64748b');

                        // Custom HTML Icon
                        const customIcon = L.divIcon({
                            className: 'leaflet-custom-marker',
                            html: `
                                <div style="position: relative; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                                    ${isOnline ? '<div class="pulse-ring"></div>' : ''}
                                    <div style="background-color: ${bgColor}; width: 34px; height: 34px; border-radius: 50%; border: 3px solid #ffffff; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 13px;">
                                        ${sub.avatar_letter}
                                    </div>
                                    <div style="position: absolute; bottom: -5px; left: 50%; transform: translateX(-50%); width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-top: 6px solid ${bgColor};"></div>
                                </div>
                            `,
                            iconSize: [36, 42],
                            iconAnchor: [18, 42],
                            popupAnchor: [0, -40]
                        });

                        const marker = L.marker(latLng, { icon: customIcon });
                        marker.subId = sub.id;

                        // Popup HTML modern
                        const popupHtml = `
                            <div style="width: 280px; font-family: Inter, sans-serif;">
                                <div style="background: linear-gradient(135deg, #4f46e5, #0d9488); padding: 12px 14px; color: white;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                        <div style="font-weight: 700; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            ${sub.nama}
                                        </div>
                                        <span style="font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 9999px; background-color: ${isOnline ? '#10b981' : (isRecent ? '#f59e0b' : '#94a3b8')}; color: white;">
                                            ${isOnline ? 'Online' : sub.last_active_human}
                                        </span>
                                    </div>
                                    <div style="font-size: 11px; opacity: 0.9; margin-top: 2px;">
                                        ${sub.jabatan}
                                    </div>
                                </div>
                                <div style="padding: 12px 14px; font-size: 11px; color: #334155; line-height: 1.5;">
                                    <div style="margin-bottom: 8px;">
                                        <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Lokasi Terakhir:</div>
                                        <div style="font-weight: 600; color: #0f172a; margin-top: 1px;">
                                            <i class="fa fa-map-marker text-rose-500 mr-1"></i> ${sub.location_name}
                                        </div>
                                    </div>
                                    <div style="margin-bottom: 8px;">
                                        <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Aktivitas Terakhir:</div>
                                        <div style="color: #475569; margin-top: 1px;">
                                            <i class="fa fa-tasks text-indigo-500 mr-1"></i> ${sub.last_activity}
                                        </div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 10px; color: #64748b; padding-top: 6px; border-top: 1px solid #f1f5f9;">
                                        <span>Koordinat: <strong>${parseFloat(sub.lat).toFixed(4)}, ${parseFloat(sub.lng).toFixed(4)}</strong></span>
                                        <span>Waktu: <strong>${sub.last_active_at || '-'}</strong></span>
                                    </div>
                                </div>
                                <div style="padding: 8px 14px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                    <a href="https://www.openstreetmap.org/?mlat=${sub.lat}&mlon=${sub.lng}#map=17/${sub.lat}/${sub.lng}" target="_blank" style="font-size: 11px; font-weight: 700; color: #0d9488; text-decoration: none;">
                                        <i class="fa fa-map-o mr-1"></i> Buka di OpenStreetMap
                                    </a>
                                </div>
                            </div>
                        `;

                        marker.bindPopup(popupHtml);
                        leafletMarkersLayer.addLayer(marker);
                    }
                });

                // Auto fit bounds jika ada marker
                if (validCoords.length === 1 && !this.selectedId) {
                    leafletMapInstance.setView(validCoords[0], 15);
                } else if (validCoords.length > 1 && !this.selectedId) {
                    this.fitMapBounds();
                }
            },

            fitMapBounds() {
                if (!leafletMapInstance || !leafletMarkersLayer) return;
                const layers = leafletMarkersLayer.getLayers();
                if (layers.length === 0) return;

                if (layers.length === 1) {
                    leafletMapInstance.setView(layers[0].getLatLng(), 15, { animate: true });
                } else {
                    leafletMapInstance.fitBounds(leafletMarkersLayer.getBounds(), {
                        padding: [50, 50],
                        maxZoom: 16
                    });
                }
            },

            focusSubordinate(sub) {
                this.selectedId = sub.id;

                if (sub.lat && sub.lng && !isNaN(sub.lat) && !isNaN(sub.lng)) {
                    const latLng = [parseFloat(sub.lat), parseFloat(sub.lng)];
                    if (leafletMapInstance) {
                        leafletMapInstance.setView(latLng, 16, { animate: true, duration: 1 });

                        // Buka popup marker terkait
                        leafletMarkersLayer.eachLayer(layer => {
                            if (layer.subId === sub.id) {
                                layer.openPopup();
                            }
                        });
                    }
                } else {
                    alert('Bawahan ' + sub.nama + ' belum memiliki rekaman titik koordinat GPS.');
                }
            },

            async openHistoryModal(sub) {
                this.historyPejabat = sub;
                this.historyLogs = [];
                this.historyPage = 1;
                this.historyHasMore = false;
                this.historyTotal = 0;
                this.historyLoading = true;
                this.historyLoadingMore = false;
                this.historyModalOpen = true;

                await this.fetchHistoryPage(1, false);
                this.historyLoading = false;
            },

            async fetchHistoryPage(page = 1, append = false) {
                if (!this.historyPejabat) return;

                try {
                    const baseUrl = "{{ plugin_route('e-office.monitoring-lokasi.history', 'REPLACE_ID') }}".replace('REPLACE_ID', this.historyPejabat.id);
                    const url = `${baseUrl}?page=${page}`;
                    const res = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const json = await res.json();
                    if (json.success) {
                        const newLogs = json.logs || [];
                        if (append) {
                            this.historyLogs = [...this.historyLogs, ...newLogs];
                        } else {
                            this.historyLogs = newLogs;
                        }
                        this.historyPage = json.current_page || page;
                        this.historyHasMore = !!json.has_more;
                        this.historyTotal = json.total || 0;
                    }
                } catch (e) {
                    console.error('Error loading history:', e);
                }
            },

            async loadMoreHistory() {
                if (this.historyLoadingMore || !this.historyHasMore) return;
                this.historyLoadingMore = true;
                await this.fetchHistoryPage(this.historyPage + 1, true);
                this.historyLoadingMore = false;
            },

            get filteredSubordinates() {
                let list = this.subordinates;

                // Filter by Status Tab
                if (this.filterStatus === 'online') {
                    list = list.filter(s => s.status === 'online');
                } else if (this.filterStatus === 'coords') {
                    list = list.filter(s => s.lat && s.lng);
                }

                // Filter by Search Query
                if (this.searchQuery.trim() !== '') {
                    const q = this.searchQuery.toLowerCase();
                    list = list.filter(s => 
                        (s.nama && s.nama.toLowerCase().includes(q)) ||
                        (s.jabatan && s.jabatan.toLowerCase().includes(q)) ||
                        (s.location_name && s.location_name.toLowerCase().includes(q)) ||
                        (s.last_activity && s.last_activity.toLowerCase().includes(q))
                    );
                }

                return list;
            }
        };
    }
</script>
@endpush
