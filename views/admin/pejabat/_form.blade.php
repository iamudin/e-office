@php $errors = $errors ?? new \Illuminate\Support\ViewErrorBag; @endphp
<form id="formPejabat" method="POST" action="{{ isset($data) ? route('e-office.pejabat.update', $data->id) : route('e-office.pejabat.store') }}" class="mb-0 {{ isset($is_modal) ? 'd-flex flex-column flex-grow-1 overflow-hidden' : '' }}" style="{{ isset($is_modal) ? 'min-height: 0;' : '' }}">
    @csrf
    @if(isset($data)) @method('PUT') @endif
    
    <div class="{{ !isset($is_modal) ? 'card-body' : 'modal-body' }}" style="{{ isset($is_modal) ? 'overflow-y: auto;' : '' }}">
        
        {{-- Alert Error Server-side (Blade Session) --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <h6 class="font-weight-bold mb-2"><i class="fa fa-exclamation-triangle mr-2"></i> Terdapat kesalahan pada pengisian form:</h6>
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        {{-- Alert Error Client-side / AJAX --}}
        <div class="alert alert-danger alert-dismissible fade show mb-4 d-none form-ajax-errors" role="alert">
            <h6 class="font-weight-bold mb-2"><i class="fa fa-exclamation-triangle mr-2"></i> Gagal menyimpan! Periksa kesalahan berikut:</h6>
            <ul class="mb-0 pl-3 form-ajax-error-list"></ul>
            <button type="button" class="close" onclick="$(this).closest('.form-ajax-errors').addClass('d-none')" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="row">
            <!-- Kolom Detail Pejabat -->
            <div class="col-md-6">
                <h6 class="font-weight-bold text-uppercase border-bottom pb-2 mb-3">Informasi Pribadi & Jabatan</h6>
                
                <div class="form-group">
                    <label class="font-weight-bold">Nama Lengkap (beserta gelar) <span class="text-danger">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama', $data->nama ?? '') }}" required class="form-control @error('nama') is-invalid @enderror">
                    @error('nama')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="invalid-feedback error-nama d-none"></div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">NIP</label>
                        <input type="text" name="nip" value="{{ old('nip', $data->nip ?? '') }}" class="form-control @error('nip') is-invalid @enderror" pattern="[0-9 ]+" oninput="this.value=this.value.replace(/[^0-9 ]/g,'');" title="NIP hanya boleh berisi angka dan spasi">
                        @error('nip')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback error-nip d-none"></div>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Nomor HP (WhatsApp) <span class="text-danger">*</span></label>
                        <input type="text" name="nohp" value="{{ old('nohp', $data->nohp ?? '') }}" placeholder="628xxxx / 08xxxx" required class="form-control @error('nohp') is-invalid @enderror" pattern="^(08|628)[0-9]*$" oninput="this.value=this.value.replace(/[^0-9]/g,'');" title="Nomor HP harus berupa angka dan diawali dengan 08 atau 628">
                        @error('nohp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback error-nohp d-none"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Nama Jabatan Tertulis <span class="text-danger">*</span></label>
                    <input type="text" name="jabatan" value="{{ old('jabatan', $data->jabatan ?? '') }}" placeholder="Contoh: Kepala Dinas / Staff Administrasi" required class="form-control @error('jabatan') is-invalid @enderror">
                    @error('jabatan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="invalid-feedback error-jabatan d-none"></div>
                </div>
                
                <div class="form-group">
                    <label class="font-weight-bold">Pangkat & Golongan</label>
                    <input type="text" name="pangkat_golongan" value="{{ old('pangkat_golongan', $data->pangkat_golongan ?? '') }}" class="form-control @error('pangkat_golongan') is-invalid @enderror">
                    @error('pangkat_golongan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="invalid-feedback error-pangkat_golongan d-none"></div>
                </div>
                
                <div class="form-group">
                    <label class="font-weight-bold">Alias / Role Jabatan <span class="text-danger">*</span></label>
                    <select name="alias_jabatan" required class="form-control @error('alias_jabatan') is-invalid @enderror">
                        <option value="">-- Pilih Alias --</option>
                        @foreach(['KADIS','KASUBAGUMUM','KABID','SEKRETARIS','OPERATOR','STAFF'] as $alias)
                            <option value="{{ $alias }}" {{ (old('alias_jabatan', $data->alias_jabatan ?? '') == $alias) ? 'selected' : '' }}>{{ $alias }}</option>
                        @endforeach
                    </select>
                    @error('alias_jabatan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="invalid-feedback error-alias_jabatan d-none"></div>
                    <small class="text-muted">Alias ini menentukan hak akses menu dan alur surat dalam sistem.</small>
                </div>
            </div>

            <!-- Kolom Akun & Hierarki -->
            <div class="col-md-6">
                <h6 class="font-weight-bold text-uppercase border-bottom pb-2 mb-3">Informasi Akun Login</h6>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Username Login <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" id="username_input" name="username" value="{{ old('username', $data->user->username ?? '') }}" required class="form-control @error('username') is-invalid @enderror" autocomplete="off" pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan garis bawah (_)" minlength="4" onkeydown="return event.keyCode !== 32;" oninput="this.value = this.value.replace(/\s/g, '')">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateUsername()" title="Generate Username"><i class="fa fa-magic"></i></button>
                            </div>
                        </div>
                        @error('username')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback error-username d-none"></div>
                        <small class="text-muted">Min 4 karakter, tanpa spasi.</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $data->user->email ?? '') }}" required class="form-control @error('email') is-invalid @enderror" autocomplete="off">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback error-email d-none"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Password @if(!isset($data)) <span class="text-danger">*</span> @endif</label>
                    <div class="input-group">
                        <input type="password" id="password_input" name="password" {{ !isset($data) ? 'required' : '' }} class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="6" pattern="^\S+$" title="Tidak boleh mengandung spasi" onkeydown="return event.keyCode !== 32;" oninput="this.value = this.value.replace(/\s/g, '')">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()" title="Lihat Password"><i class="fa fa-eye" id="toggle_icon"></i></button>
                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()" title="Generate Password"><i class="fa fa-magic"></i></button>
                        </div>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="invalid-feedback error-password d-none"></div>
                    @if(isset($data))
                        <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password. Min 6 karakter.</small>
                    @else
                        <small class="text-muted">Min 6 karakter.</small>
                    @endif
                </div>

                @if(isset($data))
                <div class="form-group">
                    <label class="font-weight-bold">Status Akun</label>
                    <select name="status" class="form-control @error('status') is-invalid @enderror">
                        <option value="active" {{ (old('status', $data->user->status ?? '') == 'active') ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ (old('status', $data->user->status ?? '') == 'inactive') ? 'selected' : '' }}>Non-Aktif</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="invalid-feedback error-status d-none"></div>
                </div>
                @endif

                <h6 class="font-weight-bold text-uppercase border-bottom pb-2 mb-3 mt-4">Hierarki & Alur Surat</h6>

                <div class="form-group form-check border rounded p-3 bg-light">
                    <input type="checkbox" name="penerima_disposisi" id="penerima_disposisi" value="1" {{ old('penerima_disposisi', $data->penerima_disposisi ?? 0) ? 'checked' : '' }} class="form-check-input ml-1">
                    <label class="form-check-label ml-4 font-weight-bold" for="penerima_disposisi">Terima Disposisi dari KADIS?</label>
                    <small class="d-block text-muted ml-4 mt-1">Centang jika pejabat ini termasuk dalam pilihan tujuan disposisi Kepala Dinas (Misal: Kabid, Sekertaris).</small>
                </div>

                <div class="form-group mt-3">
                    <label class="font-weight-bold">Atasan Langsung (Untuk Disposisi WA Kabid -> Staff)</label>
                    <select name="atasan_id" class="form-control @error('atasan_id') is-invalid @enderror">
                        <option value="">-- Tidak Ada --</option>
                        @foreach($penerima_disposisi as $row)
                            <option value="{{ $row->id }}" {{ (old('atasan_id', $data->atasan_id ?? '') == $row->id) ? 'selected' : '' }}>{{ $row->nama }} ({{ $row->jabatan }})</option>
                        @endforeach
                    </select>
                    @error('atasan_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="invalid-feedback error-atasan_id d-none"></div>
                    <small class="text-muted">Diperlukan jika role pejabat ini adalah staff bawahan Kabid/Sekretaris.</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="{{ !isset($is_modal) ? 'card-footer bg-light' : 'modal-footer bg-light' }} text-right" style="{{ isset($is_modal) ? 'flex-shrink: 0;' : '' }}">
        @if(isset($is_modal))
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        @endif
        <button type="submit" class="btn btn-primary btn-submit-pejabat">
            <i class="fa fa-save mr-1"></i> Simpan Data Pejabat
        </button>
    </div>
</form>

<script>
    function generateUsername() {
        let nama = document.querySelector('input[name="nama"]').value;
        if (!nama) {
            alert('Silakan isi Nama Lengkap terlebih dahulu untuk meng-generate username.');
            return;
        }
        let username = nama.toLowerCase().replace(/[^a-z0-9]/g, '');
        username += Math.floor(100 + Math.random() * 900); // 3 random digits
        document.getElementById('username_input').value = username;
    }

    function generatePassword() {
        let chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%";
        let pass = "";
        for (let i = 0; i < 8; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        let pwdInput = document.getElementById('password_input');
        pwdInput.value = pass;
        pwdInput.type = "text";
        document.getElementById('toggle_icon').className = "fa fa-eye-slash";
    }

    function togglePassword() {
        let pwdInput = document.getElementById('password_input');
        let icon = document.getElementById('toggle_icon');
        if (pwdInput.type === "password") {
            pwdInput.type = "text";
            icon.className = "fa fa-eye-slash";
        } else {
            pwdInput.type = "password";
            icon.className = "fa fa-eye";
        }
    }

    @if(isset($is_modal))
    $(function() {
        $('#formPejabat').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('.btn-submit-pejabat');
            var originalBtnHtml = $btn.html();

            // Clear previous errors
            $form.find('.form-ajax-errors').addClass('d-none');
            $form.find('.form-ajax-error-list').empty();
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback[class*="error-"]').addClass('d-none').removeClass('d-block').text('');

            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Menyimpan...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                success: function(res) {
                    if (res.success) {
                        $('#editPejabatModal').modal('hide');
                        window.location.reload();
                    } else {
                        window.location.reload();
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(originalBtnHtml);

                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        var errorListHtml = '';

                        $.each(errors, function(field, messages) {
                            var msg = messages[0];
                            errorListHtml += '<li>' + msg + '</li>';

                            var $input = $form.find('[name="' + field + '"]');
                            if ($input.length) {
                                $input.addClass('is-invalid');
                                var $feedback = $form.find('.error-' + field);
                                if ($feedback.length) {
                                    $feedback.removeClass('d-none').addClass('d-block').text(msg);
                                }
                            }
                        });

                        $form.find('.form-ajax-error-list').html(errorListHtml);
                        $form.find('.form-ajax-errors').removeClass('d-none');

                        // Scroll modal body ke atas agar pesan error terlihat
                        var $modalBody = $form.find('.modal-body');
                        if ($modalBody.length) {
                            $modalBody.animate({ scrollTop: 0 }, 200);
                        }
                    } else {
                        var errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan sistem. Silakan coba lagi.';
                        $form.find('.form-ajax-error-list').html('<li>' + errorMsg + '</li>');
                        $form.find('.form-ajax-errors').removeClass('d-none');

                        var $modalBody = $form.find('.modal-body');
                        if ($modalBody.length) {
                            $modalBody.animate({ scrollTop: 0 }, 200);
                        }
                    }
                }
            });
        });
    });
    @endif
</script>
