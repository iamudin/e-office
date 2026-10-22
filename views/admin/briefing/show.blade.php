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

    {{-- Checklist Progress & Konfirmasi Instruksi --}}
    @if($briefing->checklists->count() > 0)
    @php
        $allChks = $briefing->checklists;
        $totalAll = $allChks->count();
        $selesaiAll = $allChks->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
        $prosesAll = $allChks->where('status', 'proses')->count();
        $pendingAll = $totalAll - $selesaiAll - $prosesAll;
        $percentAll = $totalAll > 0 ? round(($selesaiAll / $totalAll) * 100) : 0;
    @endphp

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden p-6 sm:p-8" id="checklistSection">
        {{-- Section Header & Global Progress Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-6 border-b border-slate-100">
            <div>
                <h3 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa fa-tasks text-indigo-600"></i> Ceklis & Realisasi Instruksi Tugas
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Penerima briefing dapat mengonfirmasi status pengerjaan setiap poin instruksi secara mandiri sebagai <strong class="text-amber-600">"Proses"</strong> atau <strong class="text-emerald-600">"Selesai"</strong>.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <i class="fa fa-check-circle"></i> <span id="stat-selesai">{{ $selesaiAll }}</span> Selesai
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    <i class="fa fa-spinner"></i> <span id="stat-proses">{{ $prosesAll }}</span> Proses
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    <span id="stat-pending">{{ $pendingAll }}</span> Menunggu
                </span>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="mb-6 bg-slate-50 p-4 rounded-2xl border border-slate-100">
            <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-2">
                <span>Tingkat Ketercapaian Seluruh Instruksi</span>
                <span class="text-indigo-600 text-sm" id="stat-percent">{{ $percentAll }}%</span>
            </div>
            <div class="w-full bg-slate-200 rounded-full h-3 overflow-hidden flex">
                <div id="progressbar-selesai" class="bg-emerald-500 h-3 transition-all duration-300" style="width: {{ $percentAll }}%" title="Selesai"></div>
                <div id="progressbar-proses" class="bg-amber-400 h-3 transition-all duration-300" style="width: {{ $totalAll > 0 ? round(($prosesAll / $totalAll) * 100) : 0 }}%" title="Proses"></div>
            </div>
        </div>

        {{-- List Checklist --}}
        @php
            $grouped = $briefing->checklists->groupBy('penerima_id');
        @endphp

        <div class="space-y-6">
            @foreach($grouped as $penerimaId => $chks)
                @php
                    $isMyGroup = (is_null($penerimaId) || $penerimaId == $pejabat?->id);
                    $penerimaPejabat = $chks->first()->penerima;
                @endphp

                <div class="rounded-2xl border {{ $isMyGroup ? 'border-indigo-200 bg-indigo-50/20' : 'border-slate-200/80 bg-slate-50/30' }} p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-2 mb-3 pb-2 border-b {{ $isMyGroup ? 'border-indigo-100' : 'border-slate-100' }}">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-bold">
                                {{ strtoupper(substr($penerimaPejabat?->nama ?? 'B', 0, 1)) }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-indigo-950 flex items-center gap-1.5">
                                    {{ $penerimaPejabat?->nama ?? 'Bawahan' }}
                                    @if($penerimaId == $pejabat?->id)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-indigo-600 text-white">Tugas Anda</span>
                                    @endif
                                </h4>
                                <p class="text-[11px] text-slate-500">{{ $penerimaPejabat?->jabatan ?: ($penerimaPejabat?->alias_jabatan ?: 'Staf') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Items per person --}}
                    <div class="space-y-3">
                        @foreach($chks as $chk)
                            @php
                                $chkStatus = $chk->status ?: ($chk->is_checked ? 'selesai' : 'pending');
                                $canEditThis = ($briefing->status !== 'completed' && ($isKadis || $isPembuat || $isMyGroup));
                            @endphp

                            <div id="chk-row-{{ $chk->id }}" 
                                class="bg-white rounded-2xl p-4 border transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs {{ $chkStatus === 'selesai' ? 'border-emerald-200 bg-emerald-50/10' : ($chkStatus === 'proses' ? 'border-amber-200 bg-amber-50/10' : 'border-slate-200') }}">
                                
                                {{-- Item Details --}}
                                <div class="flex items-start gap-3 flex-1">
                                    <div id="chk-icon-{{ $chk->id }}" class="mt-0.5 flex-shrink-0">
                                        @if($chkStatus === 'selesai')
                                            <div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                                                <i class="fa fa-check"></i>
                                            </div>
                                        @elseif($chkStatus === 'proses')
                                            <div class="w-7 h-7 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm font-bold shadow-xs animate-pulse">
                                                <i class="fa fa-spinner fa-spin"></i>
                                            </div>
                                        @else
                                            <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-400 border border-slate-300 flex items-center justify-center text-sm">
                                                <i class="fa fa-circle-o"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <p id="chk-text-{{ $chk->id }}" class="text-sm font-semibold {{ $chkStatus === 'selesai' ? 'text-slate-500 line-through' : ($chkStatus === 'proses' ? 'text-amber-950 font-bold' : 'text-slate-900') }}">
                                            {{ $chk->kegiatan }}
                                        </p>
                                        <div id="chk-meta-{{ $chk->id }}" class="text-xs mt-1">
                                            @if($chkStatus === 'selesai')
                                                <span class="text-emerald-700 font-medium">
                                                    <i class="fa fa-check-circle mr-1"></i>Selesai oleh <strong>{{ $chk->checkedByPejabat?->nama ?? 'Penerima' }}</strong>
                                                    @if($chk->checked_at)
                                                        &bull; {{ $chk->checked_at->translatedFormat('d M Y, H:i') }}
                                                    @endif
                                                </span>
                                            @elseif($chkStatus === 'proses')
                                                <span class="text-amber-700 font-medium">
                                                    <i class="fa fa-clock-o mr-1"></i>Sedang dikerjakan / diproses oleh <strong>{{ $chk->checkedByPejabat?->nama ?? 'Penerima' }}</strong>
                                                    @if($chk->checked_at)
                                                        &bull; sejak {{ $chk->checked_at->translatedFormat('d M, H:i') }}
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-slate-400 italic">Belum dikonfirmasi / menunggu pengerjaan</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Action / Status Buttons --}}
                                <div class="flex-shrink-0 flex items-center gap-2 self-end md:self-center">
                                    @if($canEditThis)
                                        <div class="inline-flex rounded-xl p-1 bg-slate-100 border border-slate-200 gap-1 text-xs font-bold">
                                            {{-- Tombol Pending / Reset --}}
                                            <button type="button" 
                                                onclick="konfirmasiChecklist('{{ $chk->id }}', 'pending', this)"
                                                id="btn-status-pending-{{ $chk->id }}"
                                                class="px-2.5 py-1.5 rounded-lg transition {{ $chkStatus === 'pending' ? 'bg-white text-slate-800 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/60' }}"
                                                title="Tandai Belum Dikerjakan">
                                                Menunggu
                                            </button>

                                            {{-- Tombol Proses --}}
                                            <button type="button" 
                                                onclick="konfirmasiChecklist('{{ $chk->id }}', 'proses', this)"
                                                id="btn-status-proses-{{ $chk->id }}"
                                                class="px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 {{ $chkStatus === 'proses' ? 'bg-amber-500 text-white shadow-xs' : 'text-amber-800 hover:bg-amber-100' }}"
                                                title="Tandai Sedang Dikerjakan (Proses)">
                                                <i class="fa fa-spinner {{ $chkStatus === 'proses' ? 'fa-spin' : '' }}"></i> Proses
                                            </button>

                                            {{-- Tombol Selesai --}}
                                            <button type="button" 
                                                onclick="konfirmasiChecklist('{{ $chk->id }}', 'selesai', this)"
                                                id="btn-status-selesai-{{ $chk->id }}"
                                                class="px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 {{ $chkStatus === 'selesai' ? 'bg-emerald-600 text-white shadow-xs' : 'text-emerald-800 hover:bg-emerald-100' }}"
                                                title="Tandai Selesai">
                                                <i class="fa fa-check"></i> Selesai
                                            </button>
                                        </div>
                                    @else
                                        {{-- Readonly Badge jika bukan penerima / briefing ditutup --}}
                                        @if($chkStatus === 'selesai')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white">
                                                <i class="fa fa-check-circle"></i> Selesai
                                            </span>
                                        @elseif($chkStatus === 'proses')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500 text-white">
                                                <i class="fa fa-spinner fa-spin"></i> Proses
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                <i class="fa fa-clock-o"></i> Menunggu
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
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
                        if ($isKadis) {
                            $disposisisToView = $briefing->disposisis;
                        } else {
                            $allowedIds = [];
                            
                            // 1. Tambahkan disposisi yang diterima oleh user (dari atasan)
                            $myReceived = $briefing->disposisis->filter(fn($d) => $d->penerima_id == $pejabat?->id);
                            foreach($myReceived as $mr) {
                                $allowedIds[] = $mr->id;
                            }
                            
                            // 2. Fungsi rekursif untuk mengumpulkan semua rantai disposisi di bawah user ini
                            $getAllSubordinates = function($pengirimId) use (&$getAllSubordinates, $briefing, &$allowedIds) {
                                $children = $briefing->disposisis->filter(fn($d) => $d->pengirim_id == $pengirimId);
                                foreach($children as $child) {
                                    $allowedIds[] = $child->id;
                                    $getAllSubordinates($child->penerima_id);
                                }
                            };
                            
                            // Mulai kumpulkan dari user saat ini ke bawah
                            if ($pejabat) {
                                $getAllSubordinates($pejabat->id);
                            }
                            
                            $disposisisToView = $briefing->disposisis->filter(fn($d) => in_array($d->id, $allowedIds));
                        }
                        
                        // Buat peta penerima -> pengirim untuk menghitung level hierarki
                        $penerimaToPengirim = [];
                        foreach($briefing->disposisis as $d) {
                            $penerimaToPengirim[$d->penerima_id] = $d->pengirim_id;
                        }
                        
                        $getLevel = function($penerimaId) use ($penerimaToPengirim) {
                            $level = 0;
                            $current = $penerimaId;
                            while(isset($penerimaToPengirim[$current]) && $penerimaToPengirim[$current] !== null) {
                                $level++;
                                $current = $penerimaToPengirim[$current];
                                if ($level > 10) break; // Mencegah infinite loop
                            }
                            return $level;
                        };
                        
                        // Mengurutkan disposisi berdasarkan struktur pohon (Atasan -> Bawahan -> Bawahan dari bawahan)
                        $buildTree = function($pengirimId, $allDisposisis) use (&$buildTree) {
                            $children = $allDisposisis->filter(fn($d) => $d->pengirim_id == $pengirimId)->sortBy('created_at');
                            $result = collect();
                            foreach($children as $child) {
                                $result->push($child);
                                $result = $result->merge($buildTree($child->penerima_id, $allDisposisis));
                            }
                            return $result;
                        };

                        $penerimaIds = $disposisisToView->pluck('penerima_id')->toArray();
                        // Roots adalah disposisi dimana pengirimnya tidak menjadi penerima di disposisi manapun dalam list ini
                        $roots = $disposisisToView->filter(fn($d) => !in_array($d->pengirim_id, $penerimaIds))->sortBy('created_at');

                        $sortedDisposisis = collect();
                        foreach($roots as $root) {
                            $sortedDisposisis->push($root);
                            $sortedDisposisis = $sortedDisposisis->merge($buildTree($root->penerima_id, $disposisisToView));
                        }
                        
                        // Jaga-jaga jika ada yang tertinggal (orphan)
                        $sortedIds = $sortedDisposisis->pluck('id')->toArray();
                        $leftovers = $disposisisToView->filter(fn($d) => !in_array($d->id, $sortedIds))->sortBy('created_at');
                        $sortedDisposisis = $sortedDisposisis->merge($leftovers);

                    @endphp
                    @foreach($sortedDisposisis as $disp)
                    @php 
                        $level = $getLevel($disp->penerima_id); 
                        $marginLeft = $level > 0 ? 'ml-' . min($level * 6, 24) : ''; // Maksimal indent ml-24
                        $borderLeft = $level > 0 ? 'border-l-2 border-indigo-100 pl-4' : '';
                    @endphp
                    <div class="p-5 hover:bg-gray-50/50 transition-colors {{ $marginLeft }} {{ $borderLeft }}">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                    {{ strtoupper(substr($disp->penerima->nama ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $disp->penerima->nama ?? 'Tanpa Nama' }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $disp->penerima->jabatan ?? $disp->penerima->alias_jabatan }}
                                        @if($disp->pengirim_id)
                                            <span class="text-indigo-500 block mt-0.5"><i class="fa fa-level-up mr-1"></i>Bawahan dari: {{ $disp->pengirim->nama ?? 'Pimpinan' }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-gray-400 block">{{ $disp->created_at->diffForHumans() }}</span>
                                @if($disp->dibaca_pada)
                                    <span class="text-[10px] text-blue-500 font-medium"><i class="fa fa-check-double mr-1"></i>Dibaca</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="ml-10 space-y-3">
                            {{-- Pesan Diteruskan & Khusus --}}
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-sm text-gray-700">
                                @if($disp->pesan_diteruskan)
                                <div class="mb-2">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Instruksi Khusus</span>
                                    <div class="whitespace-pre-wrap">{{ $disp->pesan_diteruskan }}</div>
                                </div>
                                @endif
                                
                                @if($disp->pesan_khusus)
                                <div class="mb-2 p-2 bg-blue-50 border border-blue-100 rounded-lg">
                                    <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider block mb-1">Pesan Khusus</span>
                                    <div class="whitespace-pre-wrap text-blue-900">{{ $disp->pesan_khusus }}</div>
                                </div>
                                @endif
                                
                                @if(!$disp->pesan_diteruskan && !$disp->pesan_khusus)
                                Tidak ada pesan instruksi tambahan.
                                @endif
                                
                                @if($disp->file_lampiran)
                                <div class="mt-3">
                                    <a href="{{ $disp->file_lampiran }}" target="_blank" class="inline-flex items-center gap-2 text-xs font-medium text-indigo-700 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1.5 rounded-lg border border-indigo-200 transition-colors">
                                        <i class="fa fa-paperclip"></i> Unduh File Khusus
                                    </a>
                                </div>
                                @endif
                            </div>

                            @if($disp->dibalas_pada)
                                @php
                                    $canViewResponse = $isKadis || $disp->pengirim_id == $pejabat?->id || $disp->penerima_id == $pejabat?->id || $disp->is_validated_by_atasan;
                                @endphp
                                
                                @if($canViewResponse)
                                <div class="bg-indigo-50/50 p-3 rounded-xl border border-indigo-100 text-sm text-gray-800 mt-3">
                                    <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider block mb-3"><i class="fa fa-history mr-1"></i> Riwayat Progress / Balasan</span>
                                    
                                    @php
                                        $history = $disp->balasan_history ? json_decode($disp->balasan_history, true) : null;
                                    @endphp
                                    
                                    @if($history && is_array($history) && count($history) > 0)
                                        <div class="space-y-3">
                                            @foreach($history as $item)
                                            <div class="bg-white p-3 rounded-lg border border-indigo-100/60 shadow-sm">
                                                <div class="flex justify-between items-center mb-1.5 border-b border-gray-100 pb-1.5">
                                                    <span class="text-[11px] font-bold text-gray-700"><i class="fa fa-user text-indigo-400 mr-1"></i> {{ $item['nama'] ?? 'Bawahan' }}</span>
                                                    <span class="text-[10px] font-medium text-gray-500 bg-gray-50 px-1.5 py-0.5 rounded">{{ $item['waktu'] ?? '' }}</span>
                                                </div>
                                                <p class="whitespace-pre-wrap text-sm text-gray-700 leading-relaxed">{{ $item['pesan'] ?? '' }}</p>
                                                
                                                @if(!empty($item['file_url']))
                                                <div class="mt-2 pt-2 border-t border-gray-50">
                                                    <a href="{{ $item['file_url'] }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 px-3 py-1.5 rounded-lg transition-colors">
                                                        <i class="fa fa-paperclip"></i> Lihat Lampiran
                                                    </a>
                                                </div>
                                                @endif
                                            </div>
                                            @endforeach
                                        </div>
                                    @else
                                        {{-- Fallback untuk data lama --}}
                                        <p class="whitespace-pre-wrap text-sm">{{ $disp->balasan_teks ?: 'Tidak ada teks balasan.' }}</p>
                                        
                                        @if($disp->balasan_file)
                                        <div class="mt-2 pt-2 border-t border-indigo-100/50">
                                            <a href="{{ $disp->balasan_file }}" target="_blank" class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                                <i class="fa fa-paperclip"></i> Lihat Lampiran Balasan
                                            </a>
                                        </div>
                                        @endif
                                    @endif
                                    
                                    @if(!$isKadis && $disp->pengirim_id == $pejabat?->id && !$disp->is_validated_by_atasan)
                                    <div class="mt-4 pt-3 border-t border-indigo-200/60">
                                        <form action="{{ plugin_route('e-office.briefing.validasi', $disp->id) }}" method="POST" onsubmit="return confirm('Validasi dan teruskan respon ini ke pimpinan/atasan?')">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-emerald-700 hover:text-emerald-800 bg-emerald-100 px-3 py-1.5 rounded-lg border border-emerald-300 transition-colors">
                                                <i class="fa fa-check-circle mr-1"></i> Validasi & Teruskan ke Atasan
                                            </button>
                                        </form>
                                    </div>
                                    @elseif($disp->is_validated_by_atasan)
                                    <div class="mt-4 pt-3 border-t border-indigo-200/60">
                                        <span class="text-xs font-medium text-emerald-600"><i class="fa fa-check-circle mr-1"></i> Telah divalidasi dan diteruskan ke Atasan</span>
                                    </div>
                                    @endif
                                </div>
                                @else
                                <div class="bg-gray-100/50 p-3 rounded-xl border border-gray-200 border-dashed text-sm text-gray-500 italic flex items-center gap-2 mt-3">
                                    <i class="fa fa-lock text-gray-400"></i> Respon sedang direview oleh atasan langsung.
                                </div>
                                @endif
                            @endif

                            @if($disp->feedback_kadis)
                            <div class="bg-amber-50 p-3 rounded-xl border border-amber-100 text-sm text-gray-800">
                                <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider block mb-1"><i class="fa fa-commenting-o mr-1"></i> Feedback Pimpinan</span>
                                <p class="whitespace-pre-wrap">{{ $disp->feedback_kadis }}</p>
                            </div>
                            @endif
                            
                            {{-- Tombol Beri Feedback (Hanya Kadis) --}}
                            @if($isKadis && $disp->dibalas_pada && !$disp->feedback_kadis && $briefing->status !== 'completed')
                            <div x-data="{ open: false }">
                                <button @click="open = !open" class="text-xs font-medium text-amber-600 hover:text-amber-700"><i class="fa fa-reply mr-1"></i> Beri Feedback</button>
                                <div x-show="open" x-cloak class="mt-2">
                                    <form action="{{ plugin_route('e-office.briefing.feedback', $disp->id) }}" method="POST" class="flex gap-2">
                                        @csrf
                                        <input type="text" name="feedback_kadis" required placeholder="Tulis feedback..." class="flex-1 text-sm rounded-lg border-gray-200 py-1.5 px-3">
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
                        togglePenerima(id) {
                            if (this.penerima.includes(id)) {
                                this.penerima = this.penerima.filter(i => i !== id);
                            } else {
                                this.penerima.push(id);
                            }
                        },
                        selectAll: false,
                        toggleAll() {
                            const checkboxes = $el.querySelectorAll('.penerima-checkbox');
                            checkboxes.forEach(cb => {
                                cb.checked = this.selectAll;
                                const val = cb.value;
                                if (this.selectAll && !this.penerima.includes(val)) {
                                    this.penerima.push(val);
                                } else if (!this.selectAll && this.penerima.includes(val)) {
                                    this.penerima = this.penerima.filter(i => i !== val);
                                }
                            });
                        }
                    }" action="{{ plugin_route('e-office.briefing.teruskan', $briefing->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
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
                                            
                                            <div x-show="penerima.includes('{{ $pj->id }}')" class="p-3 space-y-3" style="display: none;">
                                                
                                                {{-- Tupoksi --}}
                                                <div class="bg-white p-2.5 rounded-lg border border-indigo-100">
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
                                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Pesan Khusus</label>
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

                // 3. Update Global Counters & Progress Bar
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
</script>
@endpush
@endsection
