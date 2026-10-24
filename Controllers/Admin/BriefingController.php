<?php

namespace App\Http\Controllers\Plugins\EOffice\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugins\EOffice\Briefing;
use App\Models\Plugins\EOffice\BriefingChecklist;
use App\Models\Plugins\EOffice\BriefingDisposisi;
use App\Models\Plugins\EOffice\Pejabat;
use App\Models\Plugins\EOffice\WaSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Leazycms\FLC\Models\File;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Controllers\Plugins\EOffice\Middleware\RedirectMiddleware;

class BriefingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            RedirectMiddleware::handle(),
            function ($request, $next) {
                if (!e_briefing_enabled()) {
                    abort(404);
                }
                return $next($request);
            }
        ];
    }

    public function __construct()
    {
        if (!e_briefing_enabled()) {
            abort(404);
        }
    }

    /**
     * Dapatkan daftar pejabat struktural 1 tingkat di bawah pimpinan utama (KADIS).
     */
    protected function getPejabatSatuTingkat()
    {
        $kadis = Pejabat::where('alias_jabatan', 'KADIS')->first() ?? Pejabat::whereNull('atasan_id')->first();
        return Pejabat::with(['user', 'tupoksis'])
            ->where(function ($q) use ($kadis) {
                if ($kadis) {
                    $q->where('atasan_id', $kadis->id);
                }
                $q->orWhereIn('alias_jabatan', ['SEKRETARIS', 'KABID']);
            })
            ->where('id', '!=', $kadis?->id)
            ->whereNotNull('user_id')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();
    }

    /**
     * Cek apakah pejabat adalah 1 tingkat di bawah pimpinan utama.
     */
    protected function isPejabatSatuTingkat($pejabat): bool
    {
        if (!$pejabat)
            return false;
        if (str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis'))
            return false;

        $kadis = Pejabat::where('alias_jabatan', 'KADIS')->first() ?? Pejabat::whereNull('atasan_id')->first();
        if ($kadis && $pejabat->atasan_id == $kadis->id) {
            return true;
        }
        return in_array($pejabat->alias_jabatan, ['SEKRETARIS', 'KABID']);
    }

    public function index(Request $request)
    {
        plugin_page_name('Briefing - E-Office');
        $user = e_office_user();
        $pejabat = $user?->pejabat;

        $isKadis = false;
        if ($pejabat && str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis')) {
            $isKadis = true;
        }

        // Cek apakah user adalah pejabat 1 tingkat di bawah Kadis (misal Sekretaris, Kabid)
        $isTingkatSatu = $this->isPejabatSatuTingkat($pejabat);

        // ─── DASHBOARD MONITORING PIMPINAN UTAMA (KADIS) ───
        if ($isKadis) {
            $tanggalMulai = $request->input('tanggal_mulai');
            $tanggalSelesai = $request->input('tanggal_selesai');

            if (!$tanggalMulai && !$tanggalSelesai) {
                $tanggal = $request->input('tanggal', now()->toDateString());
                $tanggalMulai = $tanggal;
                $tanggalSelesai = $tanggal;
            } else {
                $tanggalMulai = $tanggalMulai ?: now()->toDateString();
                $tanggalSelesai = $tanggalSelesai ?: $tanggalMulai;
                $tanggal = $tanggalMulai;
            }

            $subordinates = $this->getPejabatSatuTingkat();
            $today = now()->toDateString();
            $targetDate = $tanggalSelesai ?: $today;

            foreach ($subordinates as $sub) {
                // Cari briefing yang dibuat pejabat ini dalam rentang tanggal
                $subBriefings = Briefing::with(['checklists.penerima', 'checklists.checkedByPejabat', 'disposisis.penerima'])
                    ->where('user_id', $sub->user_id)
                    ->whereDate('created_at', '>=', $tanggalMulai)
                    ->whereDate('created_at', '<=', $tanggalSelesai)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $sub->briefings = $subBriefings;
                $sub->has_briefing = $subBriefings->isNotEmpty();

                // Cek status peringatan sistem untuk pejabat ini
                $sentKey = 'briefing_wa_forward_sent_' . $sub->id . '_' . $targetDate;
                $sentAt = e_office_get_option($sentKey);
                if (!$sentAt && $targetDate !== $today) {
                    $sentAt = e_office_get_option('briefing_wa_forward_sent_' . $sub->id . '_' . $today);
                }
                $sub->has_warning_sent = !empty($sentAt);
                $sub->warning_sent_at = $sentAt ? \Carbon\Carbon::parse($sentAt) : null;

                // Kumpulkan seluruh checklist / instruksi dari briefing yang dibuat
                $allChecklists = $subBriefings->flatMap->checklists;
                $sub->total_checklists = $allChecklists->count();
                $sub->selesai_checklists = $allChecklists->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
                $sub->proses_checklists = $allChecklists->where('status', 'proses')->count();
                $sub->pending_checklists = $sub->total_checklists - $sub->selesai_checklists - $sub->proses_checklists;
                $sub->progress_percent = $sub->total_checklists > 0 ? round(($sub->selesai_checklists / $sub->total_checklists) * 100) : 0;
                $sub->all_checklists = $allChecklists;
            }

            // Statistik Global
            $totalPejabat = $subordinates->count();
            $sudahMembuatCount = $subordinates->where('has_briefing', true)->count();
            $belumMembuatCount = $subordinates->where('has_briefing', false)->count();
            $diperingatkanCount = $subordinates->where('has_briefing', false)->where('has_warning_sent', true)->count();
            $totalInstruksi = $subordinates->sum('total_checklists');
            $totalSelesaiInstruksi = $subordinates->sum('selesai_checklists');
            $totalProsesInstruksi = $subordinates->sum('proses_checklists');
            $overallProgress = $totalInstruksi > 0 ? round(($totalSelesaiInstruksi / $totalInstruksi) * 100) : 0;

            // List Briefing yang Terakhir Dibuat (Recent 10)
            $recentBriefings = Briefing::with(['user.pejabat', 'disposisis.penerima', 'checklists'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            $jamForward = e_office_get_option('briefing_warning_time_forward', '09:00');
            $jamAction = e_office_get_option('briefing_warning_time_action', '14:00');
            $warningEnabled = e_office_get_option('briefing_warning_enabled', '1') == '1';
            $autoWeekend = e_office_get_option('briefing_warning_auto_weekend', '1') == '1';

            return view('e-office::admin.briefing.index', compact(
                'isKadis',
                'isTingkatSatu',
                'pejabat',
                'subordinates',
                'totalPejabat',
                'sudahMembuatCount',
                'belumMembuatCount',
                'diperingatkanCount',
                'totalInstruksi',
                'totalSelesaiInstruksi',
                'totalProsesInstruksi',
                'overallProgress',
                'recentBriefings',
                'tanggal',
                'tanggalMulai',
                'tanggalSelesai',
                'jamForward',
                'jamAction',
                'warningEnabled',
                'autoWeekend'
            ));
        }

        // ─── TAMPILAN PEJABAT 1 TINGKAT & BAWAHAN LAINNYA ───
        $briefingsCreated = collect();
        if ($isTingkatSatu) {
            $briefingsCreated = Briefing::with(['checklists', 'disposisis.penerima'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Disposisi briefing yang diterima
        $briefingsReceived = BriefingDisposisi::with(['briefing.user.pejabat', 'pengirim'])
            ->whereHas('briefing')
            ->where('penerima_id', $pejabat?->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $briefings = $isTingkatSatu ? $briefingsCreated : $briefingsReceived;
        $jamForward = e_office_get_option('briefing_warning_time_forward', '09:00');
        $jamAction = e_office_get_option('briefing_warning_time_action', '14:00');
        $warningEnabled = e_office_get_option('briefing_warning_enabled', '1') == '1';
        $autoWeekend = e_office_get_option('briefing_warning_auto_weekend', '1') == '1';

        return view('e-office::admin.briefing.index', compact(
            'isKadis',
            'isTingkatSatu',
            'pejabat',
            'briefings',
            'briefingsCreated',
            'briefingsReceived',
            'jamForward',
            'jamAction',
            'warningEnabled',
            'autoWeekend'
        ));
    }

    public function create()
    {
        plugin_page_name('Buat Briefing - E-Office');
        $user = e_office_user();
        $pejabatUser = $user?->pejabat;

        // Pimpinan utama (Kadis) tidak lagi membuat briefing (hanya memonitor)
        if ($pejabatUser && str_contains(strtolower($pejabatUser->alias_jabatan ?? ''), 'kadis')) {
            return redirect(plugin_route('e-office.briefing.index'))
                ->with('warning', 'Pimpinan Utama memantau pelaksanaan briefing melalui Dashboard Monitoring.');
        }

        // Hanya pejabat 1 tingkat di bawah pimpinan utama yang dapat membuat briefing
        $isTingkatSatu = $this->isPejabatSatuTingkat($pejabatUser);
        abort_if(!$isTingkatSatu, 403, 'Hanya pejabat 1 tingkat di bawah pimpinan utama yang dapat membuat briefing kepada bawahannya.');

        // Ambil bawahan langsung dari pejabat ini
        $pejabats = Pejabat::with('tupoksis')
            ->where('atasan_id', $pejabatUser->id)
            ->whereNotNull('user_id')
            ->get();

        return view('e-office::admin.briefing.create', compact('pejabats', 'pejabatUser'));
    }

    public function store(Request $request)
    {
        $user = e_office_user();
        $pejabatUser = $user?->pejabat;

        if ($pejabatUser && str_contains(strtolower($pejabatUser->alias_jabatan ?? ''), 'kadis')) {
            return back()->with('error', 'Pimpinan Utama memantau pelaksanaan briefing melalui Dashboard Monitoring.');
        }

        $isTingkatSatu = $this->isPejabatSatuTingkat($pejabatUser);
        abort_if(!$isTingkatSatu, 403, 'Akses ditolak.');

        $request->validate([
            'judul' => 'nullable|string|max:255',
            'penerima_ids' => 'required|array|min:1',
            'pesan_khusus' => 'nullable|array',
            'file_khusus' => 'nullable|array',
            'tupoksi_khusus' => 'nullable|array',
            'instruksi_khusus' => 'nullable|array'
        ], [
            'penerima_ids.required' => 'Pilih minimal satu bawahan untuk menerima arahan tugas.'
        ]);

        try {
            DB::beginTransaction();

            $latestIndex = Briefing::whereDate('created_at', now()->toDateString())->count() + 1;
            $generatedTitle = 'BRF-' . now()->format('Ymd') . '-' . str_pad($latestIndex, 3, '0', STR_PAD_LEFT);
            $judul = trim($request->input('judul', '')) ?: $generatedTitle;

            $briefing = Briefing::create([
                'user_id' => $user->id,
                'judul' => $judul,
                'pesan' => null,
                'status' => 'in_progress'
            ]);

            $pengirim_id = $pejabatUser?->id;

            foreach ($request->penerima_ids as $penerima_id) {
                $disposisi = BriefingDisposisi::create([
                    'briefing_id' => $briefing->id,
                    'pengirim_id' => $pengirim_id,
                    'penerima_id' => $penerima_id,
                    'pesan_diteruskan' => $request->pesan_khusus[$penerima_id] ?? null
                ]);

                if ($request->hasFile("file_khusus.{$penerima_id}")) {
                    $fname = $disposisi->addFile([
                        'file' => $request->file("file_khusus.{$penerima_id}"),
                        'purpose' => 'briefing_lampiran_khusus_' . $disposisi->id,
                        'random_name' => true,
                        'mime_type' => ['image/webp', 'application/pdf']
                    ]);
                    $disposisi->update(['file_lampiran' => $fname]);
                }

                // 2. Simpan Poin Instruksi Khusus Dinamis per bawahan
                if (isset($request->instruksi_khusus[$penerima_id]) && is_array($request->instruksi_khusus[$penerima_id])) {
                    foreach ($request->instruksi_khusus[$penerima_id] as $kegiatan) {
                        if (is_string($kegiatan) && trim($kegiatan) !== '') {
                            BriefingChecklist::create([
                                'briefing_id' => $briefing->id,
                                'kegiatan' => trim($kegiatan),
                                'penerima_id' => $penerima_id,
                                'status' => 'pending',
                                'is_checked' => false
                            ]);
                        }
                    }
                }

                // 3. Simpan Tupoksi Khusus (checkbox tupoksi bawaan)
                if (isset($request->tupoksi_khusus[$penerima_id]) && is_array($request->tupoksi_khusus[$penerima_id])) {
                    foreach ($request->tupoksi_khusus[$penerima_id] as $kegiatan) {
                        if (is_string($kegiatan) && trim($kegiatan) !== '') {
                            BriefingChecklist::create([
                                'briefing_id' => $briefing->id,
                                'kegiatan' => trim($kegiatan),
                                'penerima_id' => $penerima_id,
                                'status' => 'pending',
                                'is_checked' => false
                            ]);
                        }
                    }
                }

                $pejabatTujuan = Pejabat::find($penerima_id);
                if ($pejabatTujuan && $pejabatTujuan->nohp) {
                    $notifUrl = $briefing->addNotification([
                        'to_user' => $pejabatTujuan->user_id,
                        'title' => 'Tugas/Briefing Baru: ' . $briefing->judul,
                        'message' => 'Ada Tugas/Briefing baru dari ' . ($pejabatUser?->nama ?? $user->name),
                        'url' => plugin_route('e-office.briefing.show', $briefing->id)
                    ]);
                    WaSender::dispatch([
                        'to' => $pejabatTujuan->nohp,
                        'type' => 'text',
                        'text' => "*Tugas Baru hari ini*\n\nWaktu: " . $briefing->created_at->format('d M Y H:i') . "\nPengirim: " . ($pejabatUser?->nama ?? $user->name) . "\n\nSilakan cek E-Office untuk menindaklanjuti tugas ini di link berikut:\n" . $notifUrl,
                    ]);
                }
            }

            // Kirim Notifikasi In-App & WhatsApp ke Pimpinan Utama (KADIS)
            $kadis = Pejabat::where('alias_jabatan', 'KADIS')->first() ?? Pejabat::whereNull('atasan_id')->first();
            if ($kadis && $kadis->id !== $pejabatUser?->id) {
                $totalBawahanDitugaskan = count($request->penerima_ids);
                $pengirimNama = $pejabatUser?->nama ?? $user->name;
                $pengirimJabatan = $pejabatUser?->jabatan ?: ($pejabatUser?->alias_jabatan ?: 'Pejabat Struktural');

                $notifKadisUrl = plugin_route('e-office.briefing.show', $briefing->id);

                if ($kadis->user_id) {
                    $notifKadisUrl = $briefing->addNotification([
                        'to_user' => $kadis->user_id,
                        'title' => 'Briefing Diterbitkan: ' . $briefing->judul,
                        'message' => "{$pengirimNama} ({$pengirimJabatan}) telah menerbitkan briefing kepada {$totalBawahanDitugaskan} staf bawahan.",
                        'url' => plugin_route('e-office.briefing.show', $briefing->id)
                    ]);
                }

                if ($kadis->nohp) {
                    $pesanWaKadis = "*Laporan Penerbitan Briefing/Tugas*\n\n"
                        . "Yth. {$kadis->jabatan},\n\n"
                        . "Telah diterbitkan arahan tugas/briefing oleh:\n"
                        . "• Pengirim: *{$pengirimNama}* ({$pengirimJabatan})\n"
                        . "• Judul: *{$briefing->judul}*\n"
                        . "• Waktu: " . $briefing->created_at->format('d M Y, H:i') . " WIB\n"
                        . "• Ditujukan Kepada: *{$totalBawahanDitugaskan} Bawahan*\n\n"
                        . "Silakan pantau kepatuhan dan progres pelaksanaan tugas melalui link berikut:\n"
                        . $notifKadisUrl;

                    WaSender::dispatch([
                        'to' => $kadis->nohp,
                        'type' => 'text',
                        'text' => $pesanWaKadis,
                    ]);
                }
            }

            DB::commit();
            return redirect(plugin_route('e-office.briefing.index'))->with('success', 'Briefing/Tugas berhasil dibuat dan diteruskan kepada bawahan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $briefing = Briefing::with([
            'user.pejabat',
            'checklists.penerima',
            'checklists.checkedByPejabat',
            'disposisis.pengirim',
            'disposisis.penerima'
        ])->findOrFail($id);

        plugin_page_name($briefing->judul . ' - Briefing - E-Office');
        $user = e_office_user();
        $pejabat = $user?->pejabat;

        $isKadis = false;
        if ($pejabat && str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis')) {
            $isKadis = true;
        }

        $isPembuat = ($briefing->user_id == $user->id);

        $myDisposisi = null;
        if (!$isKadis && !$isPembuat) {
            $myDisposisi = BriefingDisposisi::where('briefing_id', $briefing->id)
                ->where('penerima_id', $pejabat?->id)
                ->first();

            // Jika bukan kadis dan bukan pembuat, pastikan user memang terdaftar sebagai penerima briefing ini
            abort_if(!$myDisposisi, 403, 'Akses ditolak. Anda tidak terdaftar sebagai penerima briefing ini.');

            if ($myDisposisi && is_null($myDisposisi->dibaca_pada)) {
                $myDisposisi->update(['dibaca_pada' => now()]);
            }
        }

        // 1. Bangun struktur pohon hierarki disposisi dan penerusan tugas
        $allDisposisis = $briefing->disposisis;
        $allChecklists = $briefing->checklists;

        $childrenByPengirim = [];
        foreach ($allDisposisis as $d) {
            $childrenByPengirim[$d->pengirim_id][] = $d;
        }

        $penerimaIdsInBriefing = $allDisposisis->pluck('penerima_id')->toArray();
        $rootDisposisis = $allDisposisis->filter(function($d) use ($penerimaIdsInBriefing) {
            return !in_array($d->pengirim_id, $penerimaIdsInBriefing);
        })->sortBy('created_at')->values();

        if ($rootDisposisis->isEmpty() && $allDisposisis->isNotEmpty()) {
            $rootDisposisis = collect([$allDisposisis->sortBy('created_at')->first()]);
        }

        // Fungsi rekursif untuk membangun pohon hierarki terstruktur
        $flattenTree = function($disp, $depth, $ancestorNames, $chainPejabat) use (&$flattenTree, $childrenByPengirim, $allChecklists, $pejabat, $isKadis, $isPembuat) {
            $res = collect();
            $penerima = $disp->penerima;
            $pengirim = $disp->pengirim;
            $penerimaNama = $penerima?->nama ?? 'Pegawai';
            $chain = array_merge($ancestorNames, [$penerimaNama]);
            $pejabatChain = array_merge($chainPejabat, [$penerima]);
            $hasChildren = isset($childrenByPengirim[$disp->penerima_id]);

            $chks = $allChecklists->where('penerima_id', $disp->penerima_id)->values();
            $cTotal = $chks->count();
            $cSelesai = $chks->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
            $cProses = $chks->where('status', 'proses')->count();
            $cPending = $cTotal - $cSelesai - $cProses;
            $cPercent = $cTotal > 0 ? round(($cSelesai / $cTotal) * 100) : 0;

            $isDirectSub = false;
            $isNestedSub = false;

            if ($isKadis || $isPembuat) {
                $isDirectSub = ($depth === 0);
                $isNestedSub = ($depth > 0);
            } else {
                $isDirectSub = ($disp->pengirim_id == $pejabat?->id);
                $ancestorPejabatIds = collect($chainPejabat)->pluck('id')->toArray();
                $isNestedSub = in_array($pejabat?->id, $ancestorPejabatIds) && $disp->penerima_id != $pejabat?->id && !$isDirectSub;
            }

            $childrenDisposisis = $hasChildren ? collect($childrenByPengirim[$disp->penerima_id]) : collect();

            $node = [
                'id' => $disp->id,
                'disposisi' => $disp,
                'pengirim_id' => $disp->pengirim_id,
                'penerima_id' => $disp->penerima_id,
                'pengirim' => $pengirim,
                'penerima' => $penerima,
                'depth' => $depth,
                'chain' => $chain,
                'pejabat_chain' => $pejabatChain,
                'chain_string' => implode(' ➔ ', $chain),
                'checklists' => $chks,
                'stats' => [
                    'total' => $cTotal,
                    'selesai' => $cSelesai,
                    'proses' => $cProses,
                    'pending' => $cPending,
                    'percent' => $cPercent,
                ],
                'has_children' => $hasChildren,
                'children_count' => $childrenDisposisis->count(),
                'is_last_receiver' => !$hasChildren,
                'is_direct_sub' => $isDirectSub,
                'is_nested_sub' => $isNestedSub,
                'is_me' => ($disp->penerima_id == $pejabat?->id),
            ];

            $res->push($node);

            if ($hasChildren) {
                foreach ($childrenByPengirim[$disp->penerima_id] as $childDisp) {
                    $res = $res->merge($flattenTree($childDisp, $depth + 1, $chain, $pejabatChain));
                }
            }
            return $res;
        };

        $disposisiTreeFlat = collect();
        foreach ($rootDisposisis as $r) {
            $pengirimNama = $r->pengirim?->nama ?? 'Pimpinan';
            $disposisiTreeFlat = $disposisiTreeFlat->merge($flattenTree($r, 0, [$pengirimNama], [$r->pengirim]));
        }

        $visitedIds = $disposisiTreeFlat->pluck('id')->toArray();
        $orphanDisposisis = $allDisposisis->filter(fn($d) => !in_array($d->id, $visitedIds));
        foreach ($orphanDisposisis as $od) {
            $disposisiTreeFlat = $disposisiTreeFlat->merge($flattenTree($od, 0, [$od->pengirim?->nama ?? 'Pimpinan'], [$od->pengirim]));
        }

        // 2. Filter checklist untuk halaman utama (Tugas Anda & Bawahan Langsung)
        if ($isKadis || $isPembuat) {
            $myChecklists = collect();
            $subordinateChecklists = $disposisiTreeFlat->filter(fn($n) => $n['is_direct_sub'])->pluck('checklists')->flatten();
            $checklistsToView = $briefing->checklists;
        } else {
            $myChecklists = $briefing->checklists->filter(function ($c) use ($pejabat) {
                return $c->penerima_id == $pejabat?->id || is_null($c->penerima_id);
            });

            $directSubPenerimaIds = $disposisiTreeFlat->filter(fn($n) => $n['is_direct_sub'])->pluck('penerima_id')->toArray();
            $subordinateChecklists = $briefing->checklists->filter(function ($c) use ($directSubPenerimaIds) {
                return in_array($c->penerima_id, $directSubPenerimaIds);
            });

            $checklistsToView = $myChecklists->merge($subordinateChecklists);
        }

        // 3. Bawahan-bawahannya (Tingkat 2+ sampai penerima terakhir) untuk ditampilkan dalam MODAL
        $nestedSubordinates = $disposisiTreeFlat->filter(fn($n) => $n['is_nested_sub'])->values();

        $penerimaSudahAda = $briefing->disposisis->pluck('penerima_id')->filter()->toArray();
        $semuaPejabat = Pejabat::with('tupoksis')
            ->where('atasan_id', $pejabat?->id)
            ->whereNotIn('id', $penerimaSudahAda)
            ->get();

        return view('e-office::admin.briefing.show', compact(
            'briefing', 
            'isKadis', 
            'isPembuat', 
            'myDisposisi', 
            'semuaPejabat', 
            'pejabat', 
            'checklistsToView',
            'myChecklists',
            'subordinateChecklists',
            'disposisiTreeFlat',
            'nestedSubordinates'
        ));
    }

    public function teruskan(Request $request, $id)
    {
        $request->validate([
            'penerima_ids' => 'required|array',
            'pesan_diteruskan' => 'nullable|string',
            'pesan_khusus' => 'nullable|array',
            'file_khusus' => 'nullable|array',
            'tupoksi_khusus' => 'nullable|array',
            'instruksi_khusus' => 'nullable|array'
        ]);

        $briefing = Briefing::findOrFail($id);
        $pejabat = e_office_user()->pejabat;
        $pengirim_id = $pejabat?->id;

        DB::beginTransaction();
        try {
            foreach ($request->penerima_ids as $penerima_id) {
                $disposisi = BriefingDisposisi::create([
                    'briefing_id' => $briefing->id,
                    'pengirim_id' => $pengirim_id,
                    'penerima_id' => $penerima_id,
                    'pesan_diteruskan' => $request->pesan_diteruskan,
                    'pesan_khusus' => $request->pesan_khusus[$penerima_id] ?? null
                ]);

                if ($request->hasFile("file_khusus.{$penerima_id}")) {
                    $fname = $disposisi->addFile([
                        'file' => $request->file("file_khusus.{$penerima_id}"),
                        'purpose' => 'briefing_lampiran_khusus_' . $disposisi->id,
                        'random_name' => true,
                        'mime_type' => ['image/webp', 'application/pdf']
                    ]);
                    $disposisi->update(['file_lampiran' => $fname]);
                }

                // Instruksi Khusus Dinamis
                if (isset($request->instruksi_khusus[$penerima_id]) && is_array($request->instruksi_khusus[$penerima_id])) {
                    foreach ($request->instruksi_khusus[$penerima_id] as $kegiatan) {
                        if (is_string($kegiatan) && trim($kegiatan) !== '') {
                            BriefingChecklist::create([
                                'briefing_id' => $briefing->id,
                                'kegiatan' => trim($kegiatan),
                                'penerima_id' => $penerima_id,
                                'status' => 'pending',
                                'is_checked' => false
                            ]);
                        }
                    }
                }

                // Tupoksi Khusus
                if (isset($request->tupoksi_khusus[$penerima_id]) && is_array($request->tupoksi_khusus[$penerima_id])) {
                    foreach ($request->tupoksi_khusus[$penerima_id] as $kegiatan) {
                        if (is_string($kegiatan) && trim($kegiatan) !== '') {
                            BriefingChecklist::create([
                                'briefing_id' => $briefing->id,
                                'kegiatan' => trim($kegiatan),
                                'penerima_id' => $penerima_id,
                                'status' => 'pending',
                                'is_checked' => false
                            ]);
                        }
                    }
                }

                $pejabatTujuan = Pejabat::find($penerima_id);
                if ($pejabatTujuan && $pejabatTujuan->nohp) {
                    $notifUrl = $briefing->addNotification([
                        'to_user' => $pejabatTujuan->user_id,
                        'title' => 'Tugas/Briefing Diteruskan: ' . $briefing->judul,
                        'message' => 'Ada Tugas/Briefing yang diteruskan dari ' . ($pejabat?->nama ?? e_office_user()->name),
                        'url' => plugin_route('e-office.briefing.show', $briefing->id)
                    ]);
                    $pesanWaTujuan = "*Penerusan Tugas / Briefing*\n\n"
                        . "Waktu: " . $disposisi->created_at->translatedFormat('d M Y, H:i') . "\n"
                        . "Pengirim: *" . ($pejabat?->nama ?? e_office_user()->name) . "* (" . ($pejabat?->jabatan ?? 'Atasan') . ")\n"
                        . "Judul: *{$briefing->judul}*\n";

                    if (!empty($request->pesan_diteruskan)) {
                        $pesanWaTujuan .= "Catatan Umum: " . trim($request->pesan_diteruskan) . "\n";
                    }
                    if (!empty($request->pesan_khusus[$penerima_id])) {
                        $pesanWaTujuan .= "Catatan Khusus: " . trim($request->pesan_khusus[$penerima_id]) . "\n";
                    }

                    if (!empty($request->instruksi_khusus[$penerima_id]) && is_array($request->instruksi_khusus[$penerima_id])) {
                        $validItems = array_filter($request->instruksi_khusus[$penerima_id], fn($k) => is_string($k) && trim($k) !== '');
                        if (count($validItems) > 0) {
                            $pesanWaTujuan .= "\n*Poin-poin Instruksi Kegiatan:*\n";
                            $no = 1;
                            foreach ($validItems as $item) {
                                $pesanWaTujuan .= "{$no}. " . trim($item) . "\n";
                                $no++;
                            }
                        }
                    }

                    $pesanWaTujuan .= "\nSilakan cek E-Office untuk menindaklanjuti dan mengonfirmasi status tugas:\n" . $notifUrl;

                    WaSender::dispatch([
                        'to' => $pejabatTujuan->nohp,
                        'type' => 'text',
                        'text' => $pesanWaTujuan,
                    ]);
                }
            }
            DB::commit();
            return back()->with('success', 'Tugas berhasil diteruskan dengan rincian khusus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function balas(Request $request, $id)
    {
        $briefing = Briefing::findOrFail($id);
        $pejabat = e_office_user()->pejabat;

        $disposisi = BriefingDisposisi::where('briefing_id', $briefing->id)
            ->where('penerima_id', $pejabat?->id)
            ->firstOrFail();

        $pejabatNama = $pejabat?->nama ?? e_office_user()->name;

        $newEntry = [
            'waktu' => now()->format('d M Y, H:i'),
            'nama' => $pejabatNama,
            'pesan' => $request->balasan_teks,
            'file_url' => null
        ];

        $updateData = [
            'dibalas_pada' => now(),
            'balasan_teks' => $request->balasan_teks
        ];

        if ($request->hasFile('balasan_file')) {
            $fname = $disposisi->addFile([
                'file' => $request->file('balasan_file'),
                'purpose' => 'briefing_balasan_' . $disposisi->id . '_' . time(),
                'random_name' => true,
                'mime_type' => ['image/webp', 'application/pdf']
            ]);
            $updateData['balasan_file'] = $fname;
            $newEntry['file_url'] = url($fname);
        }

        $history = $disposisi->balasan_history ? json_decode($disposisi->balasan_history, true) : [];
        if (!is_array($history))
            $history = [];
        $history[] = $newEntry;

        $updateData['balasan_history'] = json_encode($history);
        $disposisi->update($updateData);

        // Update status checklist dari form balas
        if ($request->checklists && is_array($request->checklists)) {
            foreach ($request->checklists as $checklist_id => $val) {
                $chk = BriefingChecklist::where('id', $checklist_id)->where('briefing_id', $briefing->id)->first();
                if ($chk) {
                    $status = in_array($val, ['selesai', 'proses', 'pending']) ? $val : ($val == 1 ? 'selesai' : 'pending');
                    $isSelesai = ($status === 'selesai');
                    $chk->update([
                        'status' => $status,
                        'is_checked' => $isSelesai,
                        'checked_by_pejabat_id' => $isSelesai ? $pejabat?->id : ($chk->checked_by_pejabat_id ?? $pejabat?->id),
                        'checked_at' => $isSelesai ? now() : $chk->checked_at
                    ]);
                }
            }
        }

        return back()->with('success', 'Balasan tugas berhasil dikirim.');
    }

    /**
     * Konfirmasi per-poin instruksi (Pending, Proses, Selesai) via AJAX / Request.
     */
    public function updateChecklist(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,proses,selesai'
        ]);

        $chk = BriefingChecklist::with('briefing', 'penerima')->findOrFail($id);
        $user = e_office_user();
        $pejabat = $user?->pejabat;

        $isKadis = ($pejabat && str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis'));
        $isPembuat = ($user && $chk->briefing && $chk->briefing->user_id == $user->id);
        $isPenerima = (is_null($chk->penerima_id) || $chk->penerima_id == $pejabat?->id);
        $isAtasanPenerima = ($pejabat && $chk->penerima && $chk->penerima->atasan_id == $pejabat->id);

        if (!$isKadis && !$isPembuat && !$isPenerima && !$isAtasanPenerima) {
            abort(403, 'Anda tidak berwenang mengonfirmasi instruksi ini.');
        }

        $newStatus = $request->status;
        $isSelesai = ($newStatus === 'selesai');

        $chk->update([
            'status' => $newStatus,
            'is_checked' => $isSelesai,
            'checked_by_pejabat_id' => $pejabat?->id,
            'checked_at' => ($isSelesai || $newStatus === 'proses') ? now() : null
        ]);

        $briefing = $chk->briefing ? $chk->briefing->fresh(['checklists']) : Briefing::with('checklists')->find($chk->briefing_id);

        if (!$isKadis && !$isPembuat) {
            $downlineIds = $pejabat ? Pejabat::where('atasan_id', $pejabat->id)->pluck('id')->toArray() : [];
            $myChks = $briefing->checklists->filter(fn($c) => $c->penerima_id == $pejabat?->id || is_null($c->penerima_id));
            $subChks = $briefing->checklists->filter(fn($c) => in_array($c->penerima_id, $downlineIds) && $c->penerima_id != $pejabat?->id);
        } else {
            $myChks = collect();
            $subChks = $briefing->checklists;
        }

        // Stats tugas pribadi (yang diterima)
        $myTotal = $myChks->count();
        $mySelesai = $myChks->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
        $myProses = $myChks->where('status', 'proses')->count();
        $myPending = $myTotal - $mySelesai - $myProses;
        $myPercent = $myTotal > 0 ? round(($mySelesai / $myTotal) * 100) : 0;

        // Stats tugas bawahan (yang diteruskan)
        $subTotal = $subChks->count();
        $subSelesai = $subChks->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
        $subProses = $subChks->where('status', 'proses')->count();
        $subPending = $subTotal - $subSelesai - $subProses;
        $subPercent = $subTotal > 0 ? round(($subSelesai / $subTotal) * 100) : 0;

        $isTargetMyTask = (is_null($chk->penerima_id) || $chk->penerima_id == $pejabat?->id);

        // Stats khusus orang/penerima terkait yang sedang diupdate
        $targetPenerimaId = $chk->penerima_id;
        $targetPersonChks = $briefing->checklists->filter(function($c) use ($targetPenerimaId, $pejabat) {
            if (is_null($targetPenerimaId)) {
                return is_null($c->penerima_id) || $c->penerima_id == $pejabat?->id;
            }
            return $c->penerima_id == $targetPenerimaId;
        });
        $targetPersonTotal = $targetPersonChks->count();
        $targetPersonSelesai = $targetPersonChks->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
        $targetPersonPercent = $targetPersonTotal > 0 ? round(($targetPersonSelesai / $targetPersonTotal) * 100) : 0;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'is_checked' => $isSelesai,
                'checked_at' => $chk->checked_at ? $chk->checked_at->translatedFormat('d M Y, H:i') : null,
                'checked_by' => $pejabat?->nama ?? $user->name,
                'updated_group' => $isTargetMyTask ? 'my' : 'sub',
                'penerima_id' => $targetPenerimaId,
                'person_stats' => [
                    'penerima_id' => $targetPenerimaId,
                    'total' => $targetPersonTotal,
                    'selesai' => $targetPersonSelesai,
                    'percent' => $targetPersonPercent,
                    'label' => "{$targetPersonSelesai}/{$targetPersonTotal} Selesai ({$targetPersonPercent}%)"
                ],
                'my_stats' => [
                    'total' => $myTotal,
                    'selesai' => $mySelesai,
                    'proses' => $myProses,
                    'pending' => $myPending,
                    'percent' => $myPercent,
                    'label' => "{$mySelesai}/{$myTotal} Selesai ({$myPercent}%)"
                ],
                'sub_stats' => [
                    'total' => $subTotal,
                    'selesai' => $subSelesai,
                    'proses' => $subProses,
                    'pending' => $subPending,
                    'percent' => $subPercent
                ],
                'stats' => [
                    'total' => $isTargetMyTask ? $myTotal : $subTotal,
                    'selesai' => $isTargetMyTask ? $mySelesai : $subSelesai,
                    'proses' => $isTargetMyTask ? $myProses : $subProses,
                    'pending' => $isTargetMyTask ? $myPending : $subPending,
                    'percent' => $isTargetMyTask ? $myPercent : $subPercent
                ],
                'message' => 'Status instruksi berhasil diperbarui menjadi ' . strtoupper($newStatus) . '.'
            ]);
        }

        return back()->with('success', 'Status instruksi berhasil diperbarui.');
    }

    /**
     * Upload dokumen/gambar untuk suatu poin instruksi (Hanya bawahan penerima).
     */
     public function uploadChecklistFile(Request $request, $id)
     {
         $request->validate([
             'file' => 'required|file|max:20480|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt'
         ], [
             'file.required' => 'Pilih file dokumen atau gambar untuk diunggah.',
             'file.max' => 'Ukuran file maksimal adalah 20MB.',
             'file.mimes' => 'Format file harus berupa dokumen (PDF, Word, Excel, PPT, ZIP/RAR, TXT) atau gambar (JPG, PNG, WEBP).'
         ]);

         $chk = BriefingChecklist::with('briefing', 'penerima')->findOrFail($id);
         $user = e_office_user();
         $pejabat = $user?->pejabat;

         // Hanya bawahan penerima instruksi ini yang dapat mengunggah dokumen
         if (!$pejabat || $chk->penerima_id != $pejabat->id) {
             abort(403, 'Hanya bawahan penerima instruksi yang dapat mengunggah dokumen/lampiran.');
         }

         if ($chk->briefing->status === 'completed') {
             return response()->json(['success' => false, 'message' => 'Briefing/tugas ini telah selesai dan ditutup.'], 422);
         }

         try {
             DB::beginTransaction();

             // 1. Bersihkan file lama milik checklist ini dari database dan storage
             foreach ($chk->files as $oldFile) {
                 Cache::forget($oldFile->host . ':media:' . $oldFile->file_name);
                 Cache::forget(request()->getHost() . ':media:' . $oldFile->file_name);
                 $oldFile->deleteFile();
             }

             // Bersihkan juga file orphaned yang terkait purpose atau fileable checklist ini
             $slugPurpose = str('briefing_checklist_' . $chk->id)->slug();
             $orphanedFiles = File::where('purpose', $slugPurpose)
                 ->orWhere(function ($q) use ($chk) {
                     $q->where('fileable_type', get_class($chk))
                       ->where('fileable_id', $chk->id);
                 })
                 ->get();

             foreach ($orphanedFiles as $orphaned) {
                 Cache::forget($orphaned->host . ':media:' . $orphaned->file_name);
                 Cache::forget(request()->getHost() . ':media:' . $orphaned->file_name);
                 $orphaned->deleteFile();
             }

             // Jika ada file fisik lampiran lama yang masih tersisa
             if ($chk->file_lampiran) {
                 $oldBasename = basename($chk->file_lampiran);
                 Cache::forget(request()->getHost() . ':media:' . $oldBasename);
                 $matchingOld = File::where('file_name', $oldBasename)->get();
                 foreach ($matchingOld as $mOld) {
                     if (str_contains($mOld->purpose ?? '', 'briefing') || $mOld->fileable_id == $chk->id) {
                         $mOld->deleteFile();
                     }
                 }
             }

             $file = $request->file('file');
             $originalName = $file->getClientOriginalName();
             $fileExt = strtolower($file->getClientOriginalExtension());

             // 2. Simpan file baru tanpa deduplikasi lintas domain/library
             $fname = $chk->addFile([
                 'file' => $file,
                 'purpose' => 'briefing_checklist_' . $chk->id,
                 'random_name' => true,
                 'no_duplicate' => true,
             ]);

             if (!$fname) {
                 throw new \Exception('Gagal memproses penyimpanan file.');
             }

             $chk->update([
                 'file_lampiran' => $fname
             ]);

             DB::commit();

             return response()->json([
                 'success' => true,
                 'file_url' => $fname,
                 'file_name' => $originalName,
                 'file_ext' => $fileExt,
                 'message' => 'Dokumen/gambar lampiran berhasil diunggah.'
             ]);
         } catch (\Exception $e) {
             DB::rollBack();
             return response()->json([
                 'success' => false,
                 'message' => 'Gagal mengunggah file: ' . $e->getMessage()
             ], 500);
         }
     }

     /**
      * Hapus dokumen/gambar lampiran instruksi (Hanya bawahan penerima).
      */
     public function deleteChecklistFile($id)
     {
         $chk = BriefingChecklist::with('briefing', 'penerima')->findOrFail($id);
         $user = e_office_user();
         $pejabat = $user?->pejabat;

         if (!$pejabat || $chk->penerima_id != $pejabat->id) {
             abort(403, 'Hanya bawahan penerima instruksi yang dapat menghapus dokumen/lampiran.');
         }

         if ($chk->briefing->status === 'completed') {
             return response()->json(['success' => false, 'message' => 'Briefing/tugas ini telah selesai dan ditutup.'], 422);
         }

         try {
             DB::beginTransaction();

             foreach ($chk->files as $file) {
                 Cache::forget($file->host . ':media:' . $file->file_name);
                 Cache::forget(request()->getHost() . ':media:' . $file->file_name);
                 $file->deleteFile();
             }

             $slugPurpose = str('briefing_checklist_' . $chk->id)->slug();
             $orphanedFiles = File::where('purpose', $slugPurpose)
                 ->orWhere(function ($q) use ($chk) {
                     $q->where('fileable_type', get_class($chk))
                       ->where('fileable_id', $chk->id);
                 })
                 ->get();

             foreach ($orphanedFiles as $orphaned) {
                 Cache::forget($orphaned->host . ':media:' . $orphaned->file_name);
                 Cache::forget(request()->getHost() . ':media:' . $orphaned->file_name);
                 $orphaned->deleteFile();
             }

             if ($chk->file_lampiran) {
                 $oldBasename = basename($chk->file_lampiran);
                 Cache::forget(request()->getHost() . ':media:' . $oldBasename);
                 $matchingOld = File::where('file_name', $oldBasename)->get();
                 foreach ($matchingOld as $mOld) {
                     if (str_contains($mOld->purpose ?? '', 'briefing') || $mOld->fileable_id == $chk->id) {
                         $mOld->deleteFile();
                     }
                 }
             }

             $chk->update([
                 'file_lampiran' => null
             ]);

             DB::commit();

             return response()->json([
                 'success' => true,
                 'message' => 'Dokumen lampiran berhasil dihapus.'
             ]);
         } catch (\Exception $e) {
             DB::rollBack();
             return response()->json([
                 'success' => false,
                 'message' => 'Gagal menghapus file: ' . $e->getMessage()
             ], 500);
         }
     }

    public function feedback(Request $request, $id)
    {
        $request->validate(['feedback_kadis' => 'required|string']);
        $disposisi = BriefingDisposisi::findOrFail($id);
        $briefing = $disposisi->briefing;
        $pejabat = e_office_user()->pejabat;
        $isKadis = ($pejabat && str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis'));

        if ($briefing->user_id != e_office_user()->id && !$isKadis) {
            abort(403);
        }

        $disposisi->update(['feedback_kadis' => $request->feedback_kadis]);

        return back()->with('success', 'Feedback pimpinan berhasil dikirim.');
    }

    public function validasi($id)
    {
        $disposisi = BriefingDisposisi::findOrFail($id);
        $pejabat = e_office_user()->pejabat;

        if ($disposisi->pengirim_id != $pejabat?->id) {
            abort(403, 'Akses ditolak.');
        }

        $disposisi->update(['is_validated_by_atasan' => true]);

        return back()->with('success', 'Respon balasan berhasil divalidasi dan diteruskan ke pimpinan.');
    }

    public function close(Request $request, $id)
    {
        $briefing = Briefing::findOrFail($id);
        $pejabat = e_office_user()->pejabat;
        $isKadis = ($pejabat && str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis'));

        if ($briefing->user_id !== e_office_user()->id && !$isKadis) {
            return back()->with('error', 'Akses ditolak.');
        }

        $briefing->update([
            'status' => 'completed'
        ]);

        return redirect(plugin_route('e-office.briefing.index'))->with('success', 'Briefing/Tugas diselesaikan.');
    }

    public function destroy($id)
    {
        $briefing = Briefing::findOrFail($id);
        $pejabat = e_office_user()->pejabat;
        $isKadis = ($pejabat && str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis'));

        if ($briefing->user_id !== e_office_user()->id && !$isKadis) {
            return back()->with('error', 'Akses ditolak. Anda tidak berhak menghapus tugas ini.');
        }

        try {
            DB::beginTransaction();
            $briefing->delete();
            DB::commit();

            return back()->with('success', 'Briefing/Tugas berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Update jam peringatan WhatsApp oleh Kepala Dinas (Pimpinan) atau Admin
     */
    public function updateJamPeringatan(Request $request)
    {
        $user = e_office_user();
        $pejabat = $user?->pejabat;

        $isKadis = false;
        if ($pejabat && $pejabat->isKadis()) {
            $isKadis = true;
        }
        if ($user?->isAdmin() || $user?->is_kadis()) {
            $isKadis = true;
        }

        if (!$isKadis) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya Kepala Dinas (Pimpinan) atau Admin yang berhak mengatur jam peringatan.'
                ], 403);
            }
            abort(403, 'Hanya Kepala Dinas (Pimpinan) atau Admin yang berhak mengatur jam peringatan.');
        }

        $request->validate([
            'briefing_warning_time_forward' => 'nullable|string',
            'briefing_warning_time_action' => 'nullable|string',
            'briefing_warning_enabled' => 'nullable',
            'briefing_warning_auto_weekend' => 'nullable',
            'is_full_setting' => 'nullable',
        ]);

        $isFullSetting = $request->boolean('is_full_setting') || $request->filled('briefing_warning_time_forward');

        if ($isFullSetting) {
            $warningEnabled = $request->boolean('briefing_warning_enabled') ? '1' : '0';
            $autoWeekend = $request->boolean('briefing_warning_auto_weekend') ? '1' : '0';
            e_office_set_option('briefing_warning_enabled', $warningEnabled);
            e_office_set_option('briefing_warning_auto_weekend', $autoWeekend);
        } else {
            if ($request->has('briefing_warning_enabled')) {
                $warningEnabled = $request->boolean('briefing_warning_enabled') ? '1' : '0';
                e_office_set_option('briefing_warning_enabled', $warningEnabled);
            } else {
                $warningEnabled = e_office_get_option('briefing_warning_enabled', '1');
            }

            if ($request->has('briefing_warning_auto_weekend')) {
                $autoWeekend = $request->boolean('briefing_warning_auto_weekend') ? '1' : '0';
                e_office_set_option('briefing_warning_auto_weekend', $autoWeekend);
            } else {
                $autoWeekend = e_office_get_option('briefing_warning_auto_weekend', '1');
            }
        }

        if ($request->filled('briefing_warning_time_forward')) {
            $jamForward = date('H:i', strtotime($request->briefing_warning_time_forward));
            e_office_set_option('briefing_warning_time_forward', $jamForward);
        } else {
            $jamForward = e_office_get_option('briefing_warning_time_forward', '09:00');
        }

        if ($request->filled('briefing_warning_time_action')) {
            $jamAction = date('H:i', strtotime($request->briefing_warning_time_action));
            e_office_set_option('briefing_warning_time_action', $jamAction);
        } else {
            $jamAction = e_office_get_option('briefing_warning_time_action', '14:00');
        }

        Cache::forget('briefing_reminder_last_run');

        $statusText = ($warningEnabled === '1')
            ? "Peringatan AKTIF (Batas memberi tugas: {$jamForward} WIB, respon: {$jamAction} WIB)."
            : "Peringatan DINONAKTIFKAN (HARI LIBUR). Pengiriman WA & peringatan dashboard dinonaktifkan sementara.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Pengaturan berhasil disimpan! {$statusText}",
                'data' => [
                    'briefing_warning_enabled' => $warningEnabled,
                    'briefing_warning_auto_weekend' => $autoWeekend,
                    'briefing_warning_time_forward' => $jamForward,
                    'briefing_warning_time_action' => $jamAction,
                ]
            ]);
        }

        return back()->with('success', "Pengaturan jam & status peringatan berhasil diperbarui! {$statusText}");
    }
}
