@extends('earsip::layout.app', ['title' => 'Detail Briefing / Tugas'])

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
            
            @if($isKadis && $briefing->status !== 'completed')
            <div>
                <form action="{{ plugin_route('earsip.briefing.close', $briefing->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menutup/menyelesaikan tugas ini?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 transition-colors shadow-sm shadow-emerald-600/20">
                        <i class="fa fa-check-circle mr-1"></i> Tandai Selesai
                    </button>
                </form>
            </div>
            @endif
        </div>
        
        <div class="p-6 text-gray-700 whitespace-pre-wrap text-sm leading-relaxed">{{ $briefing->pesan }}</div>
        
        @if(!$isKadis && isset($myDisposisi))
            @if($myDisposisi->pesan_diteruskan)
            <div class="px-6 py-4 bg-amber-50/50 border-t border-amber-100/60">
                <h4 class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-2"><i class="fa fa-bullhorn mr-1"></i> Instruksi Tambahan Massal dari {{ $myDisposisi->pengirim->nama ?? 'Atasan' }}</h4>
                <div class="text-amber-900 whitespace-pre-wrap text-sm leading-relaxed">{{ $myDisposisi->pesan_diteruskan }}</div>
            </div>
            @endif
            
            @if($myDisposisi->pesan_khusus)
            <div class="px-6 py-4 bg-blue-50/50 border-t border-blue-100/60">
                <h4 class="text-xs font-bold text-blue-700 uppercase tracking-wider mb-2"><i class="fa fa-commenting mr-1"></i> Pesan Khusus Untuk Anda</h4>
                <div class="text-blue-900 whitespace-pre-wrap text-sm leading-relaxed">{{ $myDisposisi->pesan_khusus }}</div>
            </div>
            @endif
        @endif
        
        {{-- File Utama dari Pimpinan --}}
        @if($briefing->file_lampiran)
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600">
                        <i class="fa fa-file-pdf-o"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Lampiran Utama Pimpinan</p>
                        <p class="text-xs text-gray-500">File tersedia</p>
                    </div>
                </div>
                <a href="{{ $briefing->file_lampiran }}" target="_blank" class="px-4 py-2 text-sm font-medium text-indigo-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Unduh / Lihat File
                </a>
            </div>
        @endif

        {{-- File Tambahan dari Atasan Langsung --}}
        @if(!$isKadis && isset($myDisposisi) && $myDisposisi->file_lampiran && $myDisposisi->file_lampiran !== $briefing->file_lampiran)
            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center text-blue-600">
                        <i class="fa fa-file-text-o"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">Lampiran Khusus dari Atasan</p>
                        <p class="text-xs text-gray-500">File tambahan</p>
                    </div>
                </div>
                <a href="{{ $myDisposisi->file_lampiran }}" target="_blank" class="px-4 py-2 text-sm font-medium text-blue-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Unduh / Lihat File Khusus
                </a>
            </div>
        @endif
    </div>

    {{-- Checklist Progress --}}
    @if($briefing->checklists->count() > 0)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ceklis Kegiatan</h3>
        
        @if(!$isKadis && $myDisposisi && $briefing->status !== 'completed')
        <form action="{{ plugin_route('earsip.briefing.balas', $briefing->id) }}" method="POST" id="checklistForm">
            @csrf
            <div class="space-y-3">
                @foreach($briefing->checklists->filter(fn($c) => is_null($c->penerima_id) || $c->penerima_id == $pejabat?->id) as $chk)
                <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-100 {{ $chk->is_checked ? 'bg-emerald-50/30' : 'bg-gray-50/50 cursor-pointer hover:border-indigo-200' }}">
                    <input type="checkbox" name="checklists[{{ $chk->id }}]" value="1" {{ $chk->is_checked ? 'checked disabled' : '' }} class="mt-0.5 text-indigo-600 rounded focus:ring-indigo-500 w-4 h-4 border-gray-300">
                    <div class="flex-1">
                        <p class="text-sm font-medium {{ $chk->is_checked ? 'text-gray-500 line-through' : 'text-gray-800' }}">{{ $chk->kegiatan }}</p>
                        @if($chk->is_checked)
                            <p class="text-xs text-emerald-600 mt-1"><i class="fa fa-check mr-1"></i>Diselesaikan oleh {{ $chk->checkedByPejabat->nama ?? 'Seseorang' }} pada {{ $chk->checked_at->format('d M, H:i') }}</p>
                        @endif
                    </div>
                </label>
                @endforeach
            </div>
            <button type="submit" class="mt-4 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-600/20">
                Update Ceklis
            </button>
        </form>
        @else
        <div class="space-y-6">
            @php 
                $checklistsToView = $isKadis ? $briefing->checklists : $briefing->checklists->filter(fn($c) => is_null($c->penerima_id) || $c->penerima_id == $pejabat?->id); 
                $grouped = $checklistsToView->groupBy('penerima_id');
            @endphp
            
            @foreach($grouped as $penerimaId => $chks)
                <div class="mb-4">
                    @if($penerimaId == '')
                        <h4 class="text-sm font-bold text-gray-700 mb-2 border-b border-gray-100 pb-1"><i class="fa fa-globe mr-1 text-gray-400"></i> Tugas Massal (Untuk Semua)</h4>
                    @else
                        <h4 class="text-sm font-bold text-indigo-700 mb-2 border-b border-indigo-100 pb-1">
                            <i class="fa fa-user mr-1 text-indigo-400"></i> Tugas Khusus: {{ $chks->first()->penerima->nama ?? 'Bawahan' }}
                        </h4>
                    @endif
                    <div class="space-y-3">
                        @foreach($chks as $chk)
                        <div class="flex items-start gap-3 p-3 rounded-xl border border-gray-100 {{ $chk->is_checked ? 'bg-emerald-50/30' : 'bg-gray-50/50' }}">
                            <div class="mt-0.5 w-4 h-4 rounded-full flex items-center justify-center {{ $chk->is_checked ? 'bg-emerald-500 text-white' : 'border border-gray-300' }}">
                                @if($chk->is_checked)<i class="fa fa-check text-[10px]"></i>@endif
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium {{ $chk->is_checked ? 'text-gray-500 line-through' : 'text-gray-800' }}">{{ $chk->kegiatan }}</p>
                                @if($chk->is_checked)
                                    <p class="text-xs text-emerald-600 mt-1"><i class="fa fa-check mr-1"></i>Diselesaikan oleh {{ $chk->checkedByPejabat->nama ?? 'Seseorang' }} pada {{ $chk->checked_at->format('d M, H:i') }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        @endif
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
                        // Tampilkan semua alur disposisi agar struktur pohon (hirarki) tetap utuh
                        // Namun balasan spesifik akan disembunyikan jika belum divalidasi
                        $disposisisToView = $briefing->disposisis;
                        
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
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider block mb-1">Instruksi Massal</span>
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
                                    $canViewResponse = $disp->pengirim_id == $pejabat?->id || $disp->penerima_id == $pejabat?->id || $disp->is_validated_by_atasan;
                                @endphp
                                
                                @if($canViewResponse)
                                <div class="bg-indigo-50/50 p-3 rounded-xl border border-indigo-100 text-sm text-gray-800 mt-3">
                                    <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider block mb-1"><i class="fa fa-reply mr-1"></i> Balasan</span>
                                    <p class="whitespace-pre-wrap">{{ $disp->balasan_teks ?: 'Tidak ada teks balasan.' }}</p>
                                    
                                    @if($disp->balasan_file)
                                    <a href="{{ $disp->balasan_file}}" target="_blank" class="mt-2 inline-flex items-center gap-2 text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                        <i class="fa fa-paperclip"></i> Lihat Lampiran Balasan
                                    </a>
                                    @endif
                                    
                                    @if(!$isKadis && $disp->pengirim_id == $pejabat?->id && !$disp->is_validated_by_atasan)
                                    <div class="mt-4 pt-3 border-t border-indigo-200/60">
                                        <form action="{{ plugin_route('earsip.briefing.validasi', $disp->id) }}" method="POST" onsubmit="return confirm('Validasi dan teruskan respon ini ke pimpinan/atasan?')">
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
                                    <form action="{{ plugin_route('earsip.briefing.feedback', $disp->id) }}" method="POST" class="flex gap-2">
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
                <form action="{{ plugin_route('earsip.briefing.balas', $briefing->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
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
                    }" action="{{ plugin_route('earsip.briefing.teruskan', $briefing->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instruksi Tambahan Massal (Opsional)</label>
                            <textarea name="pesan_diteruskan" rows="3" class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 border resize-none" placeholder="Instruksi ini akan diteruskan ke semua bawahan yang dipilih..."></textarea>
                        </div>
                        
                        <div class="pt-2 border-t border-gray-100">
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

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush
@endsection
