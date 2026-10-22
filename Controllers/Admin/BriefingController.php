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
            $totalInstruksi = $subordinates->sum('total_checklists');
            $totalSelesaiInstruksi = $subordinates->sum('selesai_checklists');
            $totalProsesInstruksi = $subordinates->sum('proses_checklists');
            $overallProgress = $totalInstruksi > 0 ? round(($totalSelesaiInstruksi / $totalInstruksi) * 100) : 0;

            // List Briefing yang Terakhir Dibuat (Recent 10)
            $recentBriefings = Briefing::with(['user.pejabat', 'disposisis.penerima', 'checklists'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            return view('e-office::admin.briefing.index', compact(
                'isKadis',
                'isTingkatSatu',
                'pejabat',
                'subordinates',
                'totalPejabat',
                'sudahMembuatCount',
                'belumMembuatCount',
                'totalInstruksi',
                'totalSelesaiInstruksi',
                'totalProsesInstruksi',
                'overallProgress',
                'recentBriefings',
                'tanggal',
                'tanggalMulai',
                'tanggalSelesai'
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

        return view('e-office::admin.briefing.index', compact(
            'isKadis',
            'isTingkatSatu',
            'pejabat',
            'briefings',
            'briefingsCreated',
            'briefingsReceived'
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

        // Filter checklist sesuai hak akses (Bawahan hanya melihat instruksi untuk dirinya sendiri atau bawahannya jika didelegasikan)
        if ($isKadis || $isPembuat) {
            $checklistsToView = $briefing->checklists;
        } else {
            $downlineIds = $pejabat ? Pejabat::where('atasan_id', $pejabat->id)->pluck('id')->toArray() : [];
            $visiblePenerimaIds = array_merge([$pejabat?->id], $downlineIds);
            $checklistsToView = $briefing->checklists->filter(function ($c) use ($visiblePenerimaIds) {
                return in_array($c->penerima_id, $visiblePenerimaIds) || is_null($c->penerima_id);
            });
        }

        $penerimaSudahAda = $briefing->disposisis->pluck('penerima_id')->filter()->toArray();
        $semuaPejabat = Pejabat::with('tupoksis')
            ->where('atasan_id', $pejabat?->id)
            ->whereNotIn('id', $penerimaSudahAda)
            ->get();

        return view('e-office::admin.briefing.show', compact('briefing', 'isKadis', 'isPembuat', 'myDisposisi', 'semuaPejabat', 'pejabat', 'checklistsToView'));
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
        $isPembuat = ($chk->briefing->user_id == $user->id);
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

        $briefing = $chk->briefing->fresh(['checklists']);

        if (!$isKadis && !$isPembuat) {
            $downlineIds = $pejabat ? Pejabat::where('atasan_id', $pejabat->id)->pluck('id')->toArray() : [];
            $visiblePenerimaIds = array_merge([$pejabat?->id], $downlineIds);
            $statsChks = $briefing->checklists->filter(function ($c) use ($visiblePenerimaIds) {
                return in_array($c->penerima_id, $visiblePenerimaIds) || is_null($c->penerima_id);
            });
        } else {
            $statsChks = $briefing->checklists;
        }

        $totalChks = $statsChks->count();
        $selesaiChks = $statsChks->filter(fn($c) => $c->status === 'selesai' || $c->is_checked)->count();
        $prosesChks = $statsChks->where('status', 'proses')->count();
        $pendingChks = $totalChks - $selesaiChks - $prosesChks;
        $percent = $totalChks > 0 ? round(($selesaiChks / $totalChks) * 100) : 0;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'is_checked' => $isSelesai,
                'checked_at' => $chk->checked_at ? $chk->checked_at->translatedFormat('d M Y, H:i') : null,
                'checked_by' => $pejabat?->nama ?? $user->name,
                'stats' => [
                    'total' => $totalChks,
                    'selesai' => $selesaiChks,
                    'proses' => $prosesChks,
                    'pending' => $pendingChks,
                    'percent' => $percent
                ],
                'message' => 'Status instruksi berhasil diperbarui menjadi ' . strtoupper($newStatus) . '.'
            ]);
        }

        return back()->with('success', 'Status instruksi berhasil diperbarui.');
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
}
