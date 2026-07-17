<form method="POST" action="{{ isset($data) ? route('earsip.pejabat.update', $data->id) : route('earsip.pejabat.store') }}" class="mb-0">
    @csrf
    @if(isset($data)) @method('PUT') @endif
    
    <div class="{{ !isset($is_modal) ? 'card-body' : 'modal-body' }}">
        <div class="row">
            <!-- Kolom Detail Pejabat -->
            <div class="col-md-6">
                <h6 class="font-weight-bold text-uppercase border-bottom pb-2 mb-3">Informasi Pribadi & Jabatan</h6>
                
                <div class="form-group">
                    <label class="font-weight-bold">Nama Lengkap (beserta gelar) <span class="text-danger">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama', $data->nama ?? '') }}" required class="form-control">
                </div>
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">NIP <span class="text-danger">*</span></label>
                        <input type="text" name="nip" value="{{ old('nip', $data->nip ?? '') }}" required class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Nomor HP (WhatsApp) <span class="text-danger">*</span></label>
                        <input type="text" name="nohp" value="{{ old('nohp', $data->nohp ?? '') }}" placeholder="628xxxx" required class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Nama Jabatan Terulis <span class="text-danger">*</span></label>
                    <input type="text" name="jabatan" value="{{ old('jabatan', $data->jabatan ?? '') }}" placeholder="Contoh: Kepala Dinas / Staff Administrasi" required class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="font-weight-bold">Pangkat & Golongan</label>
                    <input type="text" name="pangkat_golongan" value="{{ old('pangkat_golongan', $data->pangkat_golongan ?? '') }}" class="form-control">
                </div>
                
                <div class="form-group">
                    <label class="font-weight-bold">Alias / Role Jabatan <span class="text-danger">*</span></label>
                    <select name="alias_jabatan" required class="form-control">
                        <option value="">-- Pilih Alias --</option>
                        @foreach(['KADIS','KASUBAGUMUM','KABID','SEKRETARIS','OPERATOR','STAFF'] as $alias)
                            <option value="{{ $alias }}" {{ (old('alias_jabatan', $data->alias_jabatan ?? '') == $alias) ? 'selected' : '' }}>{{ $alias }}</option>
                        @endforeach
                    </select>
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
                            <input type="text" id="username_input" name="username" value="{{ old('username', $data->user->username ?? '') }}" required class="form-control" autocomplete="off" pattern="[a-zA-Z0-9_]+" title="Hanya huruf, angka, dan garis bawah (_)" minlength="4" onkeydown="return event.keyCode !== 32;" oninput="this.value = this.value.replace(/\s/g, '')">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateUsername()" title="Generate Username"><i class="fa fa-magic"></i></button>
                            </div>
                        </div>
                        <small class="text-muted">Min 4 karakter, tanpa spasi.</small>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="font-weight-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $data->user->email ?? '') }}" required class="form-control" autocomplete="off">
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Password @if(!isset($data)) <span class="text-danger">*</span> @endif</label>
                    <div class="input-group">
                        <input type="password" id="password_input" name="password" {{ !isset($data) ? 'required' : '' }} class="form-control" autocomplete="new-password" minlength="6" pattern="^\S+$" title="Tidak boleh mengandung spasi" onkeydown="return event.keyCode !== 32;" oninput="this.value = this.value.replace(/\s/g, '')">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()" title="Lihat Password"><i class="fa fa-eye" id="toggle_icon"></i></button>
                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()" title="Generate Password"><i class="fa fa-magic"></i></button>
                        </div>
                    </div>
                    @if(isset($data))
                        <small class="text-muted">Biarkan kosong jika tidak ingin mengubah password. Min 6 karakter.</small>
                    @else
                        <small class="text-muted">Min 6 karakter.</small>
                    @endif
                </div>

                @if(isset($data))
                <div class="form-group">
                    <label class="font-weight-bold">Status Akun</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ isset($data->user) && $data->user->status == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ isset($data->user) && $data->user->status == 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                    </select>
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
                    <select name="atasan_id" class="form-control">
                        <option value="">-- Tidak Ada --</option>
                        @foreach($penerima_disposisi as $row)
                            <option value="{{ $row->id }}" {{ (old('atasan_id', $data->atasan_id ?? '') == $row->id) ? 'selected' : '' }}>{{ $row->nama }} ({{ $row->jabatan }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Diperlukan jika role pejabat ini adalah staff bawahan Kabid/Sekretaris.</small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="{{ !isset($is_modal) ? 'card-footer bg-light' : 'modal-footer bg-light' }} text-right">
        @if(isset($is_modal))
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
        @endif
        <button type="submit" class="btn btn-primary">
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
</script>
