@extends('e-office::layout.app', ['title' => 'Surat Masuk'])

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h3 class="text-lg font-semibold text-slate-800"><i class="fa fa-envelope mr-2 text-indigo-500"></i> Surat Masuk</h3>
            <p class="text-sm text-slate-500 mt-1">Daftar semua surat masuk dan status disposisi.</p>
        </div>
        @if (e_office_user()?->pejabat?->alias_jabatan == 'OPERATOR')
            <a href="{{ plugin_route('e-office.surat-masuk.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                <i class="fa fa-plus mr-2"></i> Tambah Surat
            </a>
        @endif
    </div>
    
    <div class="p-6 border-b border-slate-100">
        <nav class="flex space-x-4" aria-label="Tabs">
            @php $isSelesai = request()->segment(2) === 'selesai'; @endphp
            <a href="{{ plugin_route('e-office.surat-masuk.index') }}" 
               class="px-4 py-2 text-sm font-medium rounded-lg {{ !$isSelesai ? 'bg-indigo-100 text-indigo-700' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100' }} transition-colors">
               <i class="fa fa-download mr-2"></i> Baru
            </a>
            <a href="{{ plugin_route('e-office.surat-masuk.index.selesai') }}" 
               class="px-4 py-2 text-sm font-medium rounded-lg {{ $isSelesai ? 'bg-indigo-100 text-indigo-700' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100' }} transition-colors">
               <i class="fa fa-mail-forward mr-2"></i> Sudah Diproses
            </a>
        </nav>
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
                    <th class="text-center w-24">Aksi</th>
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
                    url: "{{ plugin_route('e-office.surat-masuk.datatable') }}",
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
                    {data: 'status', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
                ],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data yang tersedia",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    zeroRecords: "Tidak ditemukan data yang sesuai",
                    paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
                },
                responsive: true
            });
        });
    </script>
@endpush
