@php
    $chkStatus = $chk->status ?: ($chk->is_checked ? 'selesai' : 'pending');
    $penerimaPejabat = $chk->penerima;
    $isMyTask = ($pejabat && $chk->penerima_id == $pejabat->id);
    $isMySubordinate = ($pejabat && $penerimaPejabat && $penerimaPejabat->atasan_id == $pejabat->id);
    $canEditThis = ($briefing->status !== 'completed' && ($isKadis || $isPembuat || $isMyTask || $isMySubordinate));
    $canUploadThisFile = ($isMyTask && $briefing->status !== 'completed');

    $fileExt = $chk->file_lampiran ? strtolower(pathinfo($chk->file_lampiran, PATHINFO_EXTENSION)) : '';
    $fileIcon = 'fa-file-text-o text-slate-500';
    if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        $fileIcon = 'fa-file-image-o text-indigo-500';
    } elseif ($fileExt === 'pdf') {
        $fileIcon = 'fa-file-pdf-o text-rose-500';
    } elseif (in_array($fileExt, ['doc', 'docx'])) {
        $fileIcon = 'fa-file-word-o text-blue-500';
    } elseif (in_array($fileExt, ['xls', 'xlsx', 'csv'])) {
        $fileIcon = 'fa-file-excel-o text-emerald-500';
    } elseif (in_array($fileExt, ['zip', 'rar', '7z'])) {
        $fileIcon = 'fa-file-archive-o text-amber-500';
    }
@endphp

