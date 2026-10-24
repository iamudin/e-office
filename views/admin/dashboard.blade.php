@extends('e-office::layout.app', ['title' => 'Dashboard'])

@section('content')

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{--              PERINGATAN PIMPINAN (WHATSAPP & DASHBOARD REMINDERS)      --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
@if(!empty($dashboardReminders) && count($dashboardReminders) > 0)
<div class="space-y-4 mb-6">
    @foreach($dashboardReminders as $reminder)
    <div class="bg-gradient-to-r from-rose-600 via-rose-700 to-red-800 rounded-2xl p-5 text-white shadow-lg border border-rose-500/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center shrink-0 text-2xl shadow-inner">
                <i class="fa {{ $reminder['icon'] ?? 'fa-bell' }} text-amber-300"></i>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-white text-rose-800 tracking-wider uppercase shadow-sm">
                        ⚠️ Peringatan Pimpinan
                    </span>
                    <span class="text-xs text-rose-100 font-semibold flex items-center gap-1">
                        <i class="fa fa-clock-o"></i> Batas Waktu: <strong class="text-white">{{ $reminder['deadline'] }} WIB</strong>
                    </span>
                </div>
                <h4 class="text-base sm:text-lg font-bold text-white mt-1">{{ $reminder['title'] }}</h4>
                <p class="text-xs sm:text-sm text-rose-100 mt-0.5 leading-relaxed">{!! $reminder['message'] !!}</p>
            </div>
        </div>
        <div class="shrink-0 flex items-center sm:self-center">
            <a href="{{ $reminder['action_url'] }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white text-rose-700 hover:bg-rose-50 font-bold text-xs sm:text-sm rounded-xl transition shadow-md hover:shadow-lg transform active:scale-95">
                <span>{{ $reminder['action_text'] }}</span>
                <i class="fa fa-arrow-right"></i>
            </a>
        </div>
    </div>
    @endforeach
</div>
@endif



<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    @php
        $stats = [];
        $user = e_office_user();
        $pejabat = $user?->pejabat;

        // Surat Masuk Stats
        if ($user && $user->canViewSuratMasuk()) {
            $stats[] = ['title' => 'Total Surat Masuk', 'value' => \App\Models\Plugins\EOffice\Arsip::count(), 'icon' => 'fa-envelope', 'color' => 'bg-blue-500'];
            $stats[] = ['title' => 'Surat Belum Diproses', 'value' => \App\Models\Plugins\EOffice\Arsip::whereNull('diteruskan_ke_kadis')->count(), 'icon' => 'fa-spinner', 'color' => 'bg-amber-500'];
            $stats[] = ['title' => 'Surat Selesai Disposisi', 'value' => \App\Models\Plugins\EOffice\Arsip::whereNotNull('disposisi_pada')->count(), 'icon' => 'fa-check-circle', 'color' => 'bg-emerald-500'];
        }

        // Briefing Stats
        if (e_briefing_enabled() && $pejabat) {
            $isKadis = str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis');
            if ($isKadis) {
                $stats[] = ['title' => 'Total Briefing Terbit', 'value' => \App\Models\Plugins\EOffice\Briefing::count(), 'icon' => 'fa-bullhorn', 'color' => 'bg-purple-500'];
                $stats[] = ['title' => 'Briefing Aktif', 'value' => \App\Models\Plugins\EOffice\Briefing::where('status', '!=', 'completed')->count(), 'icon' => 'fa-tasks', 'color' => 'bg-pink-500'];
                $stats[] = ['title' => 'Total Pejabat', 'value' => \App\Models\Plugins\EOffice\Pejabat::count(), 'icon' => 'fa-users', 'color' => 'bg-indigo-500'];
            } else {
                $stats[] = ['title' => 'Briefing/Tugas Baru', 'value' => \App\Models\Plugins\EOffice\BriefingDisposisi::where('penerima_id', $pejabat->id)->whereNull('dibaca_pada')->count(), 'icon' => 'fa-bell', 'color' => 'bg-rose-500'];
                $stats[] = ['title' => 'Total Tugas Diterima', 'value' => \App\Models\Plugins\EOffice\BriefingDisposisi::where('penerima_id', $pejabat->id)->count(), 'icon' => 'fa-tasks', 'color' => 'bg-purple-500'];
            }
        }
        
    @endphp
    @foreach($stats as $stat)
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center gap-4 transition-all hover:shadow-md">
        <div class="w-14 h-14 rounded-xl {{ $stat['color'] }} flex items-center justify-center shadow-inner">
            <i class="fa {{ $stat['icon'] }} text-white text-2xl"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500">{{ $stat['title'] }}</p>
            <h3 class="text-2xl font-bold text-slate-800">{{ $stat['value'] }}</h3>
        </div>
    </div>
    @endforeach
</div>

@if(e_office_user()?->is_operator() || e_office_user()?->is_kasubag())
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
        <h3 class="text-lg font-semibold text-slate-800"><i class="fa fa-print mr-2 text-indigo-500"></i> Cetak Rekapitulasi</h3>
        <p class="text-sm text-slate-500 mt-1">Cetak laporan agenda surat masuk berdasarkan periode tanggal.</p>
    </div>
    <div class="p-6">
        <form action="" method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1 w-full">
                <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai', date('Y-m-d')) }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-sm font-medium text-slate-700 mb-1">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir', date('Y-m-d')) }}" class="w-full rounded-xl border-slate-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
            </div>
            <button type="submit" name="cetak_rekap" value="true" class="w-full md:w-auto px-6 py-2.5 bg-indigo-600 text-white font-medium text-sm rounded-xl hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-600/20 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <i class="fa fa-print mr-2"></i> Cetak PDF
            </button>
        </form>
    </div>
