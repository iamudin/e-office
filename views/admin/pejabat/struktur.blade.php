@extends('cms::backend.layout.app', ['title' => 'Struktur Briefing'])

@push('styles')
<style>
    /* Nestable CSS */
    .dd { position: relative; display: block; margin: 0; padding: 0; list-style: none; font-size: 13px; line-height: 20px; }
    .dd-list { display: block; position: relative; margin: 0; padding: 0; list-style: none; }
    .dd-list .dd-list { padding-left: 30px; }
    .dd-collapsed .dd-list { display: none; }
    .dd-item, .dd-empty, .dd-placeholder { display: block; position: relative; margin: 0; padding: 0; min-height: 20px; font-size: 13px; line-height: 20px; }
    .dd-handle { display: block; text-decoration: none; font-weight: bold; }
    .dd-collapsed .dd-list { display: none !important; }
    
    .dd-item, .dd-empty, .dd-placeholder { display: block; position: relative; margin: 0; padding: 0; min-height: 20px; font-size: 13px; line-height: 20px; }
    .dd-handle { display: block; text-decoration: none; font-weight: bold; }
    
    /* Tombol Kustom Collapse/Expand */
    .my-collapse-btn {
        position: absolute;
        left: 5px;
        top: 13px;
        z-index: 20;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 0;
        outline: none !important;
        box-shadow: none !important;
        color: #6c757d;
        font-size: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        transition: color 0.2s;
    }
    .my-collapse-btn:hover {
        color: #4f46e5;
    }

    .dd-placeholder, .dd-empty { margin: 5px 0; padding: 0; min-height: 30px; background: #f8f9fa; border: 1px dashed #adb5bd; border-radius: 0.25rem; }
    .dd-empty { border: 1px dashed #bbb; min-height: 100px; background-color: #e5e5e5; background-image: -webkit-linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff), -webkit-linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff); background-image: -moz-linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff), -moz-linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff); background-image: linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff), linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff); background-size: 60px 60px; background-position: 0 0, 30px 30px; }
    .dd-dragel { position: absolute; pointer-events: none; z-index: 9999; }
    .dd-dragel > .dd-item .dd-handle { margin-top: 0; }
    .dd-dragel .dd-handle { box-shadow: 2px 4px 6px 0 rgba(0,0,0,.1); }
</style>
@endpush

@section('content')
<div class="row">
    
    {{-- Form Atur Bawahan (Manual Fallback) --}}
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0 font-weight-bold"><i class="fa fa-sitemap text-primary mr-2"></i> Atur Manual</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">Anda juga bisa mengatur atasan secara manual melalui form ini atau dengan cara **Drag and Drop** pada daftar di samping.</p>

                <form action="{{ route('e-office.struktur-briefing.update') }}" method="POST">
                    @csrf
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small">Pilih Pejabat (Bawahan) <span class="text-danger">*</span></label>
                        <select name="pejabat_id" required class="form-control">
                            <option value="">-- Pilih Pejabat --</option>
                            @foreach($semuaPejabat as $pj)
                                <option value="{{ $pj->id }}">{{ $pj->nama ?? 'Tanpa Nama' }} ({{ $pj->jabatan ?? $pj->alias_jabatan }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-weight-bold small">Pilih Atasan</label>
                        <select name="atasan_id" class="form-control">
                            <option value="">-- Tidak Ada Atasan (Top Level) --</option>
                            @foreach($semuaPejabat as $pj)
                                <option value="{{ $pj->id }}">{{ $pj->nama ?? 'Tanpa Nama' }} ({{ $pj->jabatan ?? $pj->alias_jabatan }})</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa fa-save mr-1"></i> Simpan Struktur
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Visualisasi Hirarki (Drag & Drop) --}}
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold"><i class="fa fa-arrows text-primary mr-2"></i> Drag & Drop Struktur Hierarki</h5>
                <span id="save-status" class="badge badge-success d-none p-2">Tersimpan</span>
            </div>
            <div class="card-body bg-light">
                <p class="text-muted small mb-3">Geser (Drag & Drop) item di bawah ini untuk mengatur bawahan dan atasan secara otomatis.</p>
                
                @if($semuaPejabat->count() > 0)
                    <div class="dd" id="nestable">
                        <ol class="dd-list">
                            @foreach($pejabats as $top)
                                @include('e-office::admin.pejabat._hirarki', ['pejabat' => $top, 'level' => 0])
                            @endforeach
                            
                            {{-- Tampilkan juga pejabat yang mungkin tidak punya atasan tapi tidak ter-render jika staff-nya bermasalah (fallback) --}}
                            @foreach($semuaPejabat as $p)
                                @if(is_null($p->atasan_id) && !$pejabats->contains('id', $p->id))
                                    @include('e-office::admin.pejabat._hirarki', ['pejabat' => $p, 'level' => 0])
                                @endif
                            @endforeach
                        </ol>
                    </div>
                @else
                    <div class="text-center text-muted p-4">
                        <i class="fa fa-folder-open-o fa-2x mb-2"></i>
                        <p class="mb-0">Belum ada data pejabat.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal Manage Tupoksi --}}
<div class="modal fade" id="tupoksiModal" tabindex="-1" role="dialog" aria-labelledby="tupoksiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold" id="tupoksiModalLabel"><i class="fa fa-tasks text-info mr-2"></i> Tugas & Fungsi: <span id="tupoksiPejabatNama"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="currentPejabatId">
                <form id="formAddTupoksi" class="mb-4">
                    <div class="input-group">
                        <input type="text" id="inputTugasFungsi" class="form-control" placeholder="Masukkan tugas & fungsi baru..." required>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fa fa-plus"></i> Tambah</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="bg-white">
                            <tr>
                                <th width="5%">No</th>
                                <th>Tugas Pokok & Fungsi</th>
                                <th width="15%" class="text-center"><i class="fa fa-cog"></i></th>
                            </tr>
                        </thead>
                        <tbody id="tupoksiListBody">
                            <tr>
                                <td colspan="3" class="text-center text-muted">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Edit Pejabat --}}
