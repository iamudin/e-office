@extends('e-office::layout.app', ['title' => 'Detail Surat Masuk'])

@section('content')
<div class="flex justify-between items-center mb-6">
    <h3 class="text-2xl font-semibold text-slate-800"><i class="fa fa-envelope mr-2 text-indigo-500"></i> Detail Surat Masuk</h3>
    <a href="{{ plugin_route('e-office.surat-masuk.index') }}" class="inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 transition-colors">
        <i class="fa fa-arrow-left mr-2"></i> Kembali
    </a>
</div>

<form action="{{ plugin_route('e-office.surat-masuk.disposisi', $data->id) }}" method="POST">
    @csrf
    @method('PUT')
    
        <div class="mb-6">
        <!-- Card Detail Surat -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h4 class="text-base font-semibold text-slate-800">Informasi Surat</h4>
                    @if(e_office_user()->is_kasubag() || e_office_user()->is_operator())
                        <button type="submit" name="cetak_disposisi" value="true" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors">
                            <i class="fa fa-print mr-1.5"></i> Cetak Disposisi
                        </button>
                    @endif
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
                        <div>
                            <p class="text-slate-500 mb-0.5">Surat Dari</p>
                            <p class="font-medium text-slate-900">{{ $data->surat_dari }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-0.5">Diterima Tanggal</p>
                            <p class="font-medium text-slate-900">{{ $data->tanggal_terima->translatedFormat('d F Y') }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-0.5">Nomor Surat</p>
                            <p class="font-medium text-slate-900">{{ $data->nomor_surat }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-0.5">Nomor Agenda</p>
                            <p class="font-medium text-slate-900">{{ $data->nomor_agenda }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-0.5">Tanggal Surat</p>
                            <p class="font-medium text-slate-900">{{ $data->tanggal_surat->translatedFormat('d F Y') }}</p>
                        </div>
                        <div>
                            <p class="text-slate-500 mb-0.5">Sifat</p>
                            <span class="inline-flex px-2 py-1 rounded-md text-xs font-medium bg-rose-50 text-rose-700 border border-rose-100">{{ $data->sifat }}</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <p class="text-slate-500 text-sm mb-1">Hal / Perihal</p>
                        <p class="text-slate-900 font-medium whitespace-pre-wrap">{{ $data->hal }}</p>
                    </div>
                </div>
            </div>

            
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: PDF Viewer -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Alert Untuk Kabid -->
            @if(e_office_user()->is_kabid() && $data->disposisis->count())
                <div class="bg-amber-50 border-l-4 border-amber-500 rounded-r-xl p-4 shadow-sm">
                    <div class="flex items-start">
                        <div class="flex-shrink-0"><i class="fa fa-info-circle text-amber-500 text-lg"></i></div>
                        <div class="ml-3">
                            <h3 class="text-sm font-bold text-amber-800">Instruksi Disposisi</h3>
                            <div class="mt-2 text-sm text-amber-700 space-y-2">
                                @php $others = $data->disposisis->where('pejabat_id', '<>', e_office_user()->pejabat->id); @endphp
                                @if($data->disposisis->count() == 1)
                                    <p class="font-medium">Surat ini HANYA diteruskan kepada Anda.</p>
                                @elseif($others->count())
                                    <p>Surat ini juga diteruskan kepada:</p>
                                    <ul class="list-disc pl-4 font-medium">
                                        @foreach($others as $row) <li>{{ $row->pejabat->nama }} ({{ $row->pejabat->jabatan }})</li> @endforeach
                                    </ul>
                                @endif
                                
                                <div class="mt-3 pt-3 border-t border-amber-200">
                                    <p class="font-bold">Tindakan Diharapkan:</p>
                                    <ul class="list-disc pl-4">
                                        @foreach($data->harapan as $row) <li>{{ $row }}</li> @endforeach
                                    </ul>
                                </div>
                                
                                @if($data->catatan)
                                <div class="mt-2">
                                    <p class="font-bold">Catatan Khusus:</p>
                                    <p class="bg-amber-100 p-2 rounded mt-1 italic">{{ $data->catatan }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            
            <!-- PDF Viewer -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden flex flex-col h-[600px] lg:h-[800px]">
                <div class="p-4 border-b border-slate-100 bg-rose-50/50 flex items-center justify-between">
                    <h4 class="font-semibold text-rose-800"><i class="fa fa-file-pdf-o mr-2 text-rose-500"></i> Dokumen Surat</h4>
                    <a href="{{ $data->file_surat }}" target="_blank" class="text-xs font-medium text-rose-600 hover:text-rose-800">Buka Tab Baru</a>
                </div>
                <div class="flex-1 bg-slate-100 relative min-h-[400px]">
                    @if(is_local())
                        <iframe src="{{ $data->file_surat }}" class="absolute inset-0 w-full h-full border-0"></iframe>
                    @else
                        @if($data->file_surat && media_exists($data->file_surat))
                            <iframe src="https://docs.google.com/gview?url={{ url($data->file_surat) }}&embedded=true" class="absolute inset-0 w-full h-full border-0"></iframe>
                        @else
                            <div class="absolute inset-0 flex items-center justify-center p-6 text-center">
                                <div class="bg-amber-50 text-amber-700 p-4 rounded-xl border border-amber-200">File surat tidak ditemukan atau telah dihapus.</div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            
        </div>

        <!-- Kolom Kanan: Timeline -->
        <div class="space-y-6">
            <!-- Timeline Tindak Lanjut -->
            @if(e_office_user()->is_kasubag() || e_office_user()->is_operator() || e_office_user()->is_kadis())
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden p-6">
                <h4 class="text-lg font-semibold text-slate-800 mb-6">Tindak Lanjut & Disposisi</h4>
                
                <div class="relative pl-8 space-y-8 before:absolute before:inset-y-0 before:left-3.5 before:w-0.5 before:bg-slate-200">
                    
                    <!-- Node 1: Disposisi Kadis -->
                    <div class="relative">
                        <div class="absolute -left-[35px] mt-1.5 w-4 h-4 rounded-full bg-indigo-500 ring-4 ring-indigo-50"></div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                            @if($data->sudah_diteruskan_kekadis())
                                @if($data->disposisis->count())
                                    <h5 class="font-semibold text-slate-800 mb-2">Disposisi Kepala Dinas</h5>
                                    <p class="text-sm text-slate-600 mb-3">Telah didisposisikan kepada:</p>
                                    <ul class="space-y-3 mb-4">
                                        @foreach($data->disposisis as $row)
                                            <li class="bg-white p-3 rounded-lg border border-slate-200 shadow-sm">
                                                <div class="flex justify-between items-start mb-2">
                                                    <span class="font-medium text-slate-900 text-sm">
                                                        {{ $row->pejabat->jabatan }}
                                                        @if(e_office_user()->pejabat->id == $row->pejabat->id) 
                                                            <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">ANDA</span>
                                                        @endif
                                                    </span>
                                                    <span class="text-xs text-slate-400">{{ $row->created_at->diffForHumans() }}</span>
                                                </div>
                                                
                                                @if(e_office_user()->pejabat->id == $row->pejabat->id)
                                                    @if($row->belum_dibalas())
                                                        <div class="mt-2 text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded inline-block mb-2">Belum ada balasan</div>
                                                        <div x-data="{ open: false }">
                                                            <button type="button" @click="open = !open" x-show="!open" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                                                <i class="fa fa-reply mr-1"></i> Tulis Balasan
                                                            </button>
                                                            <div x-show="open" class="mt-2 space-y-2">
                                                                <textarea name="catatan" rows="2" class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Tuliskan catatan tindak lanjut..."></textarea>
                                                                <button type="submit" name="respon_disposisi" value="true" class="px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700">Kirim Balasan</button>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="mt-2 bg-indigo-50 p-2.5 rounded-lg border border-indigo-100">
                                                            <p class="text-sm text-indigo-900"><i class="fa fa-reply text-indigo-400 mr-2"></i>{{ $row->catatan }}</p>
                                                            <p class="text-xs text-indigo-400 mt-1">{{ $row->dibalas_pada->diffForHumans() }}</p>
                                                        </div>
                                                    @endif
                                                @else
                                                    @if(!$row->belum_dibalas())
                                                        <div class="mt-2 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                                                            <p class="text-sm text-slate-700"><i class="fa fa-reply text-slate-400 mr-2"></i>{{ $row->catatan }}</p>
                                                            <p class="text-xs text-slate-400 mt-1">{{ $row->dibalas_pada->diffForHumans() }}</p>
                                                        </div>
                                                    @else
                                                        <div class="mt-2 text-xs text-slate-400 italic">Belum ada balasan</div>
                                                    @endif
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                    
                                    <div class="mb-3">
                                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Dengan Harapan</p>
                                        <ul class="list-disc list-inside text-sm text-slate-700 space-y-0.5">
                                            @foreach($data->harapan as $row) <li>{{ $row }}</li> @endforeach
                                        </ul>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Catatan Kadis</p>
                                        <p class="text-sm text-slate-700 bg-white p-2 rounded border border-slate-200">{{ $data->catatan ?? '-' }}</p>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-4 text-right">{{ $data->diteruskan_ke_kadis->diffForHumans() }}</p>
                                    
                                    @if(e_office_user()->is_kadis())
                                        <div class="mt-4 pt-4 border-t border-slate-200">
                                            <button type="submit" name="perbarui_dipsosisi" value="true" onclick="return confirm('Anda yakin untuk disposisi ulang?')" class="inline-flex items-center px-3 py-1.5 bg-amber-100 text-amber-700 text-xs font-medium rounded-lg hover:bg-amber-200">
                                                <i class="fa fa-edit mr-1.5"></i> Disposisi Ulang
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    @if(e_office_user()->is_kadis())
                                        <h5 class="font-semibold text-slate-800 mb-4">Teruskan surat ini kepada:</h5>
                                        <div class="space-y-3 mb-5">
                                            @foreach($pejabat as $row)
                                                <label class="flex items-center p-3 border border-slate-200 rounded-xl hover:bg-slate-50 cursor-pointer transition-colors bg-white">
                                                    <input type="checkbox" name="pejabat_id[]" value="{{ $row->id }}" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                                    <span class="ml-3 text-sm font-medium text-slate-700">{{ $row->nama }} <span class="text-slate-400 font-normal">({{ $row->jabatan }})</span></span>
                                                </label>
                                            @endforeach
                                        </div>
                                        
                                        <h5 class="font-semibold text-slate-800 mb-3 text-sm">Dengan Hormat harap:</h5>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
                                            @foreach(['Tanggapan dan Saran', 'Proses Lebih lanjut', 'Koordinasi / Konfirmasikan'] as $row)
                                                <label class="flex items-center p-2.5 border border-slate-200 rounded-lg hover:bg-slate-50 cursor-pointer bg-white">
                                                    <input type="checkbox" name="harapan[]" value="{{ $row }}" class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                                                    <span class="ml-2 text-sm text-slate-700">{{ $row }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="block text-sm font-semibold text-slate-800 mb-2">Catatan Kadis:</label>
                                            <textarea name="catatan" rows="3" class="w-full rounded-xl p-2 border border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Tuliskan instruksi atau catatan disini..."></textarea>
                                        </div>
                                        
                                        <button type="submit" name="kadis_meneruskan" value="1" class="px-5 py-2.5 bg-indigo-600 text-white font-medium text-sm rounded-xl hover:bg-indigo-700 shadow-sm shadow-indigo-600/20">
                                            Proses Disposisi <i class="fa fa-paper-plane ml-1.5"></i>
                                        </button>
                                    @else
                                        <div class="flex items-center text-amber-600 bg-amber-50 p-3 rounded-lg">
                                            <i class="fa fa-spinner fa-spin mr-3 text-lg"></i>
                                            <span class="text-sm font-medium">Menunggu persetujuan dan disposisi Kepala Dinas</span>
                                        </div>
                                    @endif
                                @endif
                            @else
                                <div class="text-sm text-slate-500 italic">Belum diteruskan ke Kepala Dinas.</div>
                            @endif
                        </div>
                    </div>

                    <!-- Node 2: Paraf Kasubag -->
                    <div class="relative">
                        <div class="absolute -left-[35px] mt-1.5 w-4 h-4 rounded-full {{ $data->sudah_paraf() ? 'bg-emerald-500 ring-4 ring-emerald-50' : 'bg-slate-300' }}"></div>
                        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm">
                            <h5 class="font-semibold text-slate-800 mb-1">Paraf Kasubag Umum</h5>
                            @if($data->sudah_paraf())
                                <p class="text-sm text-slate-600 mb-2">Kasubag telah memeriksa dan memberikan paraf persetujuan.</p>
                                <p class="text-xs text-slate-400">{{ $data->paraf_kasubagumum_pada->diffForHumans() }}</p>
                            @else
                                @if(e_office_user()->is_kasubag())
                                    <div class="mt-3 bg-slate-50 p-4 rounded-xl border border-slate-200">
                                        <label class="flex items-center mb-4 cursor-pointer">
                                            <input type="checkbox" name="paraf_kasubag" value="1" id="parafSurat" onchange="document.getElementById('btnTeruskan').style.display = this.checked ? 'block' : 'none';" class="w-5 h-5 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500">
                                            <span class="ml-3 font-medium text-slate-800">Berikan Paraf Surat Masuk</span>
                                        </label>
                                        <div id="btnTeruskan" style="display: none;">
                                            <button type="submit" name="teruskan_ke_kadis" value="1" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 shadow-sm shadow-emerald-600/20">
                                                Teruskan ke Kepala Dinas <i class="fa fa-arrow-right ml-1.5"></i>
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-2 flex items-center text-amber-600 text-sm font-medium">
                                        <i class="fa fa-spinner fa-spin mr-2"></i> Menunggu Paraf Kasubag Umum
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <!-- Node 3: Input Surat -->
                    <div class="relative">
                        <div class="absolute -left-[35px] mt-1.5 w-4 h-4 rounded-full bg-slate-400"></div>
                        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm">
                            <h5 class="font-semibold text-slate-800 mb-1">Surat Diterima & Diinput</h5>
                            <p class="text-sm text-slate-600 mb-2">Operator telah menginput surat ke sistem.</p>
                            <p class="text-xs text-slate-400">{{ $data->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Teruskan via WA (Kabid Only) -->
            @if(e_office_user()->is_kabid())
                @php $teruskan = $data->disposisis->where('pejabat_id', e_office_user()->pejabat->id)->first(); @endphp
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-emerald-50/50 flex items-center">
                        <h4 class="font-semibold text-emerald-800"><i class="fa fa-whatsapp mr-2 text-emerald-500 text-lg"></i> Teruskan ke WhatsApp</h4>
                    </div>
                    <div class="p-5">
                        @if($teruskan && $teruskan->teruskan_ke_whatsapp_pada)
                            <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4">
                                <p class="text-sm text-emerald-800 mb-2">Surat telah diteruskan kepada <strong>{{ $teruskan->wa_pejabat->nama }}</strong> ({{ $teruskan->wa_pejabat->jabatan }}).</p>
                                <p class="text-xs text-emerald-600 mb-3"><i class="fa fa-clock-o mr-1"></i> {{ $teruskan->teruskan_ke_whatsapp_pada->diffForHumans() }}</p>
                                <div class="bg-white p-2.5 rounded-lg border border-emerald-100 text-sm text-slate-700 italic">"{{ $teruskan->catatan }}"</div>
                            </div>
                        @else
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Pilih Staff Penerima</label>
                                    <select name="pejabat_id" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm">
                                        <option value="">-- Pilih Staff Bawah --</option>
                                        @foreach($staff as $row)
                                            <option value="{{ $row->id }}">{{ $row->nama }} ({{ $row->jabatan }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Instruksi Tambahan</label>
                                    <textarea name="pesan" rows="3" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm" placeholder="Ketik instruksi pesan WhatsApp..."></textarea>
                                </div>
                                <button type="submit" name="kirim_wa" value="1" class="w-full inline-flex justify-center items-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-xl shadow-sm shadow-emerald-600/20 text-white bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors">
                                    <i class="fa fa-paper-plane mr-2"></i> Kirim Notifikasi WA
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        
        </div>
    </div>
</form>
@endsection
