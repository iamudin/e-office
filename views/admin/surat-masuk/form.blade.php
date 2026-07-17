@extends('e-office::layout.app', ['title' => (isset($data) ? 'Edit' : 'Tambah') . ' Surat Masuk'])

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold text-slate-800"><i class="fa fa-envelope mr-2 text-indigo-500"></i> {{ isset($data) ? 'Edit' : 'Tambah' }} Surat Masuk</h3>
            <p class="text-sm text-slate-500 mt-1">Formulir untuk mendata surat masuk baru.</p>
        </div>
        <a href="{{ plugin_route('e-office.surat-masuk.index') }}" class="inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
            <i class="fa fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ isset($data) ? plugin_route('e-office.surat-masuk.update', $data->id) : plugin_route('e-office.surat-masuk.store') }}" enctype="multipart/form-data" class="p-6">
        @csrf
        @if(isset($data)) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Kolom Info Surat -->
            <div class="space-y-5">
                <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b pb-2 mb-4">Informasi Surat</h4>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nomor Surat <span class="text-rose-500">*</span></label>
                    <input type="text" name="nomor_surat" value="{{ old('nomor_surat', $data->nomor_surat ?? '') }}" required class="w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Asal Instansi/Pengirim <span class="text-rose-500">*</span></label>
                    <input type="text" name="surat_dari" value="{{ old('surat_dari', $data->surat_dari ?? '') }}" required class="w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Surat <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_surat" value="{{ old('tanggal_surat', $data && $data->tanggal_surat ? $data->tanggal_surat->format('Y-m-d') : date('Y-m-d')) }}" required class="w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Perihal <span class="text-rose-500">*</span></label>
                    <textarea name="hal" rows="3" required class="w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('hal', $data->hal ?? '') }}</textarea>
                </div>
            </div>

            <!-- Kolom Penerimaan -->
            <div class="space-y-5">
                <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b pb-2 mb-4">Detail Penerimaan</h4>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Terima <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_terima" value="{{ old('tanggal_terima', $data && $data->tanggal_terima ? $data->tanggal_terima->format('Y-m-d') : date('Y-m-d')) }}" required class="w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nomor Agenda</label>
                    <div class="inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl border border-indigo-100 font-bold text-xl">
                        {{ $data->nomor_agenda ?? $noagenda }}
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sifat Surat <span class="text-rose-500">*</span></label>
                    <div class="flex flex-wrap gap-4">
                        @foreach(['Sangat Segera', 'Segera', 'Rahasia'] as $sifat)
                            <label class="inline-flex items-center">
                                <input type="radio" name="sifat" value="{{ strtoupper($sifat) }}" {{ (old('sifat', $data->sifat ?? '') == strtoupper($sifat)) ? 'checked' : '' }} required class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-slate-700">{{ $sifat }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">File Surat (PDF) @if(!$data) <span class="text-rose-500">*</span> @endif</label>
                    <input type="file" name="file_surat" accept="application/pdf" {{ !$data ? 'required' : '' }} class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-colors border border-slate-200 rounded-xl">
                    
                    @if(isset($data) && $data->file_surat && media_exists($data->file_surat))
                        <div class="mt-3">
                            <a href="{{ $data->file_surat }}" target="_blank" class="inline-flex items-center px-3 py-1.5 border border-rose-200 text-xs font-medium rounded-lg text-rose-700 bg-rose-50 hover:bg-rose-100 transition-colors">
                                <i class="fa fa-file-pdf-o mr-1.5"></i> Lihat Surat Saat Ini
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-8 pt-5 border-t border-slate-100 flex justify-end">
            <button type="submit" class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-xl shadow-sm shadow-indigo-600/20 text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                <i class="fa fa-save mr-2"></i> Simpan Surat Masuk
            </button>
        </div>
    </form>
</div>
@endsection