<div id="chk-row-{{ $chk->id }}" 
    class="bg-white rounded-2xl p-4 border transition-all duration-200 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs {{ $chkStatus === 'selesai' ? 'border-emerald-200 bg-emerald-50/10' : ($chkStatus === 'proses' ? 'border-amber-200 bg-amber-50/10' : 'border-slate-200') }}">
    
    {{-- Item Details --}}
    <div class="flex items-start gap-3 flex-1">
        <div id="chk-icon-{{ $chk->id }}" class="mt-0.5 flex-shrink-0">
            @if($chkStatus === 'selesai')
                <div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                    <i class="fa fa-check"></i>
                </div>
            @elseif($chkStatus === 'proses')
                <div class="w-7 h-7 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm font-bold shadow-xs animate-pulse">
                    <i class="fa fa-spinner fa-spin"></i>
                </div>
            @else
                <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-400 border border-slate-300 flex items-center justify-center text-sm">
                    <i class="fa fa-circle-o"></i>
                </div>
            @endif
        </div>
        <div class="flex-1 min-w-0">
            <p id="chk-text-{{ $chk->id }}" class="text-sm font-semibold {{ $chkStatus === 'selesai' ? 'text-slate-500 line-through' : ($chkStatus === 'proses' ? 'text-amber-950 font-bold' : 'text-slate-900') }}">
                {{ $chk->kegiatan }}
            </p>
            <div id="chk-meta-{{ $chk->id }}" class="text-xs mt-1">
                @if($chkStatus === 'selesai')
                    <span class="text-emerald-700 font-medium">
                        <i class="fa fa-check-circle mr-1"></i>Selesai oleh <strong>{{ $chk->checkedByPejabat?->nama ?? 'Penerima' }}</strong>
                        @if($chk->checked_at)
                            &bull; {{ $chk->checked_at->translatedFormat('d M Y, H:i') }}
                        @endif
                    </span>
                @elseif($chkStatus === 'proses')
                    <span class="text-amber-700 font-medium">
                        <i class="fa fa-clock-o mr-1"></i>Sedang dikerjakan / diproses oleh <strong>{{ $chk->checkedByPejabat?->nama ?? 'Penerima' }}</strong>
                        @if($chk->checked_at)
                            &bull; sejak {{ $chk->checked_at->translatedFormat('d M, H:i') }}
                        @endif
                    </span>
                @else
                    <span class="text-slate-400 italic">Belum dikonfirmasi / menunggu pengerjaan</span>
                @endif
            </div>

            {{-- File Lampiran / Bukti Section --}}
            <div id="chk-file-box-{{ $chk->id }}" class="mt-2.5 pt-2 border-t border-slate-100 flex flex-wrap items-center gap-2">
                @if($chk->file_lampiran)
                    <div id="chk-file-chip-{{ $chk->id }}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200 transition-colors shadow-2xs text-xs">
                        <i class="fa {{ $fileIcon }} text-sm"></i>
                        <a href="{{ $chk->file_lampiran }}" target="_blank" class="font-semibold text-slate-800 hover:text-indigo-600 truncate max-w-[180px] sm:max-w-xs transition-colors" title="Buka Dokumen/Gambar Lampiran">
                            {{ basename($chk->file_lampiran) }}
                        </a>
                        <a href="{{ $chk->file_lampiran }}" target="_blank" class="p-1 text-slate-400 hover:text-indigo-600 transition-colors" title="Buka di tab baru">
                            <i class="fa fa-external-link"></i>
                        </a>
                    </div>

                    @if($canUploadThisFile)
                        <div id="chk-file-actions-{{ $chk->id }}" class="inline-flex items-center gap-1.5 text-xs">
                            <button type="button" onclick="triggerChecklistUpload('{{ $chk->id }}')" class="px-2 py-1 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors font-medium" title="Ganti File Lampiran">
                                <i class="fa fa-refresh mr-1"></i> Ganti
                            </button>
                            <button type="button" onclick="deleteChecklistFile('{{ $chk->id }}')" class="px-2 py-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors font-medium" title="Hapus File Lampiran">
                                <i class="fa fa-trash-o mr-1"></i> Hapus
                            </button>
                            <input type="file" id="chk-file-input-{{ $chk->id }}" onchange="handleChecklistFileUpload('{{ $chk->id }}', this)" class="hidden" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt">
                        </div>
                    @endif
                @else
                    @if($canUploadThisFile)
                        <div id="chk-file-upload-btn-{{ $chk->id }}" class="inline-flex items-center gap-2">
                            <button type="button" onclick="triggerChecklistUpload('{{ $chk->id }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100/80 border border-indigo-200/70 text-indigo-700 text-xs font-semibold transition-all shadow-2xs hover:shadow-xs">
                                <i class="fa fa-paperclip"></i> Unggah Dokumen / Bukti
                            </button>
                            <span class="text-[11px] text-slate-400 hidden sm:inline">Gambar atau Office/PDF (Maks 20MB)</span>
                            <input type="file" id="chk-file-input-{{ $chk->id }}" onchange="handleChecklistFileUpload('{{ $chk->id }}', this)" class="hidden" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt">
                        </div>
                    @else
                        <span class="text-[11px] text-slate-400 italic">
                            <i class="fa fa-paperclip mr-1 opacity-70"></i>Belum ada lampiran dokumen
                        </span>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Action / Status Buttons --}}
    <div class="flex-shrink-0 flex items-center gap-2 self-end md:self-center">
        @if($canEditThis)
            <div class="inline-flex rounded-xl p-1 bg-slate-100 border border-slate-200 gap-1 text-xs font-bold">
                {{-- Tombol Pending / Reset --}}
                <button type="button" 
                    onclick="konfirmasiChecklist('{{ $chk->id }}', 'pending', this)"
                    id="btn-status-pending-{{ $chk->id }}"
                    class="px-2.5 py-1.5 rounded-lg transition {{ $chkStatus === 'pending' ? 'bg-white text-slate-800 shadow-xs border border-slate-200' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-200/60' }}"
                    title="Tandai Belum Dikerjakan">
                    Menunggu
                </button>

                {{-- Tombol Proses --}}
                <button type="button" 
                    onclick="konfirmasiChecklist('{{ $chk->id }}', 'proses', this)"
                    id="btn-status-proses-{{ $chk->id }}"
                    class="px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 {{ $chkStatus === 'proses' ? 'bg-amber-500 text-white shadow-xs' : 'text-amber-800 hover:bg-amber-100' }}"
                    title="Tandai Sedang Dikerjakan (Proses)">
                    <i class="fa fa-spinner {{ $chkStatus === 'proses' ? 'fa-spin' : '' }}"></i> Proses
                </button>

                {{-- Tombol Selesai --}}
                <button type="button" 
                    onclick="konfirmasiChecklist('{{ $chk->id }}', 'selesai', this)"
                    id="btn-status-selesai-{{ $chk->id }}"
                    class="px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 {{ $chkStatus === 'selesai' ? 'bg-emerald-600 text-white shadow-xs' : 'text-emerald-800 hover:bg-emerald-100' }}"
                    title="Tandai Selesai">
                    <i class="fa fa-check"></i> Selesai
                </button>
            </div>
        @else
            {{-- Readonly Badge jika briefing ditutup / tidak berhak --}}
            @if($chkStatus === 'selesai')
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white">
                    <i class="fa fa-check-circle"></i> Selesai
                </span>
            @elseif($chkStatus === 'proses')
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-500 text-white">
                    <i class="fa fa-spinner fa-spin"></i> Proses
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                    <i class="fa fa-clock-o"></i> Menunggu
                </span>
            @endif
        @endif
    </div>
</div>
