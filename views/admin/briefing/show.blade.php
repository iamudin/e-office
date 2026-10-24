@extends('e-office::layout.app', ['title' => 'Detail Briefing / Tugas'])

@section('content')
<div class="space-y-6">

    {{-- Detail Briefing (Header) --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-xl font-bold text-gray-900">{{ $briefing->judul }}</h2>
                    @php 
                        $statusColor = $briefing->status == 'completed' ? 'emerald' : ($briefing->status == 'in_progress' ? 'amber' : 'slate');
                        $statusText = $briefing->status == 'completed' ? 'Selesai' : ($briefing->status == 'in_progress' ? 'Diproses' : 'Menunggu');
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $statusColor }}-50 text-{{ $statusColor }}-700 border border-{{ $statusColor }}-200/60">
                        {{ $statusText }}
                    </span>
                </div>
                <p class="text-sm text-gray-500">
                    Oleh: <span class="font-medium text-gray-700">{{ $briefing->user->name ?? 'Pimpinan' }}</span> 
                    &bull; {{ $briefing->created_at->translatedFormat('d M Y, H:i') }}
                </p>
            </div>
            
            @if(($isKadis || $isPembuat) && $briefing->status !== 'completed')
            <div>
                <form action="{{ plugin_route('e-office.briefing.close', $briefing->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menutup/menyelesaikan tugas ini?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 transition-colors shadow-sm shadow-emerald-600/20">
                        <i class="fa fa-check-circle mr-1"></i> Tandai Selesai
                    </button>
                </form>
            </div>
            @endif
        </div>
        
        @if($briefing->pesan)
            <div class="p-6 text-gray-700 whitespace-pre-wrap text-sm leading-relaxed border-t border-gray-100">{{ $briefing->pesan }}</div>
        @endif
        
        @if(!$isKadis && isset($myDisposisi))
            @if($myDisposisi->pesan_diteruskan || $myDisposisi->pesan_khusus)
            <div class="px-6 py-4 bg-indigo-50/50 border-t border-indigo-100/60">
                <h4 class="text-xs font-bold text-indigo-700 uppercase tracking-wider mb-1.5"><i class="fa fa-commenting mr-1"></i> Catatan & Arahan Khusus untuk Anda</h4>
                <div class="text-indigo-950 whitespace-pre-wrap text-sm leading-relaxed">{{ $myDisposisi->pesan_diteruskan ?: $myDisposisi->pesan_khusus }}</div>
            </div>
            @endif

            @if($myDisposisi->file_lampiran)
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <i class="fa fa-paperclip"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Lampiran Tugas untuk Anda</p>
                        <p class="text-xs text-gray-500">File lampiran dari atasan</p>
                    </div>
                </div>
                <a href="{{ $myDisposisi->file_lampiran }}" target="_blank" class="px-4 py-2 text-sm font-medium text-indigo-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Unduh / Lihat File
                </a>
            </div>
            @endif
        @endif
    </div>

    {{-- Inisialisasi Data Checklist & Pemisahan Tugas Anda vs Tugas Bawahan --}}
    @php
        $myChecklists = $myChecklists ?? (
            (!$isKadis && !$isPembuat) 
                ? $briefing->checklists->filter(fn($c) => $c->penerima_id == $pejabat?->id || is_null($c->penerima_id))
                : collect()
        );

        $subordinateChecklists = $subordinateChecklists ?? (
            ($isKadis || $isPembuat) 
                ? $briefing->checklists 
                : ($pejabat ? $briefing->checklists->filter(function($c) use ($pejabat) {
                    $downlineIds = \App\Models\Plugins\EOffice\Pejabat::where('atasan_id', $pejabat->id)->pluck('id')->toArray();
                    return in_array($c->penerima_id, $downlineIds) && $c->penerima_id != $pejabat->id;
                }) : collect())
        );

        // Stats Tugas Anda
        $myTotal = $myChecklists->count();
        $mySelesai = $myChecklists->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
        $myProses = $myChecklists->where('status', 'proses')->count();
        $myPending = $myTotal - $mySelesai - $myProses;
        $myPercent = $myTotal > 0 ? round(($mySelesai / $myTotal) * 100) : 0;

        // Stats Monitoring Bawahan
        $subTotal = $subordinateChecklists->count();
        $subSelesai = $subordinateChecklists->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
        $subProses = $subordinateChecklists->where('status', 'proses')->count();
        $subPending = $subTotal - $subSelesai - $subProses;
        $subPercent = $subTotal > 0 ? round(($subSelesai / $subTotal) * 100) : 0;
    @endphp

    {{-- 1. Card Khusus: Ceklis & Realisasi Instruksi Tugas Anda --}}
    @if($myTotal > 0)
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden p-6 sm:p-8 mb-6 transition-all" id="myChecklistSection" x-data="{ sectionOpen: true }">
        {{-- Section Header Tugas Anda --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 select-none"
             :class="sectionOpen ? 'mb-6 pb-6 border-b border-slate-100' : ''">
            <div class="cursor-pointer group flex items-center gap-3" @click="sectionOpen = !sectionOpen">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg border border-indigo-100 shadow-2xs group-hover:bg-indigo-100 transition">
                    <i class="fa fa-tasks"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xl font-bold text-slate-900 group-hover:text-indigo-600 transition flex items-center gap-2">
                            Ceklis & Realisasi Instruksi Tugas Anda
                        </h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            <i class="fa fa-user mr-1"></i>Tugas Anda
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Konfirmasi status pengerjaan setiap poin instruksi tugas untuk Anda secara mandiri sebagai <strong class="text-amber-600">"Proses"</strong> atau <strong class="text-emerald-600">"Selesai"</strong>.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <i class="fa fa-check-circle"></i> <span id="my-stat-selesai">{{ $mySelesai }}</span> Selesai
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    <i class="fa fa-spinner"></i> <span id="my-stat-proses">{{ $myProses }}</span> Proses
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    <span id="my-stat-pending">{{ $myPending }}</span> Menunggu
                </span>
                <button type="button" 
                        @click="sectionOpen = !sectionOpen" 
                        class="w-8 h-8 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center transition shadow-2xs cursor-pointer"
                        :title="sectionOpen ? 'Sembunyikan Bagian Ini' : 'Tampilkan Bagian Ini'">
                    <i class="fa fa-chevron-down text-xs transition-transform duration-200" :class="sectionOpen ? 'rotate-180' : ''"></i>
                </button>
            </div>
        </div>

        <div x-show="sectionOpen" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 -translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             x-transition:leave="transition ease-in duration-150" 
             x-transition:leave-start="opacity-100 translate-y-0" 
             x-transition:leave-end="opacity-0 -translate-y-2"
             x-cloak>
            {{-- Progress Bar Tugas Anda --}}
            <div class="mb-6 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-2">
                    <span class="flex items-center gap-1.5 text-indigo-950">
                        <i class="fa fa-line-chart text-indigo-600"></i> Tingkat Ketercapaian Tugas Anda
                    </span>
                    <span class="text-indigo-600 text-sm font-extrabold" id="my-stat-percent">{{ $myPercent }}%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden flex">
                    <div id="my-progressbar-selesai" class="bg-emerald-500 h-3 transition-all duration-300" style="width: {{ $myPercent }}%" title="Selesai"></div>
                    <div id="my-progressbar-proses" class="bg-amber-400 h-3 transition-all duration-300" style="width: {{ $myTotal > 0 ? round(($myProses / $myTotal) * 100) : 0 }}%" title="Proses"></div>
                </div>
            </div>

            {{-- List Items Tugas Anda --}}
            <div class="rounded-2xl border border-indigo-200 bg-indigo-50/20 p-4 sm:p-5" x-data="{ expanded: true }">
                <div class="flex items-center justify-between gap-2 cursor-pointer select-none group" 
                     @click="expanded = !expanded" 
                     :class="expanded ? 'mb-3 pb-2 border-b border-indigo-100' : ''">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold shadow-2xs">
                            {{ strtoupper(substr($pejabat?->nama ?? 'A', 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-indigo-950 flex items-center gap-1.5 group-hover:text-indigo-600 transition-colors">
                                {{ $pejabat?->nama ?? 'Anda' }}
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-600 text-white">Tugas Anda</span>
                            </h4>
                            <p class="text-[11px] text-slate-500">{{ $pejabat?->jabatan ?: ($pejabat?->alias_jabatan ?: 'Pejabat') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="my-person-summary" class="text-xs font-bold text-indigo-800 bg-white px-2.5 py-1 rounded-lg border border-indigo-200 shadow-2xs">
                            {{ $mySelesai }}/{{ $myTotal }} Selesai ({{ $myPercent }}%)
                        </span>
                        <div class="w-7 h-7 rounded-lg bg-white text-indigo-700 hover:bg-indigo-50 flex items-center justify-center border border-indigo-200 shadow-2xs transition">
                            <i class="fa fa-chevron-down text-xs transition-transform duration-200" :class="expanded ? 'rotate-180' : ''"></i>
                        </div>
                    </div>
                </div>

                <div x-show="expanded" 
                     x-transition:enter="transition ease-out duration-200" 
                     x-transition:enter-start="opacity-0 -translate-y-2" 
                     x-transition:enter-end="opacity-100 translate-y-0" 
                     x-transition:leave="transition ease-in duration-150" 
                     x-transition:leave-start="opacity-100 translate-y-0" 
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     x-cloak 
                     class="space-y-3">
                    @foreach($myChecklists as $chk)
                        @include('e-office::admin.briefing._checklist_item', ['chk' => $chk, 'pejabat' => $pejabat, 'briefing' => $briefing, 'isKadis' => $isKadis, 'isPembuat' => $isPembuat])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 2. Card Khusus: Monitoring Realisasi Instruksi ke Bawahan --}}
    @if($subTotal > 0)
    <div class="bg-white rounded-3xl shadow-sm border border-teal-100 overflow-hidden p-6 sm:p-8 mb-6 transition-all" id="subordinateChecklistSection" x-data="{ sectionOpen: true }">
        {{-- Section Header Monitoring Bawahan --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 select-none"
             :class="sectionOpen ? 'mb-6 pb-6 border-b border-teal-100/70' : ''">
            <div class="cursor-pointer group flex items-center gap-3" @click="sectionOpen = !sectionOpen">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg border border-teal-100 shadow-2xs group-hover:bg-teal-100 transition">
                    <i class="fa fa-sitemap"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xl font-bold text-slate-900 group-hover:text-teal-700 transition flex items-center gap-2">
                            Monitoring Realisasi Instruksi Bawahan
                        </h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-teal-50 text-teal-700 border border-teal-200">
                            <i class="fa fa-users mr-1"></i>Bawahan
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pantau perkembangan dan realisasi setiap poin instruksi tugas yang Anda teruskan kepada bawahan.
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <i class="fa fa-check-circle"></i> <span id="sub-stat-selesai">{{ $subSelesai }}</span> Selesai
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    <i class="fa fa-spinner"></i> <span id="sub-stat-proses">{{ $subProses }}</span> Proses
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    <span id="sub-stat-pending">{{ $subPending }}</span> Menunggu
                </span>
                @if(isset($nestedSubordinates) && $nestedSubordinates->isNotEmpty())
                <button type="button" 
                        onclick="openSubordinateModal('all')"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-teal-600 hover:bg-teal-700 text-white shadow-xs transition cursor-pointer">
                    <i class="fa fa-sitemap"></i> Bawahan Lanjutan ({{ $nestedSubordinates->count() }}) [Modal]
                </button>
                @endif
                <button type="button" 
                        @click="sectionOpen = !sectionOpen" 
                        class="w-8 h-8 rounded-xl bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 flex items-center justify-center transition shadow-2xs cursor-pointer"
                        :title="sectionOpen ? 'Sembunyikan Bagian Ini' : 'Tampilkan Bagian Ini'">
                    <i class="fa fa-chevron-down text-xs transition-transform duration-200" :class="sectionOpen ? 'rotate-180' : ''"></i>
                </button>
            </div>
        </div>

        <div x-show="sectionOpen" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 -translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             x-transition:leave="transition ease-in duration-150" 
             x-transition:leave-start="opacity-100 translate-y-0" 
             x-transition:leave-end="opacity-0 -translate-y-2"
             x-cloak>
            {{-- Progress Bar Monitoring Bawahan --}}
            <div class="mb-6 bg-teal-50/50 p-4 rounded-2xl border border-teal-100/80">
                <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-2">
                    <span class="flex items-center gap-1.5 text-teal-900">
                        <i class="fa fa-bar-chart text-teal-600"></i> Tingkat Ketercapaian Tugas Bawahan
                    </span>
                    <span class="text-teal-700 text-sm font-extrabold" id="sub-stat-percent">{{ $subPercent }}%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden flex">
                    <div id="sub-progressbar-selesai" class="bg-teal-500 h-3 transition-all duration-300" style="width: {{ $subPercent }}%" title="Selesai"></div>
                    <div id="sub-progressbar-proses" class="bg-amber-400 h-3 transition-all duration-300" style="width: {{ $subTotal > 0 ? round(($subProses / $subTotal) * 100) : 0 }}%" title="Proses"></div>
                </div>
            </div>

        {{-- List Items Bawahan Dikelompokkan Per Bawahan --}}
        @php
            $groupedSub = $subordinateChecklists->groupBy('penerima_id');
        @endphp

        @if($groupedSub->count() > 1)
        <div class="flex items-center justify-between gap-2 mb-3 px-1">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                Daftar Grup Bawahan ({{ $groupedSub->count() }} Orang)
            </span>
            <div class="flex items-center gap-2" x-data="{ allExpanded: true }">
                <button type="button" 
                        @click="allExpanded = !allExpanded; $dispatch('toggle-all-subgroups', allExpanded)" 
                        class="text-xs text-teal-700 hover:text-teal-900 bg-teal-50 hover:bg-teal-100 border border-teal-200 px-2.5 py-1 rounded-lg transition font-medium inline-flex items-center gap-1.5 cursor-pointer">
                    <i class="fa text-[11px]" :class="allExpanded ? 'fa-angle-double-up' : 'fa-angle-double-down'"></i>
                    <span x-text="allExpanded ? 'Tutup Semua Grup' : 'Buka Semua Grup'"></span>
                </button>
            </div>
        </div>
        @endif

        <div class="space-y-6">
            @foreach($groupedSub as $penerimaId => $chks)
                @php
                    $penerimaPejabat = $chks->first()->penerima;
                    $pTotal = $chks->count();
                    $pSelesai = $chks->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
                    $pPercent = $pTotal > 0 ? round(($pSelesai / $pTotal) * 100) : 0;
                @endphp

                <div class="rounded-2xl border border-teal-200 bg-teal-50/20 p-4 sm:p-5 transition-all"
                     x-data="{ expanded: true }"
                     @toggle-all-subgroups.window="expanded = $event.detail">
                    <div class="flex items-center justify-between gap-2 cursor-pointer select-none group"
                         @click="expanded = !expanded"
                         :class="expanded ? 'mb-3 pb-2 border-b border-teal-100' : ''">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-sm font-bold shadow-2xs">
                                {{ strtoupper(substr($penerimaPejabat?->nama ?? 'B', 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900 flex items-center gap-1.5 group-hover:text-teal-700 transition-colors">
                                    {{ $penerimaPejabat?->nama ?? 'Bawahan' }}
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-teal-600 text-white">Bawahan Anda</span>
                                </h4>
                                <p class="text-[11px] text-slate-500">{{ $penerimaPejabat?->jabatan ?: ($penerimaPejabat?->alias_jabatan ?: 'Staf') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span id="person-summary-{{ $penerimaId }}" class="text-xs font-bold text-teal-800 bg-white px-2.5 py-1 rounded-lg border border-teal-200 shadow-2xs">
                                {{ $pSelesai }}/{{ $pTotal }} Selesai ({{ $pPercent }}%)
                            </span>
                            <div class="w-7 h-7 rounded-lg bg-white text-teal-700 hover:bg-teal-50 flex items-center justify-center border border-teal-200 shadow-2xs transition">
                                <i class="fa fa-chevron-down text-xs transition-transform duration-200" :class="expanded ? 'rotate-180' : ''"></i>
                            </div>
                        </div>
                    </div>

                    <div x-show="expanded" 
                         x-transition:enter="transition ease-out duration-200" 
                         x-transition:enter-start="opacity-0 -translate-y-2" 
                         x-transition:enter-end="opacity-100 translate-y-0" 
                         x-transition:leave="transition ease-in duration-150" 
                         x-transition:leave-start="opacity-100 translate-y-0" 
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         x-cloak>
                        <div class="space-y-3">
                            @foreach($chks as $chk)
                                @include('e-office::admin.briefing._checklist_item', ['chk' => $chk, 'pejabat' => $pejabat, 'briefing' => $briefing, 'isKadis' => $isKadis, 'isPembuat' => $isPembuat])
                            @endforeach
                        </div>

                        {{-- Banner Pembuka Modal jika Bawahan Ini Telah Meneruskan ke Bawahannya Lagi --}}
                        @php
                            $subDownlines = isset($nestedSubordinates) ? $nestedSubordinates->filter(function($n) use ($penerimaId) {
                                return in_array($penerimaId, collect($n['pejabat_chain'])->pluck('id')->toArray());
                            }) : collect();
                        @endphp
                        @if($subDownlines->isNotEmpty())
                            <div class="mt-4 pt-3 border-t border-teal-200/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white/80 p-3.5 rounded-xl border border-teal-200 shadow-2xs">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-teal-100 text-teal-700 flex items-center justify-center text-xs font-bold shadow-2xs">
                                        <i class="fa fa-sitemap"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="text-xs font-bold text-slate-900">
                                                Diteruskan ke Bawahan Lanjutan
                                            </p>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-teal-100 text-teal-800 border border-teal-200">
                                                {{ $subDownlines->count() }} Penerima
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500">
                                            {{ $penerimaPejabat?->nama }} telah mendelegasikan tugas ke bawahannya hingga penerima terakhir.
                                        </p>
                                    </div>
                                </div>
                                <button type="button" 
                                        onclick="openSubordinateModal('{{ $penerimaId }}')"
                                        class="px-3.5 py-2 rounded-xl text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-300 shadow-2xs hover:shadow-xs transition inline-flex items-center justify-center gap-1.5 cursor-pointer whitespace-nowrap">
                                    <i class="fa fa-eye text-teal-600"></i> Buka Instruksi Bawahannya (Modal)
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        </div>
    </div>
    @endif

    {{-- Responses & Disposisi Tree --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        {{-- List of Responses --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">Riwayat Disposisi & Balasan</h3>
            </div>
            <div class="p-0 max-h-[600px] overflow-y-auto">
                <div class="divide-y divide-gray-100">
                    @php
                        $visibleDisposisiNodes = ($isKadis || $isPembuat) 
                            ? $disposisiTreeFlat 
                            : $disposisiTreeFlat->filter(fn($n) => $n['is_me'] || $n['is_direct_sub'] || $n['is_nested_sub']);
                        
                        $minDepth = $visibleDisposisiNodes->min('depth') ?? 0;
                    @endphp

                    @foreach($visibleDisposisiNodes as $node)
                        @php
                            $disp = $node['disposisi'];
                            $relDepth = max(0, $node['depth'] - $minDepth);
                            $indentPx = min($relDepth * 22, 66);
                        @endphp
                        
                        <div class="relative p-4 sm:p-5 hover:bg-slate-50/80 transition-colors"
                             style="margin-left: {{ $indentPx }}px;">
                             
                            @if($relDepth > 0)
                                {{-- Garis konektor siku pohon hierarki --}}
                                <div class="absolute -left-3 top-7 w-3 h-5 border-l-2 border-b-2 border-indigo-300 rounded-bl-md pointer-events-none"></div>
                            @endif

                            {{-- Header Hirarki: Pengirim -> Penerima --}}
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2 pb-1.5 border-b border-slate-100">
                                <div class="flex flex-wrap items-center gap-1.5 text-[11px]">
                                    @if($relDepth > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 border border-indigo-200/80 text-indigo-700 font-semibold">
                                            <i class="fa fa-level-down fa-rotate-180"></i> Di bawah: <strong>{{ $node['pengirim']?->nama ?? 'Pimpinan' }}</strong>
                                        </span>
                                        <i class="fa fa-arrow-right text-[10px] text-slate-400"></i>
                                    @endif
                                    
                                    <span class="font-bold text-slate-800">
                                        {{ $node['penerima']?->nama ?? 'Tanpa Nama' }}
                                    </span>

                                    {{-- Badges Status Hirarki --}}
                                    @if($node['is_me'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-600 text-white shadow-2xs">Tugas Anda</span>
                                    @elseif($node['is_direct_sub'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-teal-600 text-white shadow-2xs">Bawahan Anda</span>
                                    @elseif($node['is_nested_sub'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-purple-100 text-purple-800 border border-purple-200">Bawahan Lanjutan</span>
                                    @endif

                                    @if($node['is_last_receiver'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200">
                                            <i class="fa fa-flag-checkered mr-0.5"></i> Penerima Terakhir
                                        </span>
                                    @endif
                                </div>

                                <div class="text-right flex items-center gap-2">
                                    <span class="text-[11px] text-slate-400">{{ $disp->created_at->diffForHumans() }}</span>
                                    @if($disp->dibaca_pada)
                                        <span class="text-[10px] text-teal-700 font-bold bg-teal-50 border border-teal-200 px-1.5 py-0.5 rounded"><i class="fa fa-check-double mr-0.5"></i>Dibaca</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Profil Singkat Penerima --}}
                            <div class="flex items-start justify-between gap-3 mb-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-500 to-teal-500 flex items-center justify-center text-white text-xs font-bold shadow-2xs">
                                        {{ strtoupper(substr($node['penerima']?->nama ?? 'U', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-900">{{ $node['penerima']?->nama ?? 'Tanpa Nama' }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $node['penerima']?->jabatan ?: ($node['penerima']?->alias_jabatan ?: 'Pejabat') }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Ringkasan Poin Instruksi Tugas yang Diberikan ke Penerima Ini --}}
                            @if($node['stats']['total'] > 0)
                                <div class="mb-3 p-2.5 rounded-xl bg-indigo-50/60 border border-indigo-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-md bg-indigo-600 text-white flex items-center justify-center text-[10px]">
                                            <i class="fa fa-tasks"></i>
                                        </span>
                                        <span class="font-bold text-slate-800">
                                            {{ $node['stats']['total'] }} Poin Instruksi
                                        </span>
                                        <span class="text-indigo-700 font-semibold">
                                            &bull; {{ $node['stats']['selesai'] }}/{{ $node['stats']['total'] }} Selesai ({{ $node['stats']['percent'] }}%)
                                        </span>
                                    </div>
                                    @if($node['is_nested_sub'])
                                        <button type="button" 
                                                onclick="openSubordinateModal('{{ $node['penerima_id'] }}')" 
                                                class="text-xs font-bold text-teal-700 hover:text-teal-900 inline-flex items-center gap-1 self-start sm:self-auto cursor-pointer">
                                            <i class="fa fa-eye"></i> Lihat Instruksi di Modal &rarr;
                                        </button>
                                    @endif
                                </div>
                            @endif

                            {{-- Isi Pesan Disposisi & Khusus --}}
                            <div class="space-y-2">
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 text-xs text-slate-700">
                                    @if($disp->pesan_diteruskan)
                                        <div class="mb-2">
                                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Instruksi Khusus</span>
                                            <div class="whitespace-pre-wrap">{{ $disp->pesan_diteruskan }}</div>
                                        </div>
                                    @endif
                                    
                                    @if($disp->pesan_khusus)
                                        <div class="mb-2 p-2 bg-indigo-50/70 border border-indigo-100 rounded-lg text-indigo-950">
                                            <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider block mb-1">Pesan Khusus</span>
                                            <div class="whitespace-pre-wrap">{{ $disp->pesan_khusus }}</div>
                                        </div>
                                    @endif
                                    
                                    @if(!$disp->pesan_diteruskan && !$disp->pesan_khusus)
                                        <span class="text-slate-400 italic">Tidak ada catatan instruksi tambahan.</span>
                                    @endif
                                    
                                    @if($disp->file_lampiran)
                                        <div class="mt-2.5">
                                            <a href="{{ $disp->file_lampiran }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg border border-indigo-200 transition">
                                                <i class="fa fa-paperclip"></i> Unduh File Khusus
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                {{-- Balasan / Laporan Progres --}}
                                @if($disp->dibalas_pada)
                                    @php
                                        $canViewResponse = $isKadis || $disp->pengirim_id == $pejabat?->id || $disp->penerima_id == $pejabat?->id || $disp->is_validated_by_atasan || $node['is_nested_sub'];
                                    @endphp
                                    
                                    @if($canViewResponse)
                                        <div class="bg-indigo-50/40 p-3 rounded-xl border border-indigo-100 text-xs text-slate-800 mt-2">
                                            <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider block mb-2"><i class="fa fa-history mr-1"></i> Riwayat Progress / Balasan</span>
                                            
                                            @php
                                                $history = $disp->balasan_history ? json_decode($disp->balasan_history, true) : null;
                                            @endphp
                                            
                                            @if($history && is_array($history) && count($history) > 0)
                                                <div class="space-y-2">
                                                    @foreach($history as $item)
                                                        <div class="bg-white p-2.5 rounded-lg border border-indigo-100/60 shadow-2xs">
                                                            <div class="flex justify-between items-center mb-1 border-b border-slate-100 pb-1">
                                                                <span class="text-[10px] font-bold text-slate-700"><i class="fa fa-user text-indigo-400 mr-1"></i> {{ $item['nama'] ?? 'Bawahan' }}</span>
                                                                <span class="text-[9px] font-medium text-slate-500 bg-slate-50 px-1.5 py-0.5 rounded">{{ $item['waktu'] ?? '' }}</span>
                                                            </div>
                                                            <p class="whitespace-pre-wrap text-slate-700 leading-relaxed">{{ $item['pesan'] ?? '' }}</p>
                                                            
                                                            @if(!empty($item['file_url']))
                                                                <div class="mt-1.5 pt-1.5 border-t border-slate-50">
                                                                    <a href="{{ $item['file_url'] }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-700 bg-indigo-50 px-2 py-1 rounded transition">
                                                                        <i class="fa fa-paperclip"></i> Lihat Lampiran
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="whitespace-pre-wrap text-slate-700">{{ $disp->balasan_teks ?: 'Tidak ada teks balasan.' }}</p>
                                                
                                                @if($disp->balasan_file)
                                                    <div class="mt-2 pt-2 border-t border-indigo-100/50">
                                                        <a href="{{ $disp->balasan_file }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                                            <i class="fa fa-paperclip"></i> Lihat Lampiran Balasan
                                                        </a>
                                                    </div>
                                                @endif
                                            @endif
                                            
                                            @if(!$isKadis && $disp->pengirim_id == $pejabat?->id && !$disp->is_validated_by_atasan)
                                                <div class="mt-3 pt-2 border-t border-indigo-200/60">
                                                    <form action="{{ plugin_route('e-office.briefing.validasi', $disp->id) }}" method="POST" onsubmit="return confirm('Validasi dan teruskan respon ini ke pimpinan/atasan?')">
                                                        @csrf
                                                        <button type="submit" class="text-xs font-semibold text-emerald-800 hover:text-emerald-900 bg-emerald-100 px-3 py-1.5 rounded-lg border border-emerald-300 transition cursor-pointer">
                                                            <i class="fa fa-check-circle mr-1"></i> Validasi & Teruskan ke Atasan
                                                        </button>
                                                    </form>
                                                </div>
                                            @elseif($disp->is_validated_by_atasan)
                                                <div class="mt-2 pt-1.5 border-t border-indigo-200/60">
                                                    <span class="text-xs font-medium text-emerald-600"><i class="fa fa-check-circle mr-1"></i> Telah divalidasi dan diteruskan ke Atasan</span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="bg-slate-100/60 p-2.5 rounded-xl border border-slate-200 border-dashed text-xs text-slate-500 italic flex items-center gap-2 mt-2">
                                            <i class="fa fa-lock text-slate-400"></i> Respon sedang direview oleh atasan langsung.
                                        </div>
                                    @endif
                                @endif

                                @if($disp->feedback_kadis)
                                    <div class="bg-amber-50 p-2.5 rounded-xl border border-amber-100 text-xs text-slate-800 mt-2">
                                        <span class="text-[10px] font-bold text-amber-600 uppercase tracking-wider block mb-1"><i class="fa fa-commenting-o mr-1"></i> Feedback Pimpinan</span>
                                        <p class="whitespace-pre-wrap">{{ $disp->feedback_kadis }}</p>
                                    </div>
                                @endif
                                
                                @if($isKadis && $disp->dibalas_pada && !$disp->feedback_kadis && $briefing->status !== 'completed')
                                    <div x-data="{ open: false }" class="mt-2">
                                        <button @click="open = !open" class="text-xs font-medium text-amber-600 hover:text-amber-700 cursor-pointer"><i class="fa fa-reply mr-1"></i> Beri Feedback</button>
                                        <div x-show="open" x-cloak class="mt-2">
                                            <form action="{{ plugin_route('e-office.briefing.feedback', $disp->id) }}" method="POST" class="flex gap-2">
                                                @csrf
                                                <input type="text" name="feedback_kadis" required placeholder="Tulis feedback..." class="flex-1 text-xs rounded-lg border-slate-200 py-1.5 px-3">
                                                <button type="submit" class="px-3 py-1.5 bg-amber-600 text-white text-xs font-medium rounded-lg hover:bg-amber-700">Kirim</button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Form Aksi (Teruskan & Balas) --}}
        @if(!$isKadis && $myDisposisi && $briefing->status !== 'completed')
        <div class="space-y-6">
            
            {{-- Form Balas --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Balas Tugas Ini</h3>
                <form action="{{ plugin_route('e-office.briefing.balas', $briefing->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pesan Balasan</label>
                        <textarea name="balasan_teks" rows="3" required class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 border resize-none" placeholder="Tulis progres atau laporan Anda..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lampiran Laporan (Opsional)</label>
                        <input type="file" name="balasan_file" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-200 rounded-lg">
                    </div>
                    <button type="submit" class="w-full px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 shadow-sm shadow-indigo-600/20">
                        <i class="fa fa-paper-plane mr-1"></i> Kirim Balasan Laporan
                    </button>
                </form>
            </div>

            {{-- Form Teruskan --}}
            @if($semuaPejabat->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6" x-data="{ show: false }">
                <div class="flex items-center justify-between cursor-pointer" @click="show = !show">
                    <h3 class="text-lg font-semibold text-gray-900">Teruskan Tugas ke Bawahan</h3>
                    <i class="fa fa-chevron-down text-gray-400 transition-transform" :class="show ? 'rotate-180' : ''"></i>
                </div>
                
                <div x-show="show" x-cloak class="mt-4 pt-4 border-t border-gray-100">
                    <form x-data="{
                        penerima: [],
                        instruksiKhusus: {},
                        togglePenerima(id) {
                            if (this.penerima.includes(id)) {
                                this.penerima = this.penerima.filter(i => i !== id);
                            } else {
                                this.penerima.push(id);
                                if (!this.instruksiKhusus[id] || this.instruksiKhusus[id].length === 0) {
                                    this.instruksiKhusus[id] = [''];
                                }
                            }
                        },
                        addInstruksiKhusus(pejabatId) {
                            if (!this.instruksiKhusus[pejabatId]) {
                                this.instruksiKhusus[pejabatId] = [];
                            }
                            this.instruksiKhusus[pejabatId].push('');
                        },
                        removeInstruksiKhusus(pejabatId, index) {
                            if (this.instruksiKhusus[pejabatId] && this.instruksiKhusus[pejabatId].length > 1) {
                                this.instruksiKhusus[pejabatId].splice(index, 1);
                            } else if (this.instruksiKhusus[pejabatId]) {
                                this.instruksiKhusus[pejabatId][0] = '';
                            }
                        },
                        selectAll: false,
                        toggleAll() {
                            const checkboxes = $el.querySelectorAll('.penerima-checkbox');
                            checkboxes.forEach(cb => {
                                cb.checked = this.selectAll;
                                const val = cb.value;
                                if (this.selectAll) {
                                    if (!this.penerima.includes(val)) {
                                        this.penerima.push(val);
                                    }
                                    if (!this.instruksiKhusus[val] || this.instruksiKhusus[val].length === 0) {
                                        this.instruksiKhusus[val] = [''];
                                    }
                                } else {
                                    this.penerima = [];
                                }
                            });
                        }
                    }" action="{{ plugin_route('e-office.briefing.teruskan', $briefing->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        {{-- Arahan Umum Penerusan --}}
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Arahan / Catatan Umum Penerusan (Opsional)</label>
                            <textarea name="pesan_diteruskan" rows="2" class="w-full rounded-xl border border-gray-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs py-2 px-3 outline-none resize-none" placeholder="Instruksi umum yang berlaku untuk semua bawahan yang dipilih..."></textarea>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-sm font-bold text-gray-900"><i class="fa fa-users text-indigo-500 mr-2"></i> Delegasi Spesifik Bawahan</h4>
                                <label class="flex items-center gap-1.5 text-xs text-indigo-600 font-medium cursor-pointer hover:text-indigo-800">
                                    <input type="checkbox" x-model="selectAll" @change="toggleAll()" class="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 border-gray-300">
                                    Pilih Semua Bawahan
                                </label>
                            </div>
                            
                            @if($semuaPejabat->count() > 0)
                                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                                    @foreach($semuaPejabat as $pj)
                                        <div class="bg-gray-50 border border-gray-200 rounded-xl overflow-hidden transition-all hover:border-indigo-300">
                                            <div class="bg-white p-3 border-b border-gray-200 flex items-start gap-3">
                                                <input type="checkbox" name="penerima_ids[]" value="{{ $pj->id }}" @change="togglePenerima('{{ $pj->id }}')" class="penerima-checkbox mt-1 text-indigo-600 rounded focus:ring-indigo-500 w-5 h-5 border-gray-300 cursor-pointer">
                                                <div>
                                                    <h5 class="font-bold text-gray-900 leading-tight text-sm">{{ $pj->nama ?: 'Tanpa Nama' }}</h5>
                                                    <p class="text-xs text-gray-500 mt-0.5">{{ $pj->jabatan ?? $pj->alias_jabatan }}</p>
                                                </div>
                                            </div>
                                            
                                            <div x-show="penerima.includes('{{ $pj->id }}')" class="p-4 space-y-4 bg-white" style="display: none;">
                                                
                                                {{-- LIST INPUT DINAMIS POIN INSTRUKSI KHUSUS PER BAWAHAN --}}
                                                <div class="bg-indigo-50/40 p-4 rounded-2xl border border-indigo-100">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <label class="text-xs font-bold text-indigo-950 flex items-center gap-1.5">
                                                            <i class="fa fa-list-ol text-indigo-600"></i> Poin-poin Instruksi Kegiatan
                                                        </label>
                                                        <button type="button" @click="addInstruksiKhusus('{{ $pj->id }}')" 
                                                            class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-xs transition-colors flex items-center gap-1">
                                                            <i class="fa fa-plus"></i> Tambah Poin
                                                        </button>
                                                    </div>
                                                    <p class="text-[11px] text-slate-500 mb-3">
                                                        Tuliskan instruksi yang wajib dikonfirmasi (<strong>Proses</strong> atau <strong>Selesai</strong>) oleh {{ $pj->nama }}.
                                                    </p>

                                                    <div class="space-y-2.5">
                                                        <template x-for="(item, idx) in (instruksiKhusus['{{ $pj->id }}'] || [''])" :key="idx">
                                                            <div class="flex items-center gap-2">
                                                                <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0" x-text="idx + 1"></span>
                                                                <input type="text" 
                                                                    :name="'instruksi_khusus[{{ $pj->id }}][]'" 
                                                                    x-model="instruksiKhusus['{{ $pj->id }}'][idx]" 
                                                                    placeholder="Contoh: Tindak lanjuti berkas ini dan laporkan hasilnya..." 
                                                                    class="flex-1 rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs py-2 px-3 outline-none bg-white">
                                                                <button type="button" @click="removeInstruksiKhusus('{{ $pj->id }}', idx)" 
                                                                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors flex-shrink-0" 
                                                                    title="Hapus baris instruksi">
                                                                    <i class="fa fa-trash-o text-sm"></i>
                                                                </button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                {{-- Tupoksi --}}
                                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                                                    <label class="block text-xs font-bold text-indigo-900 mb-2"><i class="fa fa-check-square-o mr-1"></i> Tugas Pokok & Fungsi</label>
                                                    @if($pj->tupoksis && $pj->tupoksis->count() > 0)
                                                        <div class="space-y-1.5 max-h-32 overflow-y-auto pr-1">
                                                            @foreach($pj->tupoksis as $tp)
                                                                <label class="flex items-start gap-2 text-xs text-gray-700 cursor-pointer p-1.5 hover:bg-gray-50 rounded">
                                                                    <input type="checkbox" name="tupoksi_khusus[{{ $pj->id }}][]" value="{{ $tp->tugas_fungsi }}" class="mt-0.5 text-indigo-600 rounded focus:ring-indigo-500 w-3.5 h-3.5 border-gray-300">
                                                                    <span>{{ $tp->tugas_fungsi }}</span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        <p class="text-[10px] text-gray-500 italic">Tidak ada data tupoksi.</p>
                                                    @endif
                                                </div>

                                                {{-- Pesan Khusus --}}
                                                <div>
                                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Pesan Khusus (Opsional)</label>
                                                    <textarea name="pesan_khusus[{{ $pj->id }}]" rows="2" class="w-full rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs py-1.5 px-2 border outline-none resize-none" placeholder="Instruksi spesifik..."></textarea>
                                                </div>
                                                
                                                {{-- File Khusus --}}
                                                <div>
                                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">File Tambahan Khusus (Opsional)</label>
                                                    <input type="file" name="file_khusus[{{ $pj->id }}]" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 transition-all border border-gray-200 rounded-lg bg-white p-1">
                                                </div>
                                                
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 text-center">
                                    <p class="text-rose-800 text-sm font-medium">Anda belum memiliki bawahan langsung.</p>
                                </div>
                            @endif
                        </div>

                        <button type="submit" class="w-full px-4 py-2.5 bg-slate-800 text-white text-sm font-medium rounded-xl hover:bg-slate-900 shadow-sm mt-4">
                            <i class="fa fa-share mr-1"></i> Teruskan Tugas
                        </button>
                    </form>
                </div>
            </div>
            @endif

        </div>
        @endif

    </div>
</div>

{{-- MODAL KHUSUS: POHON INSTRUKSI BAWAHAN LANJUTAN (SAMPAI PENERIMA TERAKHIR) --}}
<div id="modalSubordinateInstructions" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden my-8 max-h-[90vh] flex flex-col">
        {{-- Header Modal --}}
        <div class="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-teal-700 via-indigo-700 to-indigo-800 text-white flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center text-white text-lg shadow-inner">
                    <i class="fa fa-sitemap"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold leading-tight flex items-center gap-2">
                        Instruksi Bawahan ke Bawahannya
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-teal-400 text-teal-950">
                            Sampai Penerima Terakhir
                        </span>
                    </h3>
                    <p class="text-xs text-teal-100/90 mt-0.5">
                        Monitoring instruksi tugas dan checklist realisasi yang didelegasikan berantai antar bawahan.
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeSubordinateModal()" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer" title="Tutup">
                <i class="fa fa-times text-base"></i>
            </button>
        </div>

        {{-- Filter / Nav Tabs jika ada lebih dari 1 bawahan lanjutan --}}
        @if(isset($nestedSubordinates) && $nestedSubordinates->count() > 1)
        <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between gap-3 overflow-x-auto flex-shrink-0">
            <div class="flex items-center gap-2 overflow-x-auto">
                <span class="text-xs font-bold text-slate-500 whitespace-nowrap mr-1">Pilih Penerima:</span>
                <button type="button" onclick="filterSubordinateCards('all')" id="tab-sub-all" class="sub-tab-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-2xs transition whitespace-nowrap cursor-pointer">
                    Semua ({{ $nestedSubordinates->count() }})
                </button>
                @foreach($nestedSubordinates as $ns)
                    <button type="button" onclick="filterSubordinateCards('{{ $ns['penerima_id'] }}')" id="tab-sub-{{ $ns['penerima_id'] }}" class="sub-tab-btn px-3 py-1.5 rounded-xl text-xs font-semibold bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 transition whitespace-nowrap cursor-pointer">
                        {{ $ns['penerima']?->nama ?? 'Pegawai' }}
                    </button>
                @endforeach
            </div>
            <div class="flex items-center gap-2 flex-shrink-0" x-data="{ allModalExpanded: true }">
                <button type="button" 
                        @click="allModalExpanded = !allModalExpanded; $dispatch('toggle-all-modal-subgroups', allModalExpanded)" 
                        class="text-xs text-indigo-700 hover:text-indigo-900 bg-white hover:bg-indigo-50 border border-indigo-200 px-2.5 py-1.5 rounded-xl transition font-semibold inline-flex items-center gap-1.5 cursor-pointer shadow-2xs whitespace-nowrap">
                    <i class="fa text-[11px]" :class="allModalExpanded ? 'fa-angle-double-up' : 'fa-angle-double-down'"></i>
                    <span x-text="allModalExpanded ? 'Tutup Semua' : 'Buka Semua'"></span>
                </button>
            </div>
        </div>
        @endif

        {{-- Body Modal --}}
        <div class="p-6 overflow-y-auto space-y-6 flex-1 bg-slate-50/50">
            @if(!isset($nestedSubordinates) || $nestedSubordinates->isEmpty())
                <div class="text-center py-12 bg-white rounded-2xl border border-dashed border-slate-200 p-8">
                    <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mx-auto flex items-center justify-center text-xl mb-3">
                        <i class="fa fa-info-circle"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-700">Belum Ada Delegasi ke Bawahan Lanjutan</h4>
                    <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
                        Bawahan Anda belum meneruskan briefing ini ke tingkat bawahannya lagi.
                    </p>
                </div>
            @else
                @foreach($nestedSubordinates as $idx => $node)
                <div class="subordinate-card bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 transition-all" 
                     id="sub-card-{{ $node['penerima_id'] }}"
                     data-penerima-id="{{ $node['penerima_id'] }}"
                     data-pengirim-id="{{ $node['pengirim_id'] }}"
                     data-ancestor-ids="{{ implode(',', collect($node['pejabat_chain'])->pluck('id')->toArray()) }}"
                     x-data="{ expanded: true }"
                     @toggle-all-modal-subgroups.window="expanded = $event.detail">
                    {{-- Header Bawahan Turunan --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer select-none group"
                         @click="expanded = !expanded"
                         :class="expanded ? 'pb-4 border-b border-slate-100' : ''">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-teal-500 to-indigo-600 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                                {{ strtoupper(substr($node['penerima']?->nama ?? 'B', 0, 1)) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold text-slate-900 group-hover:text-teal-700 transition-colors">
                                        {{ $node['penerima']?->nama ?? 'Tanpa Nama' }}
                                    </h4>
                                    @if($node['is_last_receiver'])
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200">
                                            <i class="fa fa-flag-checkered mr-1"></i>Penerima Terakhir
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-teal-100 text-teal-800 border border-teal-200">
                                            Tingkat {{ $node['depth'] + 1 }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500">
                                    {{ $node['penerima']?->jabatan ?: ($node['penerima']?->alias_jabatan ?: 'Staf') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 sm:text-right">
                            <span class="text-xs font-bold text-teal-900 bg-teal-50 border border-teal-200 px-3 py-1 rounded-xl shadow-2xs">
                                {{ $node['stats']['selesai'] }}/{{ $node['stats']['total'] }} Selesai ({{ $node['stats']['percent'] }}%)
                            </span>
                            <div class="w-7 h-7 rounded-lg bg-slate-50 group-hover:bg-teal-50 text-slate-500 group-hover:text-teal-700 flex items-center justify-center border border-slate-200 group-hover:border-teal-200 shadow-2xs transition">
                                <i class="fa fa-chevron-down text-xs transition-transform duration-200" :class="expanded ? 'rotate-180' : ''"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Konten Collapsible Grup Bawahan di Modal --}}
                    <div x-show="expanded" 
                         x-transition:enter="transition ease-out duration-200" 
                         x-transition:enter-start="opacity-0 -translate-y-2" 
                         x-transition:enter-end="opacity-100 translate-y-0" 
                         x-transition:leave="transition ease-in duration-150" 
                         x-transition:leave-start="opacity-100 translate-y-0" 
                         x-transition:leave-end="opacity-0 -translate-y-2"
                         x-cloak 
                         class="pt-3">
                        {{-- Alur Rantai Hirarki / Breadcrumbs --}}
                        <div class="my-3 py-2 px-3 bg-slate-50 rounded-xl border border-slate-200/60 text-xs flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">
                                <i class="fa fa-code-fork mr-1"></i>Alur Rantai:
                            </span>
                            @foreach($node['chain'] as $cIdx => $cName)
                                @if($cIdx > 0)
                                    <i class="fa fa-angle-right text-slate-400 text-xs"></i>
                                @endif
                                <span class="{{ $cIdx === count($node['chain']) - 1 ? 'font-bold text-teal-800 bg-teal-100/60 px-2 py-0.5 rounded' : 'text-slate-600' }}">
                                    {{ $cName }}
                                </span>
                            @endforeach
                        </div>

                        {{-- Catatan Khusus Disposisi dari Atasannya --}}
                        @if($node['disposisi']->pesan_diteruskan || $node['disposisi']->pesan_khusus)
                        <div class="mb-4 p-3 bg-amber-50/60 border border-amber-200/80 rounded-xl text-xs text-amber-950">
                            <span class="font-bold uppercase tracking-wider text-[10px] text-amber-800 block mb-1">
                                <i class="fa fa-comment-o mr-1"></i> Catatan dari {{ $node['pengirim']?->nama ?? 'Atasan' }}:
                            </span>
                            <div class="whitespace-pre-wrap">{{ $node['disposisi']->pesan_diteruskan ?: $node['disposisi']->pesan_khusus }}</div>
                        </div>
                        @endif

                        {{-- Lampiran Khusus Disposisi --}}
                        @if($node['disposisi']->file_lampiran)
                        <div class="mb-4">
                            <a href="{{ $node['disposisi']->file_lampiran }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3 py-1.5 rounded-xl transition">
                                <i class="fa fa-paperclip"></i> Unduh File Khusus Disposisi
                            </a>
                        </div>
                        @endif

                        {{-- DAFTAR LENGKAP CHECKLIST INSTRUKSI --}}
                        <div>
                            <h5 class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-2 flex items-center gap-1.5">
                                <i class="fa fa-check-square-o text-teal-600"></i> Daftar Poin Instruksi Tugas ({{ $node['checklists']->count() }} Poin)
                            </h5>

                            @if($node['checklists']->isEmpty())
                                <p class="text-xs text-slate-400 italic bg-slate-50 p-3 rounded-xl border border-slate-100">
                                    Tidak ada poin instruksi checklist khusus yang dicatat.
                                </p>
                            @else
                                <div class="space-y-2.5">
                                    @foreach($node['checklists'] as $chkIdx => $chk)
                                        @php
                                            $chkStatus = $chk->status ?? ($chk->is_checked ? 'selesai' : 'pending');
                                        @endphp
                                        <div class="p-3.5 rounded-xl border {{ $chkStatus === 'selesai' ? 'bg-emerald-50/30 border-emerald-200' : ($chkStatus === 'proses' ? 'bg-amber-50/30 border-amber-200' : 'bg-slate-50/60 border-slate-200') }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div class="flex items-start gap-2.5">
                                                <span class="w-6 h-6 rounded-lg {{ $chkStatus === 'selesai' ? 'bg-emerald-600 text-white' : ($chkStatus === 'proses' ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-600') }} flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">
                                                    @if($chkStatus === 'selesai')
                                                        <i class="fa fa-check text-[10px]"></i>
                                                    @elseif($chkStatus === 'proses')
                                                        <i class="fa fa-spinner fa-spin text-[10px]"></i>
                                                    @else
                                                        {{ $chkIdx + 1 }}
                                                    @endif
                                                </span>
                                                <div>
                                                    <p class="text-xs font-semibold {{ $chkStatus === 'selesai' ? 'text-slate-600 line-through' : 'text-slate-900' }}">
                                                        {{ $chk->kegiatan }}
                                                    </p>
                                                    <div class="flex flex-wrap items-center gap-2 mt-1 text-[11px]">
                                                        @if($chkStatus === 'selesai')
                                                            <span class="text-emerald-700 font-semibold inline-flex items-center gap-1">
                                                                <i class="fa fa-check-circle"></i> Selesai
                                                                @if($chk->checked_at)
                                                                    &bull; {{ $chk->checked_at->translatedFormat('d M Y, H:i') }}
                                                                @endif
                                                            </span>
                                                        @elseif($chkStatus === 'proses')
                                                            <span class="text-amber-700 font-semibold inline-flex items-center gap-1">
                                                                <i class="fa fa-clock-o"></i> Sedang Dikerjakan
                                                            </span>
                                                        @else
                                                            <span class="text-slate-400 italic">
                                                                Menunggu pengerjaan
                                                            </span>
                                                        @endif

                                                        {{-- File Lampiran Bukti Pengerjaan Bawahan --}}
                                                        @if($chk->file_lampiran)
                                                            @php
                                                                $chkFile = $chk->files->first();
                                                                $chkPath = $chkFile?->file ?? $chk->file_lampiran ?? '';
                                                                $chkExt = pathinfo($chkPath, PATHINFO_EXTENSION);
                                                                $chkUrl = $chkFile ? $chkFile->url : (filter_var($chk->file_lampiran, FILTER_VALIDATE_URL) ? $chk->file_lampiran : url('storage/' . $chk->file_lampiran));
                                                            @endphp
                                                            <a href="{{ $chkUrl }}" target="_blank" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-medium border border-indigo-200 transition">
                                                                <i class="fa fa-paperclip"></i> Bukti / Lampiran
                                                                <i class="fa fa-external-link text-[9px]"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Badge Status di kanan --}}
                                            <div class="flex-shrink-0 self-end sm:self-center">
                                                @if($chkStatus === 'selesai')
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        <i class="fa fa-check"></i> Selesai
                                                    </span>
                                                @elseif($chkStatus === 'proses')
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                        <i class="fa fa-spinner fa-spin"></i> Proses
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                        Menunggu
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Balasan / Laporan Progres Bawahan jika ada --}}
                        @if($node['disposisi']->balasan_teks || $node['disposisi']->dibalas_pada)
                            <div class="mt-4 pt-3 border-t border-slate-100 text-xs">
                                <span class="font-bold text-slate-700 uppercase tracking-wider text-[10px] block mb-1.5">
                                    <i class="fa fa-reply mr-1 text-indigo-600"></i> Laporan / Balasan Bawahan:
                                </span>
                                <div class="p-3 rounded-xl bg-indigo-50/40 border border-indigo-100 text-slate-800 whitespace-pre-wrap">
                                    {{ $node['disposisi']->balasan_teks ?: 'Telah mengirimkan respon.' }}
                                </div>
                                @if($node['disposisi']->balasan_file)
                                    <div class="mt-2">
                                        <a href="{{ $node['disposisi']->balasan_file }}" target="_blank" class="inline-flex items-center gap-1.5 text-indigo-700 hover:text-indigo-900 font-semibold bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-200">
                                            <i class="fa fa-paperclip"></i> Lihat Lampiran Laporan
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            @endif
        </div>

        {{-- Footer Modal --}}
        <div class="px-6 py-4 bg-white border-t border-slate-200 flex items-center justify-between flex-shrink-0">
            <span class="text-xs text-slate-500">
                Total Bawahan Lanjutan: <strong>{{ isset($nestedSubordinates) ? $nestedSubordinates->count() : 0 }} Orang</strong>
            </span>
            <button type="button" onclick="closeSubordinateModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-100 border border-slate-300 transition cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- Toast Notification Container --}}
<div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `pointer-events-auto flex items-center gap-2 px-4 py-3 rounded-2xl shadow-lg text-xs font-bold transition-all duration-300 transform translate-y-4 opacity-0 ${
            type === 'success' ? 'bg-emerald-600 text-white shadow-emerald-600/30' : 
            (type === 'warning' ? 'bg-amber-500 text-white shadow-amber-500/30' : 'bg-slate-800 text-white shadow-slate-900/30')
        }`;
        
        const icon = type === 'success' ? '<i class="fa fa-check-circle"></i>' : (type === 'warning' ? '<i class="fa fa-spinner fa-spin"></i>' : '<i class="fa fa-info-circle"></i>');
        toast.innerHTML = `${icon} <span>${message}</span>`;
        container.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-y-4', 'opacity-0');
        }, 10);

        // Auto remove
        setTimeout(() => {
            toast.classList.add('opacity-0', 'translate-y-4');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    async function konfirmasiChecklist(id, status, btnElement) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const updateUrl = "{{ plugin_route('e-office.briefing.checklist.update', 'REPLACE_ID') }}".replace('REPLACE_ID', id);

        // Disable all buttons in this group temporarily
        const parentGroup = btnElement.closest('.inline-flex');
        const allBtns = parentGroup ? parentGroup.querySelectorAll('button') : [];
        allBtns.forEach(b => b.disabled = true);

        try {
            const response = await fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            });

            const data = await response.json();

            if (data.success) {
                // 1. Update Buttons State
                const btnPending = document.getElementById(`btn-status-pending-${id}`);
                const btnProses = document.getElementById(`btn-status-proses-${id}`);
                const btnSelesai = document.getElementById(`btn-status-selesai-${id}`);

                if (btnPending) {
                    btnPending.className = status === 'pending'
                        ? 'px-2.5 py-1.5 rounded-lg transition bg-white text-slate-800 shadow-xs border border-slate-200'
                        : 'px-2.5 py-1.5 rounded-lg transition text-slate-500 hover:text-slate-900 hover:bg-slate-200/60';
                }
                if (btnProses) {
                    btnProses.className = status === 'proses'
                        ? 'px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 bg-amber-500 text-white shadow-xs'
                        : 'px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 text-amber-800 hover:bg-amber-100';
                    const iconP = btnProses.querySelector('i');
                    if (iconP) iconP.className = `fa fa-spinner ${status === 'proses' ? 'fa-spin' : ''}`;
                }
                if (btnSelesai) {
                    btnSelesai.className = status === 'selesai'
                        ? 'px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 bg-emerald-600 text-white shadow-xs'
                        : 'px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 text-emerald-800 hover:bg-emerald-100';
                }

                // 2. Update Row Visual
                const row = document.getElementById(`chk-row-${id}`);
                const icon = document.getElementById(`chk-icon-${id}`);
                const text = document.getElementById(`chk-text-${id}`);
                const meta = document.getElementById(`chk-meta-${id}`);

                if (row) {
                    row.className = `bg-white rounded-2xl p-4 border transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs ${
                        status === 'selesai' ? 'border-emerald-200 bg-emerald-50/10' : 
                        (status === 'proses' ? 'border-amber-200 bg-amber-50/10' : 'border-slate-200')
                    }`;
                }

                if (icon) {
                    if (status === 'selesai') {
                        icon.innerHTML = `<div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm font-bold shadow-xs"><i class="fa fa-check"></i></div>`;
                    } else if (status === 'proses') {
                        icon.innerHTML = `<div class="w-7 h-7 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm font-bold shadow-xs animate-pulse"><i class="fa fa-spinner fa-spin"></i></div>`;
                    } else {
                        icon.innerHTML = `<div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-400 border border-slate-300 flex items-center justify-center text-sm"><i class="fa fa-circle-o"></i></div>`;
                    }
                }

                if (text) {
                    text.className = `text-sm font-semibold ${
                        status === 'selesai' ? 'text-slate-500 line-through' : 
                        (status === 'proses' ? 'text-amber-950 font-bold' : 'text-slate-900')
                    }`;
                }

                if (meta) {
                    if (status === 'selesai') {
                        meta.innerHTML = `<span class="text-emerald-700 font-medium"><i class="fa fa-check-circle mr-1"></i>Selesai oleh <strong>${data.checked_by || 'Anda'}</strong> &bull; ${data.checked_at || 'Baru saja'}</span>`;
                    } else if (status === 'proses') {
                        meta.innerHTML = `<span class="text-amber-700 font-medium"><i class="fa fa-clock-o mr-1"></i>Sedang diproses oleh <strong>${data.checked_by || 'Anda'}</strong> &bull; sejak ${data.checked_at || 'Baru saja'}</span>`;
                    } else {
                        meta.innerHTML = `<span class="text-slate-400 italic">Belum dikonfirmasi / menunggu pengerjaan</span>`;
                    }
                }

                // 3. Update Counters & Progress Bar untuk Tugas Anda
                if (data.my_stats) {
                    const elMySelesai = document.getElementById('my-stat-selesai');
                    const elMyProses = document.getElementById('my-stat-proses');
                    const elMyPending = document.getElementById('my-stat-pending');
                    const elMyPercent = document.getElementById('my-stat-percent');
                    const barMySelesai = document.getElementById('my-progressbar-selesai');
                    const barMyProses = document.getElementById('my-progressbar-proses');

                    if (elMySelesai) elMySelesai.textContent = data.my_stats.selesai;
                    if (elMyProses) elMyProses.textContent = data.my_stats.proses;
                    if (elMyPending) elMyPending.textContent = data.my_stats.pending;
                    if (elMyPercent) elMyPercent.textContent = `${data.my_stats.percent}%`;
                    if (barMySelesai) barMySelesai.style.width = `${data.my_stats.percent}%`;
                    if (barMyProses && data.my_stats.total > 0) {
                        const myProsesPct = Math.round((data.my_stats.proses / data.my_stats.total) * 100);
                        barMyProses.style.width = `${myProsesPct}%`;
                    }
                }

                // 4. Update Counters & Progress Bar untuk Monitoring Bawahan
                if (data.sub_stats) {
                    const elSubSelesai = document.getElementById('sub-stat-selesai');
                    const elSubProses = document.getElementById('sub-stat-proses');
                    const elSubPending = document.getElementById('sub-stat-pending');
                    const elSubPercent = document.getElementById('sub-stat-percent');
                    const barSubSelesai = document.getElementById('sub-progressbar-selesai');
                    const barSubProses = document.getElementById('sub-progressbar-proses');

                    if (elSubSelesai) elSubSelesai.textContent = data.sub_stats.selesai;
                    if (elSubProses) elSubProses.textContent = data.sub_stats.proses;
                    if (elSubPending) elSubPending.textContent = data.sub_stats.pending;
                    if (elSubPercent) elSubPercent.textContent = `${data.sub_stats.percent}%`;
                    if (barSubSelesai) barSubSelesai.style.width = `${data.sub_stats.percent}%`;
                    if (barSubProses && data.sub_stats.total > 0) {
                        const subProsesPct = Math.round((data.sub_stats.proses / data.sub_stats.total) * 100);
                        barSubProses.style.width = `${subProsesPct}%`;
                    }
                }

                // 5. Update Badge Ringkasan Perorangan Realtime (Tugas Anda & Kelompok Bawahan)
                const elMySummary = document.getElementById('my-person-summary');
                if (elMySummary && data.my_stats) {
                    elMySummary.textContent = data.my_stats.label || `${data.my_stats.selesai}/${data.my_stats.total} Selesai (${data.my_stats.percent}%)`;
                }

                if (data.person_stats && data.person_stats.penerima_id) {
                    const elPersonSummary = document.getElementById(`person-summary-${data.person_stats.penerima_id}`);
                    if (elPersonSummary) {
                        elPersonSummary.textContent = data.person_stats.label || `${data.person_stats.selesai}/${data.person_stats.total} Selesai (${data.person_stats.percent}%)`;
                    }
                }

                // Fallback untuk backward compatibility
                if (data.stats) {
                    const elSelesai = document.getElementById('stat-selesai');
                    const elProses = document.getElementById('stat-proses');
                    const elPending = document.getElementById('stat-pending');
                    const elPercent = document.getElementById('stat-percent');
                    const barSelesai = document.getElementById('progressbar-selesai');
                    const barProses = document.getElementById('progressbar-proses');

                    if (elSelesai) elSelesai.textContent = data.stats.selesai;
                    if (elProses) elProses.textContent = data.stats.proses;
                    if (elPending) elPending.textContent = data.stats.pending;
                    if (elPercent) elPercent.textContent = `${data.stats.percent}%`;
                    if (barSelesai) barSelesai.style.width = `${data.stats.percent}%`;
                    if (barProses && data.stats.total > 0) {
                        const prosesPercent = Math.round((data.stats.proses / data.stats.total) * 100);
                        barProses.style.width = `${prosesPercent}%`;
                    }
                }

                showToast(data.message || 'Status berhasil diperbarui.', status === 'selesai' ? 'success' : (status === 'proses' ? 'warning' : 'info'));
            } else {
                alert('Gagal memperbarui status: ' + (data.message || 'Terjadi kesalahan'));
            }
        } catch (err) {
            console.error('Error checklist update:', err);
            alert('Terjadi kesalahan jaringan saat memperbarui checklist.');
        } finally {
            allBtns.forEach(b => b.disabled = false);
        }
    }

    function triggerChecklistUpload(id) {
        const input = document.getElementById(`chk-file-input-${id}`);
        if (input) input.click();
    }

    function getFileIconClass(ext) {
        ext = (ext || '').toLowerCase();
        if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
            return 'fa-file-image-o text-indigo-500';
        } else if (ext === 'pdf') {
            return 'fa-file-pdf-o text-rose-500';
        } else if (['doc', 'docx'].includes(ext)) {
            return 'fa-file-word-o text-blue-500';
        } else if (['xls', 'xlsx', 'csv'].includes(ext)) {
            return 'fa-file-excel-o text-emerald-500';
        } else if (['zip', 'rar', '7z'].includes(ext)) {
            return 'fa-file-archive-o text-amber-500';
        }
        return 'fa-file-text-o text-slate-500';
    }

    async function handleChecklistFileUpload(id, inputElement) {
        if (!inputElement.files || inputElement.files.length === 0) return;
        const file = inputElement.files[0];
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const uploadUrl = "{{ plugin_route('e-office.briefing.checklist.upload-file', 'REPLACE_ID') }}".replace('REPLACE_ID', id);

        const container = document.getElementById(`chk-file-box-${id}`);
        if (!container) return;

        const originalHtml = container.innerHTML;
        container.innerHTML = `
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 text-xs font-semibold text-indigo-700 animate-pulse">
                <i class="fa fa-spinner fa-spin"></i> Mengunggah ${file.name}...
            </div>
        `;

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const iconClass = getFileIconClass(data.file_ext);
                const fileName = data.file_name || 'Dokumen Lampiran';
                container.innerHTML = `
                    <div id="chk-file-chip-${id}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200 transition-colors shadow-2xs text-xs">
                        <i class="fa ${iconClass} text-sm"></i>
                        <a href="${data.file_url}" target="_blank" class="font-semibold text-slate-800 hover:text-indigo-600 truncate max-w-[180px] sm:max-w-xs transition-colors" title="Buka Dokumen/Gambar Lampiran">
                            ${fileName}
                        </a>
                        <a href="${data.file_url}" target="_blank" class="p-1 text-slate-400 hover:text-indigo-600 transition-colors" title="Buka di tab baru">
                            <i class="fa fa-external-link"></i>
                        </a>
                    </div>
                    <div id="chk-file-actions-${id}" class="inline-flex items-center gap-1.5 text-xs">
                        <button type="button" onclick="triggerChecklistUpload('${id}')" class="px-2 py-1 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors font-medium" title="Ganti File Lampiran">
                            <i class="fa fa-refresh mr-1"></i> Ganti
                        </button>
                        <button type="button" onclick="deleteChecklistFile('${id}')" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors font-medium" title="Hapus File Lampiran">
                            <i class="fa fa-trash-o mr-1"></i> Hapus
                        </button>
                        <input type="file" id="chk-file-input-${id}" onchange="handleChecklistFileUpload('${id}', this)" class="hidden" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt">
                    </div>
                `;
                showToast(data.message || 'Dokumen berhasil diunggah.', 'success');
            } else {
                container.innerHTML = originalHtml;
                alert('Gagal mengunggah file: ' + (data.message || 'Terjadi kesalahan'));
            }
        } catch (err) {
            console.error('Upload error:', err);
            container.innerHTML = originalHtml;
            alert('Terjadi kesalahan jaringan saat mengunggah file.');
        } finally {
            inputElement.value = '';
        }
    }

    async function deleteChecklistFile(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus file lampiran ini?')) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const deleteUrl = "{{ plugin_route('e-office.briefing.checklist.delete-file', 'REPLACE_ID') }}".replace('REPLACE_ID', id);

        const container = document.getElementById(`chk-file-box-${id}`);
        if (!container) return;

        const originalHtml = container.innerHTML;
        container.innerHTML = `
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-500 animate-pulse">
                <i class="fa fa-spinner fa-spin"></i> Menghapus lampiran...
            </div>
        `;

        try {
            const response = await fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                container.innerHTML = `
                    <div id="chk-file-upload-btn-${id}" class="inline-flex items-center gap-2">
                        <button type="button" onclick="triggerChecklistUpload('${id}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100/80 border border-indigo-200/70 text-indigo-700 text-xs font-semibold transition-all shadow-2xs hover:shadow-xs">
                            <i class="fa fa-paperclip"></i> Unggah Dokumen / Bukti
                        </button>
                        <span class="text-[11px] text-slate-400 hidden sm:inline">Gambar atau Office/PDF (Maks 20MB)</span>
                        <input type="file" id="chk-file-input-${id}" onchange="handleChecklistFileUpload('${id}', this)" class="hidden" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt">
                    </div>
                `;
                showToast(data.message || 'File lampiran berhasil dihapus.', 'info');
            } else {
                container.innerHTML = originalHtml;
                alert('Gagal menghapus file: ' + (data.message || 'Terjadi kesalahan'));
            }
        } catch (err) {
            console.error('Delete file error:', err);
            container.innerHTML = originalHtml;
            alert('Terjadi kesalahan jaringan saat menghapus file.');
        }
    }

    // Modal Subordinate Instructions Handlers
    function openSubordinateModal(penerimaId = 'all') {
        const modal = document.getElementById('modalSubordinateInstructions');
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');

        filterSubordinateCards(penerimaId);
    }

    function closeSubordinateModal() {
        const modal = document.getElementById('modalSubordinateInstructions');
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function filterSubordinateCards(targetId) {
        const cards = document.querySelectorAll('.subordinate-card');
        const tabs = document.querySelectorAll('.sub-tab-btn');

        let matchedCards = [];
        cards.forEach(card => {
            const penerimaId = card.getAttribute('data-penerima-id') || '';
            const pengirimId = card.getAttribute('data-pengirim-id') || '';
            const ancestorIds = (card.getAttribute('data-ancestor-ids') || '').split(',');

            const isMatch = (!targetId || targetId === 'all')
                || (penerimaId === targetId)
                || (pengirimId === targetId)
                || ancestorIds.includes(targetId);

            if (isMatch) {
                card.style.display = 'block';
                matchedCards.push(card);
            } else {
                card.style.display = 'none';
            }
        });

        // Jika tidak ada kartu yang cocok sama sekali, jangan biarkan modal kosong: tampilkan semua kartu
        if (matchedCards.length === 0) {
            cards.forEach(card => {
                card.style.display = 'block';
                matchedCards.push(card);
            });
            targetId = 'all';
        }

        // Update status active pada Nav Tabs
        tabs.forEach(tab => {
            tab.className = 'sub-tab-btn px-3 py-1.5 rounded-xl text-xs font-semibold bg-white text-slate-700 hover:bg-slate-100 border border-slate-200 transition whitespace-nowrap cursor-pointer';
        });

        let activeTab = document.getElementById(`tab-sub-${targetId}`);
        if (!activeTab && matchedCards.length === 1) {
            const matchedPenerimaId = matchedCards[0].getAttribute('data-penerima-id');
            activeTab = document.getElementById(`tab-sub-${matchedPenerimaId}`);
        }
        if (!activeTab) {
            activeTab = document.getElementById('tab-sub-all');
        }

        if (activeTab) {
            activeTab.className = 'sub-tab-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-600 text-white shadow-2xs transition whitespace-nowrap cursor-pointer';
        }

        // Scroll kartu yang pertama ke pandangan
        if (matchedCards.length > 0) {
            setTimeout(() => {
                matchedCards[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 50);
        }
    }

    // Event listener untuk tombol Escape & klik pada backdrop modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSubordinateModal();
        }
    });

    document.getElementById('modalSubordinateInstructions')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeSubordinateModal();
        }
    });
</script>
@endpush
@endsection
