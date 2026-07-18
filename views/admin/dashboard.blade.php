@extends('e-office::layout.app', ['title' => 'Dashboard'])

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    @php
        $stats = [
            ['title' => 'Total Surat Masuk', 'value' => \App\Models\Plugins\EOffice\Arsip::count(), 'icon' => 'fa-envelope', 'color' => 'bg-blue-500'],
            ['title' => 'Surat Belum Diproses', 'value' => \App\Models\Plugins\EOffice\Arsip::whereNull('diteruskan_ke_kadis')->count(), 'icon' => 'fa-spinner', 'color' => 'bg-amber-500'],
            ['title' => 'Surat Selesai Disposisi', 'value' => \App\Models\Plugins\EOffice\Arsip::whereNotNull('disposisi_pada')->count(), 'icon' => 'fa-check-circle', 'color' => 'bg-emerald-500'],
            ['title' => 'Total Pejabat', 'value' => \App\Models\Plugins\EOffice\Pejabat::count(), 'icon' => 'fa-users', 'color' => 'bg-indigo-500'],
        ];
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

@if(e_office_user()->is_operator() || e_office_user()->is_kasubag())
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

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
        <div>
            <h3 class="text-lg font-semibold text-slate-800"><i class="fa fa-history mr-2 text-indigo-500"></i> Riwayat Surat Masuk</h3>
            <p class="text-sm text-slate-500 mt-1">Daftar riwayat surat masuk keseluruhan.</p>
        </div>
    </div>
    <div class="p-6">
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
