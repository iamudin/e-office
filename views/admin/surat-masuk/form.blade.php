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
                    <input type="text" name="nomor_surat" value="{{ old('nomor_surat', $data->nomor_surat ?? '') }}" required class="w-full px-5 py-3 rounded-xl border border-slate-300 shadow-sm text-base text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Asal Instansi/Pengirim <span class="text-rose-500">*</span></label>
                    <input type="text" name="surat_dari" value="{{ old('surat_dari', $data->surat_dari ?? '') }}" required class="w-full px-5 py-3 rounded-xl border border-slate-300 shadow-sm text-base text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Surat <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_surat" value="{{ old('tanggal_surat', $data && $data->tanggal_surat ? $data->tanggal_surat->format('Y-m-d') : date('Y-m-d')) }}" required class="w-full px-5 py-3 rounded-xl border border-slate-300 shadow-sm text-base text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-colors">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Perihal <span class="text-rose-500">*</span></label>
                    <textarea name="hal" rows="3" required class="w-full px-5 py-3 rounded-xl border border-slate-300 shadow-sm text-base text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-colors">{{ old('hal', $data->hal ?? '') }}</textarea>
                </div>
            </div>

            <!-- Kolom Penerimaan -->
            <div class="space-y-5">
                <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b pb-2 mb-4">Detail Penerimaan</h4>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Terima <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_terima" value="{{ old('tanggal_terima', $data && $data->tanggal_terima ? $data->tanggal_terima->format('Y-m-d') : date('Y-m-d')) }}" required class="w-full px-5 py-3 rounded-xl border border-slate-300 shadow-sm text-base text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition-colors">
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
                        @foreach(['Sangat Segera', 'Segera', 'Rahasia','Biasa'] as $sifat)
                            <label class="inline-flex items-center">
                                <input type="radio" name="sifat" value="{{ strtoupper($sifat) }}" {{ (old('sifat', $data->sifat ?? '') == strtoupper($sifat)) ? 'checked' : '' }} required class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-slate-700">{{ $sifat }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">File Surat (PDF) @if(!$data) <span class="text-rose-500">*</span> @endif</label>
                    <div class="media-input-wrapper" style="{{ (isset($data) && $data->file_surat && media_exists($data->file_surat)) ? 'display:none;' : '' }}">
                        <input type="file" data-max-size="50000000" name="file_surat" accept="application/pdf" {{ (!$data || empty($data->file_surat)) ? 'required' : '' }} class="compress-image block w-full text-base text-slate-600 file:mr-4 file:py-3 file:px-5 file:rounded-xl file:border-0 file:text-base file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-colors rounded-xl py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    @if(isset($data) && $data->file_surat && media_exists($data->file_surat))
                        <div class="media-preview-wrapper mt-3">
                            <input type="hidden" name="file_surat" value="{{ $data->file_surat }}">
                            <div class="inline-flex items-center gap-3 px-4 py-2.5 bg-indigo-50/80 border border-indigo-200 rounded-xl text-xs font-semibold text-indigo-800 shadow-xs">
                                <i class="fa fa-file-pdf-o text-rose-500 text-lg"></i>
                                <a href="{{ $data->file_surat }}" target="_blank" class="hover:underline font-bold">{{ basename($data->file_surat) }}</a>
                                <button type="button" class="ml-2 text-rose-500 hover:text-rose-700 btn-remove-media cursor-pointer p-1" title="Hapus / Ganti File" onclick="$(this).closest('.media-preview-wrapper').remove(); $('.media-input-wrapper').show();">
                                    <i class="fa fa-trash text-sm"></i>
                                </button>
                            </div>
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
