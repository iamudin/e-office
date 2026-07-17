<li class="dd-item mb-2" data-id="{{ $pejabat->id }}">
    @if($pejabat->staff && $pejabat->staff->count() > 0)
        <button type="button" class="my-collapse-btn" data-myaction="collapse">
            <i class="fa fa-minus-square"></i>
        </button>
    @endif
    <div class="dd-handle d-flex align-items-center p-2 pl-5 bg-white border rounded shadow-sm mb-2"
        style="cursor: move;">
        <div class="mr-3">
            @if($level == 0)
                <div class="bg-primary text-white rounded p-2 text-center"
                    style="width: 35px; height: 35px; line-height: 20px;">
                    <i class="fa fa-sitemap"></i>
                </div>
            @else
                <div class="bg-light text-secondary rounded p-2 text-center"
                    style="width: 35px; height: 35px; line-height: 20px;">
                    <i class="fa fa-user"></i>
                </div>
            @endif
        </div>
        <div class="flex-grow-1">
            <h6 class="mb-0 font-weight-bold text-dark">{{ $pejabat->nama ?? 'Tanpa Nama' }}</h6>
            <small class="text-muted">{{ $pejabat->jabatan ?? $pejabat->alias_jabatan }}</small>
        </div>
        <div class="ml-auto pr-3 dd-nodrag d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" onmousedown="event.stopPropagation();" onclick="event.stopPropagation(); editPejabat('{{ $pejabat->id }}')">
                <i class="fa fa-edit"></i> Edit
            </button>
            <button type="button" class="btn btn-sm btn-outline-info ml-1" onmousedown="event.stopPropagation();" onclick="event.stopPropagation(); manageTupoksi('{{ $pejabat->id }}', '{{ addslashes($pejabat->nama ?? 'Tanpa Nama') }}')">
                <i class="fa fa-tasks"></i> Tupoksi
            </button>
        </div>
    </div>

    @if($pejabat->staff && $pejabat->staff->count() > 0)
        <ol class="dd-list ">
            @foreach($pejabat->staff as $bawahan)
                @include('earsip::admin.pejabat._hirarki', ['pejabat' => $bawahan, 'level' => $level + 1])
            @endforeach
        </ol>
    @endif
</li>