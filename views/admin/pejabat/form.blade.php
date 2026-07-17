@extends('cms::backend.layout.app', ['title' => (isset($data) ? 'Edit' : 'Tambah') . ' User & Pejabat'])

@section('content')
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 font-weight-bold"><i class="fa fa-user text-primary mr-2"></i> {{ isset($data) ? 'Edit' : 'Tambah' }} Pejabat</h5>
            <small class="text-muted">Formulir untuk mendata pejabat/staff sekaligus akun loginnya.</small>
        </div>
        <a href="{{ route('earsip.pejabat.index') }}" class="btn btn-secondary btn-sm">
            <i class="fa fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    @include('earsip::admin.pejabat._form')

</div>
@endsection
