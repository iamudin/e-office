@extends('e-office::layout.app', ['title' => 'Briefing & Monitoring Tugas'])

@section('content')
<div class="space-y-6">

    @if($isKadis)
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{--                     DASHBOARD MONITORING PIMPINAN UTAMA (KADIS)        --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}

    {{-- Header Banner & Periode Filter --}}
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        {{-- Background Decoration --}}
        <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-12 -top-12 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="max-w-2xl">
                <div class="flex flex-wrap items-center gap-2.5 mb-3">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-200 text-xs font-semibold">
                        <i class="fa fa-shield"></i> Pimpinan Utama (Monitoring &amp; Evaluasi)
                    </div>

                    <button type="button" onclick="openModalSettingJam()" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full {{ ($warningEnabled ?? true) ? 'bg-amber-500/20 hover:bg-amber-500/30 border-amber-400/40 text-amber-200' : 'bg-rose-500/20 hover:bg-rose-500/30 border-rose-400/40 text-rose-200' }} border text-xs font-semibold transition cursor-pointer shadow-sm active:scale-95">
                        <i class="fa {{ ($warningEnabled ?? true) ? 'fa-clock-o text-amber-300' : 'fa-bell-slash text-rose-300' }}"></i> Atur Jam &amp; Status Peringatan
                        <span class="{{ ($warningEnabled ?? true) ? 'bg-amber-400/30 text-white' : 'bg-rose-400/30 text-white' }} text-[10px] px-2 py-0.5 rounded-full font-mono font-bold">
                            {{ ($warningEnabled ?? true) ? ($jamForward ?? '09:00') . ' • ' . ($jamAction ?? '14:00') . ' WIB' : 'NONAKTIF (LIBUR)' }}
                        </span>
                    </button>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Dashboard Monitoring Briefing Harian</h1>
                <p class="text-sm sm:text-base text-slate-300 mt-2 leading-relaxed">
                    Pantau kepatuhan pejabat struktural 1 tingkat di bawah pimpinan utama (Sekretaris & Kepala Bidang) dalam menerbitkan arahan tugas dan progres ceklis instruksi bawahan.
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2.5 text-xs text-slate-300">
                    @if($warningEnabled ?? true)
                        <div class="flex items-center gap-1.5 bg-emerald-500/20 border border-emerald-400/30 px-2.5 py-1 rounded-lg backdrop-blur-xs text-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span> Peringatan Aktif
                        </div>
                        <div class="flex items-center gap-1.5 bg-white/10 border border-white/15 px-2.5 py-1 rounded-lg backdrop-blur-xs">
                            <i class="fa fa-bell text-amber-300"></i> Batas Beri Tugas: <strong class="text-white font-mono font-bold">{{ $jamForward ?? '09:00' }} WIB</strong>
                        </div>
                        <div class="flex items-center gap-1.5 bg-white/10 border border-white/15 px-2.5 py-1 rounded-lg backdrop-blur-xs">
                            <i class="fa fa-bell text-indigo-300"></i> Batas Respon Tugas: <strong class="text-white font-mono font-bold">{{ $jamAction ?? '14:00' }} WIB</strong>
                        </div>
                    @else
                        <div class="flex items-center gap-1.5 bg-rose-500/30 border border-rose-400/40 px-3 py-1 rounded-lg backdrop-blur-xs text-rose-200 font-bold">
                            <i class="fa fa-bell-slash text-rose-300"></i> Peringatan Sedang Dinonaktifkan (Hari Libur / Cuti)
                        </div>
                    @endif
                </div>
            </div>

            {{-- Filter Form --}}
            <div class="bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/15 w-full lg:w-auto shadow-inner">
                <form action="{{ plugin_route('e-office.briefing.index') }}" method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-200 mb-1">Dari Tanggal</label>
                        <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}" 
                            class="w-full sm:w-36 bg-slate-800/80 border border-white/20 text-white rounded-xl text-xs py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-200 mb-1">Sampai Tanggal</label>
                        <input type="date" name="tanggal_selesai" value="{{ $tanggalSelesai }}" 
                            class="w-full sm:w-36 bg-slate-800/80 border border-white/20 text-white rounded-xl text-xs py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-md">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <a href="{{ plugin_route('e-office.briefing.index', ['tanggal' => now()->toDateString()]) }}" 
                            title="Tampilkan Hari Ini"
                            class="inline-flex items-center justify-center px-3 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-medium rounded-xl transition border border-white/20">
                            Hari Ini
                        </a>
                    </div>
                </form>
                <div class="text-[11px] text-slate-300 mt-2 flex items-center gap-1.5">
                    <i class="fa fa-calendar-check-o text-indigo-300"></i>
                    <span>
                        Periode: 
                        <strong class="text-white">{{ \Carbon\Carbon::parse($tanggalMulai)->translatedFormat('d M Y') }}</strong>
                        @if($tanggalMulai !== $tanggalSelesai)
                            s/d <strong class="text-white">{{ \Carbon\Carbon::parse($tanggalSelesai)->translatedFormat('d M Y') }}</strong>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Ringkasan Statistik KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        {{-- Total Pejabat --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pejabat Struktural</p>
                <h3 class="text-2xl font-extrabold text-slate-900 mt-1">{{ $totalPejabat }} <span class="text-xs font-semibold text-slate-500">Pejabat</span></h3>
                <p class="text-xs text-slate-500 mt-1">1 Tingkat di bawah Pimpinan</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">
                <i class="fa fa-users"></i>
            </div>
        </div>

        {{-- Sudah Membuat (Hijau) --}}
        <div class="bg-emerald-50/80 rounded-2xl p-5 border border-emerald-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Sudah Buat Briefing</p>
                <h3 class="text-2xl font-extrabold text-emerald-900 mt-1">
                    {{ $sudahMembuatCount }} 
                    <span class="text-xs font-semibold text-emerald-700">/ {{ $totalPejabat }} Pejabat</span>
                </h3>
                <p class="text-xs text-emerald-600 mt-1 font-medium">
                    {{ $totalPejabat > 0 ? round(($sudahMembuatCount / $totalPejabat) * 100) : 0 }}% Kepatuhan Periode Ini
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center text-xl font-bold shadow-sm shadow-emerald-600/30">
                <i class="fa fa-check"></i>
            </div>
        </div>

        {{-- Belum Membuat (Merah) --}}
        <div class="bg-rose-50/80 rounded-2xl p-5 border border-rose-200/80 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-rose-700 uppercase tracking-wider">Belum Buat Briefing</p>
                <h3 class="text-2xl font-extrabold text-rose-900 mt-1">
                    {{ $belumMembuatCount }} 
                    <span class="text-xs font-semibold text-rose-700">/ {{ $totalPejabat }} Pejabat</span>
                </h3>
                <p class="text-xs text-rose-600 mt-1 font-medium">
                    @if($belumMembuatCount > 0)
                        @if(isset($diperingatkanCount) && $diperingatkanCount > 0)
                            <span class="text-emerald-700 font-bold"><i class="fa fa-check-circle text-emerald-600"></i> {{ $diperingatkanCount }} pejabat</span> telah diberi notif peringatan sistem
                        @else
                            Perlu diingatkan untuk input arahan
                        @endif
                    @else
                        Semua pejabat telah membuat
                    @endif
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-600 text-white flex items-center justify-center text-xl font-bold shadow-sm shadow-rose-600/30">
                <i class="fa fa-exclamation"></i>
            </div>
        </div>

        {{-- Progres Instruksi / Checklist --}}
        <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Instruksi Selesai</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 mt-1">
                        {{ $totalSelesaiInstruksi }} <span class="text-xs font-semibold text-slate-500">/ {{ $totalInstruksi }} Poin</span>
                    </h3>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold">
                    <i class="fa fa-tasks"></i>
                </div>
            </div>
            <div class="mt-3">
                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 mb-1">
                    <span>Realisasi Kegiatan</span>
                    <span>{{ $overallProgress }}%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $overallProgress }}%"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{--             MONITORING PEJABAT 1 TINGKAT (HIJAU / MERAH)               --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa fa-id-badge text-indigo-600"></i> Status Kepatuhan & Progres Pejabat Struktural
                </h2>
                <p class="text-xs text-slate-500">
                    Warna hijau menandakan pejabat telah menerbitkan briefing pada periode ini. Warna merah menandakan belum menerbitkan.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3 text-xs">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 font-semibold border border-emerald-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span> Sudah Buat Briefing
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-100 text-rose-800 font-semibold border border-rose-300">
                    <span class="w-2 h-2 rounded-full bg-rose-600 animate-pulse"></span> Belum Buat Briefing
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold border border-emerald-200">
                    <i class="fa fa-paper-plane text-emerald-600 text-[10px]"></i> Sudah Diperingatkan Sistem
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($subordinates as $sub)
                @if($sub->has_briefing)
                    {{-- ── CARD HIJAU: SUDAH MEMBUAT BRIEFING ── --}}
                    <div class="bg-gradient-to-br from-emerald-50/90 via-emerald-50/40 to-white rounded-3xl p-6 border-2 border-emerald-400 shadow-md hover:shadow-lg transition-all relative overflow-hidden flex flex-col justify-between">
                        
                        {{-- Top Badge --}}
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center text-white text-base font-extrabold shadow-md shadow-emerald-600/30 flex-shrink-0">
                                    {{ strtoupper(substr($sub->nama ?? 'P', 0, 1)) }}
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 leading-tight">{{ $sub->nama }}</h3>
                                    <p class="text-xs font-semibold text-emerald-800 mt-0.5">{{ $sub->jabatan ?: ($sub->alias_jabatan ?: 'Pejabat Struktural') }}</p>
                                    @if($sub->nohp)
                                        <p class="text-[11px] text-slate-500 mt-0.5"><i class="fa fa-phone mr-1"></i>{{ $sub->nohp }}</p>
                                    @endif
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-600 text-white shadow-sm flex-shrink-0">
                                <i class="fa fa-check-circle"></i> Sudah Menerbitkan
                            </span>
                        </div>

                        {{-- Briefing Information & Instructions --}}
                        <div class="space-y-4 my-2">
                            @foreach($sub->briefings as $bIndex => $brf)
                                <div class="bg-white/90 rounded-2xl p-4 border border-emerald-200/80 shadow-sm space-y-3">
                                    <div class="flex items-start justify-between gap-2 border-b border-emerald-100 pb-2.5">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-lg border border-indigo-100">
                                                    Briefing #{{ $bIndex + 1 }}
                                                </span>
                                                <h4 class="text-sm font-bold text-slate-900">{{ $brf->judul }}</h4>
                                            </div>
                                            <p class="text-[11px] text-slate-500 mt-1">
                                                <i class="fa fa-clock-o mr-1"></i>{{ $brf->created_at->translatedFormat('d M Y, H:i') }} &bull; Ditujukan ke <span class="font-semibold text-slate-700">{{ $brf->disposisis->count() }} bawahan</span>
                                            </p>
                                        </div>
                                        <a href="{{ plugin_route('e-office.briefing.show', $brf->id) }}" 
                                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex-shrink-0">
                                            <i class="fa fa-eye"></i> Detail
                                        </a>
                                    </div>

                                    {{-- List Poin Instruksi & Ceklis --}}
                                    @php
                                        $chks = $brf->checklists;
                                        $chksCount = $chks->count();
                                        $chksSelesai = $chks->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
                                        $chksProses = $chks->where('status', 'proses')->count();
                                        $chksPercent = $chksCount > 0 ? round(($chksSelesai / $chksCount) * 100) : 0;
                                    @endphp

                                    <div>
                                        <div class="flex items-center justify-between text-xs mb-1.5">
                                            <span class="font-bold text-slate-700 flex items-center gap-1.5">
                                                <i class="fa fa-list-check text-emerald-600"></i> Poin Arahan / Instruksi:
                                            </span>
                                            <span class="font-bold text-emerald-700 text-[11px]">
                                                {{ $chksSelesai }} Selesai, {{ $chksProses }} Proses / {{ $chksCount }} Poin ({{ $chksPercent }}%)
                                            </span>
                                        </div>
                                        
                                        <div class="w-full bg-emerald-100 rounded-full h-1.5 mb-3 overflow-hidden">
                                            <div class="bg-emerald-600 h-1.5 rounded-full transition-all" style="width: {{ $chksPercent }}%"></div>
                                        </div>

                                        @if($chksCount > 0)
                                            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                                @foreach($chks as $chk)
                                                    @php
                                                        $isDone = ($chk->status === 'selesai' || $chk->is_checked);
                                                        $isProses = ($chk->status === 'proses');
                                                    @endphp
                                                    <div class="flex items-start justify-between gap-2 p-2 rounded-xl text-xs {{ $isDone ? 'bg-emerald-50/80 border border-emerald-200' : ($isProses ? 'bg-amber-50/80 border border-amber-200' : 'bg-slate-50 border border-slate-200') }}">
                                                        <div class="flex items-start gap-2 flex-1">
                                                            <span class="mt-0.5 flex-shrink-0">
                                                                @if($isDone)
                                                                    <i class="fa fa-check-circle text-emerald-600 font-bold"></i>
                                                                @elseif($isProses)
                                                                    <i class="fa fa-spinner fa-spin text-amber-600"></i>
                                                                @else
                                                                    <i class="fa fa-circle-o text-slate-400"></i>
                                                                @endif
                                                            </span>
                                                            <div>
                                                                <p class="font-medium {{ $isDone ? 'text-emerald-950 line-through text-slate-500' : ($isProses ? 'text-amber-950 font-semibold' : 'text-slate-800') }}">
                                                                    {{ $chk->kegiatan }}
                                                                </p>
                                                                @if($chk->penerima)
                                                                    <p class="text-[10px] text-slate-500 mt-0.5">
                                                                        <i class="fa fa-user mr-1 text-slate-400"></i>Tugas untuk: <strong class="text-slate-700">{{ $chk->penerima->nama }}</strong>
                                                                    </p>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        {{-- Badge Status --}}
                                                        <div class="flex-shrink-0 text-right">
                                                            @if($isDone)
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-600 text-white shadow-xs">
                                                                    Selesai
                                                                </span>
                                                                @if($chk->checked_at)
                                                                    <p class="text-[9px] text-emerald-700 mt-0.5 font-medium">{{ $chk->checked_at->format('d/m H:i') }}</p>
                                                                @endif
                                                            @elseif($isProses)
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500 text-white">
                                                                    Proses
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-200 text-slate-700">
                                                                    Menunggu
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-xs text-slate-400 italic">Tidak ada rincian checklist instruksi.</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Footer Summary --}}
                        <div class="pt-3 border-t border-emerald-200/80 flex items-center justify-between text-xs text-emerald-900 font-semibold">
                            <span><i class="fa fa-folder-open-o mr-1"></i> Total {{ $sub->briefings->count() }} Briefing Terbit</span>
                            <span class="text-emerald-700 font-bold">{{ $sub->selesai_checklists }} / {{ $sub->total_checklists }} Poin Instruksi Tuntas</span>
                        </div>
                    </div>
                @else
                    {{-- ── CARD MERAH: BELUM MEMBUAT BRIEFING ── --}}
                    <div class="bg-gradient-to-br from-rose-50/95 via-rose-50/50 to-white rounded-3xl p-6 border-2 border-rose-400 shadow-md hover:shadow-lg transition-all relative overflow-hidden flex flex-col justify-between">
                        
                        {{-- Top Badge --}}
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-rose-600 to-red-700 flex items-center justify-center text-white text-base font-extrabold shadow-md shadow-rose-600/30 flex-shrink-0">
                                    {{ strtoupper(substr($sub->nama ?? 'P', 0, 1)) }}
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 leading-tight">{{ $sub->nama }}</h3>
                                    <p class="text-xs font-semibold text-rose-800 mt-0.5">{{ $sub->jabatan ?: ($sub->alias_jabatan ?: 'Pejabat Struktural') }}</p>
                                    @if($sub->nohp)
                                        <p class="text-[11px] text-slate-500 mt-0.5"><i class="fa fa-phone mr-1"></i>{{ $sub->nohp }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-600 text-white shadow-sm flex-shrink-0 animate-pulse">
                                    <i class="fa fa-exclamation-triangle"></i> Belum Menerbitkan
                                </span>
                                @if($sub->has_warning_sent)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-xs" title="Sistem otomatis telah mengirimkan peringatan WhatsApp & In-App">
                                        <i class="fa fa-check-circle text-emerald-600"></i> Peringatan Sistem Terkirim
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Warning Box --}}
                        <div class="my-4 p-4 rounded-2xl bg-white/95 border border-rose-200 shadow-xs space-y-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 text-rose-700 font-bold text-xs uppercase tracking-wider">
                                    <i class="fa fa-bell-o"></i> Belum Ada Arahan Tugas
                                </div>
                                @if($sub->has_warning_sent)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                        <i class="fa fa-whatsapp text-emerald-600"></i> WA & Notif Terkirim
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-rose-950 leading-relaxed">
                                Pejabat ini <strong>belum membuat / menerbitkan briefing harian</strong> kepada jajaran staf bawahannya pada periode tanggal terpilih.
                            </p>

                            @if($sub->has_warning_sent)
                                <div class="pt-2 border-t border-rose-100 flex items-center justify-between gap-2 text-[11px] text-emerald-800 bg-emerald-50/70 -mx-4 -mb-4 px-4 py-2.5 rounded-b-2xl border-emerald-200/80 border-t">
                                    <span class="flex items-center gap-1.5 font-medium">
                                        <i class="fa fa-check-circle text-emerald-600 font-bold"></i> Peringatan WhatsApp & In-App otomatis telah dikirim oleh sistem
                                    </span>
                                    <span class="font-bold text-emerald-900 bg-white/90 px-2.5 py-0.5 rounded-full border border-emerald-200 text-[10px] flex-shrink-0 shadow-2xs">
                                        {{ $sub->warning_sent_at ? $sub->warning_sent_at->format('H:i') . ' WIB' : 'Hari ini' }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Action Buttons (WhatsApp Pengingat) --}}
                        <div class="pt-3 border-t border-rose-200/80 flex flex-col sm:flex-row items-center justify-between gap-3">
                            @if($sub->has_warning_sent)
                                <div class="flex items-center gap-1.5 text-xs text-emerald-700 font-semibold">
                                    <i class="fa fa-check-circle text-emerald-600 text-sm"></i>
                                    <span>Sudah diperingatkan sistem ({{ $sub->warning_sent_at ? $sub->warning_sent_at->format('H:i') . ' WIB' : 'Hari ini' }})</span>
                                </div>
                                @if($sub->nohp)
                                    @php
                                        $pesanWa = "Yth. Bapak/Ibu " . $sub->nama . " (" . ($sub->jabatan ?: $sub->alias_jabatan) . "),\n\nMohon untuk dapat segera menerbitkan arahan tugas dan Briefing harian untuk jajaran bawahan pada aplikasi E-Office untuk periode hari ini.\n\nTerima kasih.\n- Pimpinan Utama";
                                        $linkWa = "https://wa.me/" . preg_replace('/[^0-9]/', '', $sub->nohp) . "?text=" . urlencode($pesanWa);
                                    @endphp
                                    <a href="{{ $linkWa }}" target="_blank" 
                                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold rounded-xl transition shadow-xs"
                                        title="Kirim pengingat susulan secara manual via WhatsApp">
                                        <i class="fa fa-whatsapp text-emerald-600 text-sm"></i> Ingatkan Ulang via WhatsApp
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400 italic">No. WhatsApp belum diisi</span>
                                @endif
                            @else
                                <span class="text-xs text-rose-700 font-medium">
                                    <i class="fa fa-clock-o mr-1"></i> Segera ingatkan pejabat ini
                                </span>
                                @if($sub->nohp)
                                    @php
                                        $pesanWa = "Yth. Bapak/Ibu " . $sub->nama . " (" . ($sub->jabatan ?: $sub->alias_jabatan) . "),\n\nMohon untuk dapat menerbitkan arahan tugas dan Briefing harian untuk jajaran bawahan pada aplikasi E-Office untuk periode hari ini.\n\nTerima kasih.\n- Pimpinan Utama";
                                        $linkWa = "https://wa.me/" . preg_replace('/[^0-9]/', '', $sub->nohp) . "?text=" . urlencode($pesanWa);
                                    @endphp
                                    <a href="{{ $linkWa }}" target="_blank" 
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-md shadow-emerald-600/20">
                                        <i class="fa fa-whatsapp text-sm"></i> Ingatkan via WhatsApp
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400 italic">No. WhatsApp belum diisi</span>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            @empty
                <div class="col-span-2 p-12 bg-white rounded-3xl border border-slate-100 text-center">
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400 text-2xl">
                        <i class="fa fa-users"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-800">Tidak ada data pejabat struktural 1 tingkat.</h4>
                    <p class="text-xs text-slate-500 mt-1">Pastikan hierarki jabatan pejabat telah diatur pada menu Data Pejabat.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{--                     LIST BRIEFING TERAKHIR DIBUAT                      --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa fa-history text-indigo-600"></i> Daftar Briefing yang Terakhir Dibuat
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">10 briefing terbaru yang diterbitkan oleh para pejabat struktural di sistem E-Office.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100">
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Pembuat (Pejabat)</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tugas / Arahan</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Penerima Bawahan</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Realisasi Instruksi</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Waktu Terbit</th>
                        <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($recentBriefings as $rb)
                        @php
                            $pembuatPejabat = $rb->user?->pejabat;
                            $rbChks = $rb->checklists;
                            $rbCount = $rbChks->count();
                            $rbSelesai = $rbChks->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
                            $rbPercent = $rbCount > 0 ? round(($rbSelesai / $rbCount) * 100) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs flex-shrink-0">
                                        {{ strtoupper(substr($pembuatPejabat?->nama ?? $rb->user?->name ?? 'P', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900 leading-tight">{{ $pembuatPejabat?->nama ?? $rb->user?->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $pembuatPejabat?->jabatan ?: ($pembuatPejabat?->alias_jabatan ?: 'Pejabat') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <p class="font-bold text-slate-900">{{ $rb->judul }}</p>
                                <p class="text-xs text-slate-500 line-clamp-1 mt-0.5">{{ $rb->pesan ? Str::limit($rb->pesan, 70) : 'Instruksi terfokus untuk jajaran bawahan' }}</p>
                            </td>
                            <td class="p-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    <i class="fa fa-users text-xs"></i> {{ $rb->disposisis->count() }} Orang
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="w-36">
                                    <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1">
                                        <span>{{ $rbSelesai }} / {{ $rbCount }} Selesai</span>
                                        <span class="text-emerald-600">{{ $rbPercent }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $rbPercent }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-xs text-slate-500">
                                <p class="font-medium text-slate-700">{{ $rb->created_at->translatedFormat('d M Y') }}</p>
                                <p class="text-[11px] text-slate-400">{{ $rb->created_at->format('H:i') }} WIB ({{ $rb->created_at->diffForHumans() }})</p>
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ plugin_route('e-office.briefing.show', $rb->id) }}" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-indigo-50 hover:text-indigo-600 text-slate-700 text-xs font-bold rounded-xl transition">
                                    <i class="fa fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center mx-auto mb-2 text-slate-300">
                                    <i class="fa fa-folder-open-o text-xl"></i>
                                </div>
                                Belum ada data briefing yang diterbitkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @else
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{--             TAMPILAN NON-KADIS (PEJABAT 1 TINGKAT & STAF)              --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900">Briefing & Arahan Tugas</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    @if($isTingkatSatu)
                        Kelola arahan tugas dan instruksi harian untuk jajaran bawahan Anda.
                    @else
                        Daftar arahan tugas dan instruksi kegiatan yang ditugaskan kepada Anda.
                    @endif
                </p>
            </div>
            
            {{-- Tombol Buat Briefing Hanya untuk Pejabat 1 Tingkat --}}
            @if($isTingkatSatu)
                <a href="{{ plugin_route('e-office.briefing.create') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-2xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/25">
                    <i class="fa fa-plus"></i> Buat Briefing Baru
                </a>
            @endif
        </div>

        @if($isTingkatSatu)
            {{-- Tabs / Section untuk Pejabat 1 Tingkat --}}
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <i class="fa fa-bullhorn text-indigo-600"></i> Briefing yang Anda Berikan ke Bawahan
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white border-b border-slate-200/80">
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Judul Briefing</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Penerima Bawahan</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status Instruksi</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Waktu Pembuatan</th>
                                <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm bg-white">
                            @forelse($briefingsCreated as $bItem)
                                @php
                                    $bChks = $bItem->checklists;
                                    $bCount = $bChks->count();
                                    $bSelesai = $bChks->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
                                    $bProses = $bChks->where('status', 'proses')->count();
                                    $bPercent = $bCount > 0 ? round(($bSelesai / $bCount) * 100) : 0;
                                @endphp
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="p-4">
                                        <p class="font-bold text-slate-900">{{ $bItem->judul }}</p>
                                        <p class="text-xs text-slate-500 line-clamp-1 mt-0.5">{{ $bItem->pesan ? Str::limit($bItem->pesan, 70) : 'Instruksi terfokus untuk jajaran bawahan' }}</p>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            <i class="fa fa-users text-xs"></i> {{ $bItem->disposisis->count() }} Bawahan
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <div class="w-40">
                                            <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1">
                                                <span>{{ $bSelesai }} Selesai, {{ $bProses }} Proses</span>
                                                <span class="text-emerald-600">{{ $bPercent }}%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $bPercent }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-xs text-slate-500">
                                        <p class="font-medium text-slate-700">{{ $bItem->created_at->translatedFormat('d M Y, H:i') }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $bItem->created_at->diffForHumans() }}</p>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ plugin_route('e-office.briefing.show', $bItem->id) }}" 
                                                class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl transition">
                                                <i class="fa fa-eye"></i> Detail
                                            </a>
                                            <form action="{{ plugin_route('e-office.briefing.destroy', $bItem->id) }}" 
                                                method="POST" 
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus briefing ini?');"
                                                class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-xl transition">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-400">
                                        Belum ada briefing yang Anda buat. Silakan klik tombol <strong>+ Buat Briefing Baru</strong> di atas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Briefing yang Diterima (Untuk Semua Bawahan / Staf) --}}
        <div class="p-6">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 flex items-center gap-2">
                <i class="fa fa-inbox text-emerald-600"></i> Tugas / Briefing yang Anda Terima
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/70 border-b border-slate-100">
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Tugas / Arahan</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Dari Atasan</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Status Tugas</th>
                            <th class="p-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($briefingsReceived as $item)
                            @php
                                $brf = $item->briefing;
                                $statusColor = $brf->status == 'completed' ? 'emerald' : ($brf->status == 'in_progress' ? 'amber' : 'slate');
                                $statusText = $brf->status == 'completed' ? 'Selesai' : ($brf->status == 'in_progress' ? 'Diproses' : 'Menunggu');
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="p-4">
                                    <p class="font-bold text-slate-900">{{ $brf->judul }}</p>
                                    <p class="text-xs text-slate-500 mt-1"><i class="fa fa-clock-o mr-1"></i>{{ $brf->created_at->diffForHumans() }}</p>
                                </td>
                                <td class="p-4">
                                    <p class="font-semibold text-slate-900">{{ $item->pengirim->nama ?? $brf->user->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">{{ $item->pengirim->jabatan ?? $item->pengirim->alias_jabatan ?? '-' }}</p>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-{{ $statusColor }}-50 text-{{ $statusColor }}-700 border border-{{ $statusColor }}-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-{{ $statusColor }}-500"></span>
                                        {{ $statusText }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <a href="{{ plugin_route('e-office.briefing.show', $brf->id) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-xl transition">
                                        <i class="fa fa-eye"></i> Buka Tugas
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-slate-400">
                                    Belum ada tugas/briefing yang ditujukan kepada Anda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

@include('e-office::admin.briefing._modal_setting_jam')

</div>
@endsection