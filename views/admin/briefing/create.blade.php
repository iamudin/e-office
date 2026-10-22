@extends('e-office::layout.app', ['title' => 'Buat Briefing / Tugas Baru'])

@section('content')
<div class="max-w-full mx-auto" x-data="{
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
    }
}">
    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        {{-- Header Form --}}
        <div class="bg-gradient-to-r from-indigo-900 via-indigo-950 to-slate-900 p-6 sm:p-8 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-200 text-xs font-semibold mb-2">
                    <i class="fa fa-users"></i> Delegasi Tugas Bawahan Langsung
                </div>
                <h2 class="text-xl sm:text-2xl font-extrabold text-white">Buat Briefing / Arahan Tugas</h2>
                <p class="text-xs sm:text-sm text-slate-300 mt-1">
                    Berikan poin-poin instruksi terfokus untuk masing-masing bawahan 1 tingkat di bawah Anda.
                </p>
            </div>
            <a href="{{ plugin_route('e-office.briefing.index') }}" 
                class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-bold rounded-xl border border-white/20 transition-all inline-flex items-center gap-2 w-fit">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>

        <form action="{{ plugin_route('e-office.briefing.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-8">
            @csrf
            
            {{-- Pokok / Judul Briefing --}}
            <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Judul / Pokok Briefing <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <input type="text" name="judul" 
                    placeholder="Contoh: Arahan Tugas Operasional & Pelaporan Harian (Kosongkan jika ingin digenerate otomatis)" 
                    class="w-full rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-sm py-2.5 px-3.5 bg-white outline-none">
                <p class="text-[11px] text-slate-500 mt-1.5">
                    Jika dikosongkan, sistem akan otomatis membuat kode judul briefing harian (misal: <em>BRF-{{ date('Ymd') }}-001</em>).
                </p>
            </div>

            {{-- DAFTAR BAWAHAN & INSTRUKSI PER BAWAHAN --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <i class="fa fa-user-check text-indigo-600"></i> Pilih Bawahan & Input Poin Instruksi
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Centang bawahan 1 tingkat yang ditugaskan, kemudian masukkan poin-poin instruksi spesifik untuk bawahan tersebut.
                        </p>
                    </div>
                </div>
                
                @if($pejabats->count() > 0)
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        @foreach($pejabats as $pejabat)
                            <div class="border rounded-2xl overflow-hidden transition-all duration-200"
                                :class="penerima.includes('{{ $pejabat->id }}') ? 'border-indigo-400 bg-indigo-50/20 shadow-md' : 'border-slate-200 bg-slate-50/50'">
                                
                                {{-- Card Header: Checkbox & Pejabat Profile --}}
                                <div class="p-4 border-b flex items-start gap-3.5 transition-colors"
                                    :class="penerima.includes('{{ $pejabat->id }}') ? 'bg-white border-indigo-100' : 'bg-white/80 border-slate-200'">
                                    
                                    <input type="checkbox" name="penerima_ids[]" value="{{ $pejabat->id }}" 
                                        @change="togglePenerima('{{ $pejabat->id }}')" 
                                        class="mt-1 text-indigo-600 rounded focus:ring-indigo-500 w-5 h-5 border-slate-300 cursor-pointer flex-shrink-0">
                                    
                                    <div class="flex-1 cursor-pointer" @click="togglePenerima('{{ $pejabat->id }}')">
                                        <h4 class="font-bold text-slate-900 text-sm leading-tight">{{ $pejabat->nama ?: 'Tanpa Nama' }}</h4>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $pejabat->jabatan ?: ($pejabat->alias_jabatan ?: 'Bawahan Langsung') }}</p>
                                        @if($pejabat->nohp)
                                            <p class="text-[11px] text-slate-400 mt-0.5"><i class="fa fa-phone mr-1"></i>{{ $pejabat->nohp }}</p>
                                        @endif
                                    </div>

                                    <span class="text-xs px-2.5 py-1 rounded-full font-bold flex-shrink-0 transition-colors"
                                        :class="penerima.includes('{{ $pejabat->id }}') ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500'"
                                        x-text="penerima.includes('{{ $pejabat->id }}') ? 'Ditugaskan' : 'Belum Dipilih'">
                                    </span>
                                </div>
                                
                                {{-- Card Body: Input Dinamis Poin Instruksi & Opsi Tambahan --}}
                                <div x-show="penerima.includes('{{ $pejabat->id }}')" class="p-5 space-y-4 bg-white" style="display: none;">
                                    
                                    {{-- LIST INPUT DINAMIS POIN INSTRUKSI KHUSUS PER BAWAHAN --}}
                                    <div class="bg-indigo-50/40 p-4 rounded-2xl border border-indigo-100">
                                        <div class="flex items-center justify-between mb-2">
                                            <label class="text-xs font-bold text-indigo-950 flex items-center gap-1.5">
                                                <i class="fa fa-list-ol text-indigo-600"></i> Poin-poin Instruksi Kegiatan
                                            </label>
                                            <button type="button" @click="addInstruksiKhusus('{{ $pejabat->id }}')" 
                                                class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-xs transition-colors flex items-center gap-1">
                                                <i class="fa fa-plus"></i> Tambah Poin
                                            </button>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mb-3">
                                            Tuliskan instruksi yang wajib dikonfirmasi (<strong>Proses</strong> atau <strong>Selesai</strong>) oleh {{ $pejabat->nama }}.
                                        </p>

                                        <div class="space-y-2.5">
                                            <template x-for="(item, idx) in (instruksiKhusus['{{ $pejabat->id }}'] || [''])" :key="idx">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0" x-text="idx + 1"></span>
                                                    <input type="text" 
                                                        :name="'instruksi_khusus[{{ $pejabat->id }}][]'" 
                                                        x-model="instruksiKhusus['{{ $pejabat->id }}'][idx]" 
                                                        placeholder="Contoh: Rekap berkas dan kirimkan laporannya..." 
                                                        class="flex-1 rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs py-2 px-3 outline-none bg-white">
                                                    <button type="button" @click="removeInstruksiKhusus('{{ $pejabat->id }}', idx)" 
                                                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors flex-shrink-0" 
                                                        title="Hapus baris instruksi">
                                                        <i class="fa fa-trash-o text-sm"></i>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Tupoksi Bawaan --}}
                                    @if($pejabat->tupoksis->count() > 0)
                                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                                            <label class="block text-xs font-bold text-slate-700 mb-2">
                                                <i class="fa fa-check-square-o mr-1 text-indigo-600"></i> Tugas Pokok & Fungsi Terkait (Opsional)
                                            </label>
                                            <div class="space-y-2 max-h-36 overflow-y-auto pr-1 text-xs">
                                                @foreach($pejabat->tupoksis as $tp)
                                                    <label class="flex items-start gap-2 text-slate-700 cursor-pointer p-1.5 hover:bg-white rounded-lg transition">
                                                        <input type="checkbox" name="tupoksi_khusus[{{ $pejabat->id }}][]" value="{{ $tp->tugas_fungsi }}" 
                                                            class="mt-0.5 text-indigo-600 rounded focus:ring-indigo-500 w-3.5 h-3.5 border-slate-300">
                                                        <span>{{ $tp->tugas_fungsi }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Catatan / Pesan Khusus --}}
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan untuk Bawahan Ini (Opsional)</label>
                                        <textarea name="pesan_khusus[{{ $pejabat->id }}]" rows="2" 
                                            class="w-full rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 text-xs py-2 px-3 outline-none resize-none" 
                                            placeholder="Catatan atau pengingat khusus..."></textarea>
                                    </div>
                                    
                                    {{-- File Khusus --}}
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Lampiran File untuk Bawahan Ini (Opsional)</label>
                                        <input type="file" name="file_khusus[{{ $pejabat->id }}]" 
                                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition border border-slate-200 rounded-xl bg-white p-1">
                                    </div>
                                    
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-rose-50 border border-rose-100 rounded-2xl p-8 text-center">
                        <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3 text-2xl">
                            <i class="fa fa-users"></i>
                        </div>
                        <h4 class="text-sm font-bold text-rose-900">Belum memiliki bawahan langsung yang terdaftar.</h4>
                        <p class="text-xs text-rose-600 mt-1">Silakan atur struktur bawahan terlebih dahulu pada menu Struktur Pejabat.</p>
                    </div>
                @endif
            </div>

            <div class="pt-6 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">
                    <i class="fa fa-info-circle mr-1 text-indigo-500"></i> Pastikan memilih minimal satu bawahan untuk menerbitkan briefing.
                </span>
                <button type="submit" 
                    class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-2xl shadow-lg shadow-indigo-600/25 transition-all flex items-center gap-2">
                    <i class="fa fa-paper-plane"></i> Kirim Arahan Tugas
                </button>
            </div>

        </form>
    </div>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush

@endsection