</div>
@endif

<div class="grid grid-cols-1 {{ e_briefing_enabled() ? 'lg:grid-cols-2' : '' }} gap-6 mb-6">
    @if(e_office_user() && e_office_user()->canViewSuratMasuk())
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden {{ !e_briefing_enabled() ? 'col-span-full' : '' }}">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-slate-800"><i class="fa fa-history mr-2 text-indigo-500"></i> Riwayat Surat Masuk</h3>
                <p class="text-sm text-slate-500 mt-1">Daftar riwayat surat masuk keseluruhan.</p>
            </div>
        </div>
        <div class="p-6 overflow-x-auto">
            <table class="w-full datatable" style="width: 100%">
                <thead>
                    <tr>
                        <th class="text-center w-12">No</th>
                        <th>Pengirim</th>
                        <th>Tgl Surat</th>
                        <th>Nomor</th>
                        <th>Perihal</th>
                        <th>Diterima Tgl</th>
                        <th>Status</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    @endif

    @if(e_briefing_enabled())
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden {{ (e_office_user() && e_office_user()->canViewSuratMasuk()) ? '' : 'col-span-2' }}">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-slate-800"><i class="fa fa-bullhorn mr-2 text-purple-500"></i> Tugas & Briefing</h3>
                <p class="text-sm text-slate-500 mt-1">Daftar briefing terbaru terkait Anda.</p>
            </div>
            <a href="{{ plugin_route('e-office.briefing.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Lihat Semua <i class="fa fa-arrow-right ml-1"></i></a>
        </div>
        <div class="p-0 overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-slate-100">
                        <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tugas</th>
                        @if(!str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis'))
                        <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Dari</th>
                        @endif
                        <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php
                        $isKadis = str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis');
                        if ($isKadis) {
                            $latestBriefings = \App\Models\Plugins\EOffice\Briefing::with(['user.pejabat'])->orderBy('created_at', 'desc')->take(5)->get();
                        } else {
                            $latestBriefings = \App\Models\Plugins\EOffice\BriefingDisposisi::with('briefing', 'pengirim')->where('penerima_id', $pejabat?->id)->orderBy('created_at', 'desc')->take(5)->get();
                        }
                    @endphp
                    @forelse($latestBriefings as $item)
                        @php 
                            $briefing = $isKadis ? $item : $item->briefing; 
                            if(!$briefing) continue;
                            $statusColor = $briefing->status == 'completed' ? 'emerald' : ($briefing->status == 'in_progress' ? 'amber' : 'slate');
                            $statusText = $briefing->status == 'completed' ? 'Selesai' : ($briefing->status == 'in_progress' ? 'Diproses' : 'Menunggu');
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4">
                                <p class="text-sm font-medium text-gray-900">{{ $briefing->judul }}</p>
                                <p class="text-xs text-gray-500 mt-1"><i class="fa fa-clock-o mr-1"></i>{{ $briefing->created_at->diffForHumans() }}</p>
                            </td>
                            @if(!$isKadis)
                            <td class="p-4">
                                <p class="text-sm text-gray-900">{{ $item->pengirim->nama ?? '-' }}</p>
                            </td>
                            @endif
                            <td class="p-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $statusColor }}-50 text-{{ $statusColor }}-700 border border-{{ $statusColor }}-200/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $statusColor }}-500"></span>
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ plugin_route('e-office.briefing.show', $briefing->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-gray-200 text-gray-500 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isKadis ? 3 : 4 }}" class="p-6 text-center text-gray-500 text-sm">
                                Belum ada data briefing/tugas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ plugin_route('e-office.surat-masuk.riwayat') }}",
                    type: "POST",
                    data: function(d) {
                        d._token = "{{ csrf_token() }}";
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center'},
                    {data: 'surat_dari', name: 'surat_dari'},
                    {data: 'tanggal_surat', name: 'tanggal_surat'},
                    {data: 'nomor_surat', name: 'nomor_surat'},
                    {data: 'hal', name: 'hal'},
                    {data: 'tanggal_terima', name: 'tanggal_terima'},
                    {data: 'status', name: 'status'}
                ],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data yang tersedia",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    zeroRecords: "Tidak ditemukan data yang sesuai",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                },
                responsive: true
            });
        });
    </script>
@endpush
