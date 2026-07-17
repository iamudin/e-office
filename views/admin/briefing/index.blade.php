@extends('earsip::layout.app', ['title' => 'Briefing / Tugas'])

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Daftar Briefing & Tugas</h2>
            <p class="text-sm text-gray-500">Kelola dan pantau tugas dari pimpinan.</p>
        </div>
        @if($isKadis)
        <a href="{{ plugin_route('earsip.briefing.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-600/20">
            <i class="fa fa-plus"></i> Buat Briefing Baru
        </a>
        @endif
    </div>

    <div class="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50">
                        <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Tugas</th>
                        @if(!$isKadis)
                        <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Dari</th>
                        @endif
                        <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="p-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($briefings as $item)
                        @php 
                            $briefing = $isKadis ? $item : $item->briefing; 
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
                                <p class="text-xs text-gray-500">{{ $item->pengirim->alias_jabatan ?? '-' }}</p>
                            </td>
                            @endif
                            <td class="p-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-{{ $statusColor }}-50 text-{{ $statusColor }}-700 border border-{{ $statusColor }}-200/60">
                                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $statusColor }}-500"></span>
                                    {{ $statusText }}
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ plugin_route('earsip.briefing.show', $briefing->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-gray-200 text-gray-500 hover:text-indigo-600 hover:border-indigo-200 hover:bg-indigo-50 transition-all shadow-sm">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isKadis ? 3 : 4 }}" class="p-8 text-center text-gray-500">
                                <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-3 text-gray-400">
                                    <i class="fa fa-folder-open-o text-xl"></i>
                                </div>
                                Belum ada data briefing/tugas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