<div class="modal fade" id="editPejabatModal" tabindex="-1" role="dialog" aria-labelledby="editPejabatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold" id="editPejabatModalLabel"><i class="fa fa-user text-primary mr-2"></i> Edit Pejabat</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div id="editPejabatModalBody">
                <div class="p-5 text-center text-muted"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat form...</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/nestable2/1.6.0/jquery.nestable.min.js"></script>
<script>
$(document).ready(function() {
    var $nestable = $('#nestable');
    
    $nestable.nestable({
        maxDepth: 5, // Maksimal 5 level bawahan
        expandBtnHTML: '',   // Matikan tombol bawaan Nestable
        collapseBtnHTML: ''  // Matikan tombol bawaan Nestable
    }).on('change', function() {
        var dataStr = window.JSON.stringify($nestable.nestable('serialize'));
        
        // Tampilkan loading/menyimpan status
        $('#save-status').removeClass('d-none badge-success badge-danger').addClass('badge-warning').text('Menyimpan...').show();
        
        $.ajax({
            url: '{{ route("e-office.struktur-briefing.update-ajax") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                data: dataStr
            },
            success: function(res) {
                if(res.success) {
                    $('#save-status').removeClass('badge-warning').addClass('badge-success').text('Tersimpan!');
                    setTimeout(function() {
                        $('#save-status').fadeOut();
                    }, 2000);
                }
            },
            error: function() {
                $('#save-status').removeClass('badge-warning').addClass('badge-danger').text('Gagal Menyimpan!');
            }
        });
    });

    // Custom Javascript untuk Expand/Collapse 100% jalan
    $(document).on('click', '.my-collapse-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var item = btn.closest('.dd-item');
        var list = item.children('.dd-list');
        var icon = btn.find('i');
        
        if (btn.attr('data-myaction') === 'collapse') {
            list.slideUp(250); // Animasi smooth nutup
            btn.attr('data-myaction', 'expand');
            icon.removeClass('fa-minus-square').addClass('fa-plus-square');
        } else {
            list.slideDown(250); // Animasi smooth buka
            btn.attr('data-myaction', 'collapse');
            icon.removeClass('fa-plus-square').addClass('fa-minus-square');
        }
    });

    // Handle form submit for Add Tupoksi
    $('#formAddTupoksi').submit(function(e) {
        e.preventDefault();
        var pejabatId = $('#currentPejabatId').val();
        var tugasFungsi = $('#inputTugasFungsi').val();
        
        if(!tugasFungsi.trim()) return;

        var btn = $(this).find('button[type="submit"]');
        var originalText = btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled', true);

        var url = '{{ route("e-office.pejabat.tupoksi.store", ["pejabatId" => ":id"]) }}'.replace(':id', pejabatId);

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                tugas_fungsi: tugasFungsi
            },
            success: function(res) {
                if(res.success) {
                    $('#inputTugasFungsi').val('');
                    loadTupoksi(pejabatId);
                }
            },
            error: function() {
                alert('Gagal menambahkan tugas & fungsi');
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
});

function editPejabat(id) {
    $('#editPejabatModalBody').html('<div class="p-5 text-center text-muted"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat form...</div>');
    $('#editPejabatModal').modal('show');
    
    var url = '{{ route("e-office.pejabat.edit", ["pejabat" => ":id"]) }}'.replace(':id', id);
    
    $.ajax({
        url: url,
        type: 'GET',
        success: function(res) {
            $('#editPejabatModalBody').html(res);
        },
        error: function() {
            $('#editPejabatModalBody').html('<div class="p-5 text-center text-danger">Gagal memuat form. Silakan coba lagi.</div>');
        }
    });
}

function manageTupoksi(pejabatId, nama) {
    $('#tupoksiPejabatNama').text(nama);
    $('#currentPejabatId').val(pejabatId);
    $('#tupoksiModal').modal('show');
    loadTupoksi(pejabatId);
}

function loadTupoksi(pejabatId) {
    $('#tupoksiListBody').html('<tr><td colspan="3" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Memuat data...</td></tr>');
    
    var url = '{{ route("e-office.pejabat.tupoksi.get", ["pejabatId" => ":id"]) }}'.replace(':id', pejabatId);

    $.ajax({
        url: url,
        type: 'GET',
        success: function(data) {
            var html = '';
            if(data.length === 0) {
                html = '<tr><td colspan="3" class="text-center text-muted">Belum ada tugas & fungsi</td></tr>';
            } else {
                data.forEach(function(item, index) {
                    html += `<tr>
                        <td class="text-center">${index + 1}</td>
                        <td>${item.tugas_fungsi}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteTupoksi('${item.id}', '${pejabatId}')">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                });
            }
            $('#tupoksiListBody').html(html);
        }
    });
}

function deleteTupoksi(id, pejabatId) {
    if(confirm('Yakin ingin menghapus tugas & fungsi ini?')) {
        var url = '{{ route("e-office.pejabat.tupoksi.delete", ["id" => ":id"]) }}'.replace(':id', id);
        
        $.ajax({
            url: url,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if(res.success) {
                    loadTupoksi(pejabatId);
                }
            },
            error: function() {
                alert('Gagal menghapus');
            }
        });
    }
}
</script>
@endpush
