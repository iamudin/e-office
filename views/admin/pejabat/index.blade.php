@extends('cms::backend.layout.app', ['title' => 'Akses User & Pejabat'])

@section('content')
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 font-weight-bold"><i class="fa fa-users text-primary mr-2"></i> Daftar User & Pejabat</h5>
                <small class="text-muted">Kelola data pejabat struktural dan akses sistem arsip.</small>
            </div>
            <a href="{{ route('earsip.pejabat.create') }}" class="btn btn-primary">
                <i class="fa fa-plus mr-1"></i> Tambah Pejabat
            </a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover datatable w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">No</th>
                            <th>Nama User</th>
                            <th>Jabatan</th>
                            <th>Level Akses</th>
                            <th>No. HP (WA)</th>
                            <th>Status User</th>
                            <th class="text-center" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    {{datatable_asset('style')}}

@endpush

@push('scripts')
    {{datatable_asset('js')}}

    <script>
        $(document).ready(function () {
            $('.datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('earsip.pejabat.datatable') }}",
                    type: "POST",
                    data: function (d) {
                        d._token = "{{ csrf_token() }}";
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'nama', name: 'nama' },
                    { data: 'jabatan', name: 'jabatan' },
                    { data: 'alias', name: 'alias_jabatan' },
                    { data: 'nohp', name: 'nohp' },
                    { data: 'status', name: 'user.status' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
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