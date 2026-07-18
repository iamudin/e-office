@extends('e-office::layout.app', ['title' => 'Buat Briefing / Tugas Baru'])

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-indigo-50/50 p-6 border-b border-indigo-100 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-indigo-900">Buat Briefing Baru</h3>
                <p class="text-sm text-indigo-600 mt-1">Delegasikan tugas dengan instruksi massal maupun instruksi khusus per bawahan.</p>
            </div>
            <a href="{{ plugin_route('e-office.briefing.index') }}" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors">
                <i class="fa fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>

        <form x-data="{
            penerima: [],
            togglePenerima(id) {
                if (this.penerima.includes(id)) {
                    this.penerima = this.penerima.filter(i => i !== id);
                } else {
                    this.penerima.push(id);
                }
            }
        }" action="{{ plugin_route('e-office.briefing.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-8">
            @csrf
            
            <!-- BAGIAN INSTRUKSI MASSAL -->
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Judul Tugas <span class="text-rose-500">*</span></label>
                    <input type="text" name="judul" required class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 border outline-none" placeholder="Masukkan judul tugas...">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pesan / Instruksi Massal (Opsional)</label>
                        <textarea name="pesan_masal" rows="4" class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 border outline-none resize-none" placeholder="Instruksi ini akan ditujukan untuk semua bawahan yang dipilih..."></textarea>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File Lampiran Massal (Opsional)</label>
                            <input type="file" name="file_lampiran_masal" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all border border-gray-200 rounded-xl">
                        </div>
                    </div>
                </div>
            </div>

            <!-- BAGIAN INSTRUKSI PER BAWAHAN -->
            <div class="pt-6 border-t border-gray-200">
                <h4 class="text-base font-bold text-gray-900 mb-4"><i class="fa fa-users text-indigo-500 mr-2"></i> Delegasi Spesifik Bawahan</h4>
                
                @if($pejabats->count() > 0)
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        @foreach($pejabats as $pejabat)
                            <div class="bg-gray-50 border border-gray-200 rounded-2xl overflow-hidden transition-all hover:border-indigo-300">
                                <div class="bg-white p-4 border-b border-gray-200 flex items-start gap-3">
                                    <input type="checkbox" name="penerima_ids[]" value="{{ $pejabat->id }}" @change="togglePenerima('{{ $pejabat->id }}')" class="mt-1 text-indigo-600 rounded focus:ring-indigo-500 w-5 h-5 border-gray-300 cursor-pointer">
                                    <div>
                                        <h5 class="font-bold text-gray-900 leading-tight">{{ $pejabat->nama ?: 'Tanpa Nama' }}</h5>
                                        <p class="text-xs text-gray-500 mt-1">{{ $pejabat->jabatan ?? $pejabat->alias_jabatan }}</p>
                                    </div>
                                </div>
                                
                                <div x-show="penerima.includes('{{ $pejabat->id }}')" class="p-4 space-y-4" style="display: none;">
                                    
                                    {{-- Tupoksi --}}
                                    <div class="bg-white p-3 rounded-xl border border-indigo-100">
                                        <label class="block text-xs font-bold text-indigo-900 mb-2"><i class="fa fa-check-square-o mr-1"></i> Tugas Pokok & Fungsi</label>
                                        @if($pejabat->tupoksis->count() > 0)
                                            <div class="space-y-2 max-h-40 overflow-y-auto pr-2">
                                                @foreach($pejabat->tupoksis as $tp)
                                                    <label class="flex items-start gap-2 text-xs text-gray-700 cursor-pointer p-2 hover:bg-gray-50 rounded-lg">
                                                        <input type="checkbox" name="tupoksi_khusus[{{ $pejabat->id }}][]" value="{{ $tp->tugas_fungsi }}" class="mt-0.5 text-indigo-600 rounded focus:ring-indigo-500 w-3.5 h-3.5 border-gray-300">
                                                        <span>{{ $tp->tugas_fungsi }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-xs text-gray-500 italic">Tidak ada data tupoksi untuk pejabat ini.</p>
                                        @endif
                                    </div>

                                    {{-- Pesan Khusus --}}
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Pesan Khusus</label>
                                        <textarea name="pesan_khusus[{{ $pejabat->id }}]" rows="2" class="w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs py-2 px-3 border outline-none resize-none" placeholder="Instruksi spesifik..."></textarea>
                                    </div>
                                    
                                    {{-- File Khusus --}}
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">File Spesifik</label>
                                        <input type="file" name="file_khusus[{{ $pejabat->id }}]" class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 transition-all border border-gray-200 rounded-xl bg-white p-1">
                                    </div>
                                    
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-rose-50 border border-rose-100 rounded-xl p-6 text-center">
                        <i class="fa fa-users text-rose-300 fa-3x mb-3"></i>
                        <p class="text-rose-800 font-medium">Anda belum memiliki bawahan langsung yang terdaftar.</p>
                        <p class="text-rose-600 text-sm mt-1">Silakan atur struktur bawahan terlebih dahulu di menu Struktur Briefing.</p>
                    </div>
                @endif
            </div>

            <div class="pt-6 border-t border-gray-200 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl shadow-sm shadow-indigo-200 transition-all flex items-center">
                    <i class="fa fa-paper-plane mr-2"></i> Kirim Tugas
                </button>
            </div>

        </form>
    </div>
</div>
@push('scripts')
{{-- Alpine.js for interactive dynamic checklist inputs --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush

@endsection

