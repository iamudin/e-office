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

class BriefingController extends Controller
{
    public function index()
    {
        $user = e_office_user();
        $isKadis = false; // logic to determine if current user is Kadis
        
        $pejabat = $user->pejabat;
        if($pejabat && str_contains(strtolower($pejabat->alias_jabatan ?? ''), 'kadis')) {
            $isKadis = true;
        }

        // Jika Kadis, tampilkan briefing yang dibuatnya
        if ($isKadis) {
            $briefings = Briefing::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Jika bawahan, tampilkan disposisi briefing yang diterima
            $briefings = BriefingDisposisi::with('briefing')
                ->where('penerima_id', $pejabat?->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('e-office::admin.briefing.index', compact('briefings', 'isKadis', 'pejabat'));
    }

    public function create()
    {
        $pejabatUser = e_office_user()?->pejabat;
        
        // Ambil bawahan langsung (bawahan pertama)
        $pejabats = Pejabat::with('tupoksis')
            ->where('atasan_id', $pejabatUser?->id)
            ->whereNotNull('user_id')
            ->get();
            
        return view('e-office::admin.briefing.create', compact('pejabats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'penerima_ids' => 'required|array',
            'pesan_masal' => 'nullable|string',
            'file_lampiran_masal' => 'nullable|file',
            'pesan_khusus' => 'nullable|array',
            'file_khusus' => 'nullable|array',
            'tupoksi_khusus' => 'nullable|array'
        ]);

        try {
            DB::beginTransaction();

            $briefing = Briefing::create([
                'user_id' => e_office_user()->id,
                'judul' => $request->judul,
                'pesan' => $request->pesan_masal,
                'status' => 'in_progress'
            ]);

            if ($request->hasFile('file_lampiran_masal')) {
                $fname = $briefing->addFile([
                    'file' => $request->file('file_lampiran_masal'),
                    'purpose' => 'briefing_lampiran_masal_' . $briefing->id,
                    'random_name'=>true,
                    'mime_type'=>['image/webp','application/pdf']

                ]);
                $briefing->update(['file_lampiran' => $fname]);
            }



            $pengirim_id = e_office_user()->pejabat?->id;

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
                        'random_name'=>true,
                    'mime_type'=>['image/webp','application/pdf']

                    ]);
                    $disposisi->update(['file_lampiran' => $fname]);
                }
                
                // Simpan Tupoksi Khusus
                if (isset($request->tupoksi_khusus[$penerima_id]) && is_array($request->tupoksi_khusus[$penerima_id])) {
                    foreach ($request->tupoksi_khusus[$penerima_id] as $kegiatan) {
                        if (trim($kegiatan) !== '') {
                            BriefingChecklist::create([
                                'briefing_id' => $briefing->id,
                                'kegiatan' => $kegiatan,
                                'penerima_id' => $penerima_id
                            ]);
                        }
                    }
                }



                $pejabatTujuan = Pejabat::find($penerima_id);
                if ($pejabatTujuan && $pejabatTujuan->nohp) {
                    WaSender::dispatch([
                        'to' => $pejabatTujuan->nohp,
                        'type' => 'text',
                        'text' => "*Tugas/Briefing Baru*\n\nJudul: " . $briefing->judul . "\nPengirim: " . (e_office_user()->pejabat?->nama ?? e_office_user()->name) . "\n\nSilakan cek E-Office untuk menindaklanjuti tugas ini.",
                    ]);
                }
            }

            DB::commit();
            return redirect(plugin_route('e-office.briefing.index'))->with('success', 'Briefing/Tugas berhasil dibuat dan diteruskan.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $briefing = Briefing::with(['checklists', 'disposisis.pengirim', 'disposisis.penerima'])->findOrFail($id);
        
        $pejabat = e_office_user()->pejabat;
        $isKadis = ($briefing->user_id == e_office_user()->id);

        $myDisposisi = null;
        if (!$isKadis) {
            $myDisposisi = BriefingDisposisi::where('briefing_id', $briefing->id)
                ->where('penerima_id', $pejabat?->id)
                ->first();
                
            if ($myDisposisi && is_null($myDisposisi->dibaca_pada)) {
                $myDisposisi->update(['dibaca_pada' => now()]);
            }
        }

        $penerimaSudahAda = $briefing->disposisis->pluck('penerima_id')->filter()->toArray();
        $semuaPejabat = Pejabat::with('tupoksis')
            ->where('atasan_id', $pejabat?->id)
            ->whereNotIn('id', $penerimaSudahAda)
            ->get();

        return view('e-office::admin.briefing.show', compact('briefing', 'isKadis', 'myDisposisi', 'semuaPejabat', 'pejabat'));
    }

    public function teruskan(Request $request, $id)
    {
        $request->validate([
            'penerima_ids' => 'required|array',
            'pesan_diteruskan' => 'nullable|string',
            'pesan_khusus' => 'nullable|array',
            'file_khusus' => 'nullable|array',
            'tupoksi_khusus' => 'nullable|array'
        ]);

        $briefing = Briefing::findOrFail($id);
        $pejabat = e_office_user()->pejabat;
        $pengirim_id = $pejabat?->id;

        $myDisposisi = BriefingDisposisi::where('briefing_id', $briefing->id)
            ->where('penerima_id', $pengirim_id)
            ->first();

        DB::beginTransaction();
        try {
            foreach ($request->penerima_ids as $penerima_id) {
                // Gunakan pesan_khusus jika ada, jika tidak gunakan pesan_diteruskan (massal)
                $disposisi = BriefingDisposisi::create([
                    'briefing_id' => $briefing->id,
                    'pengirim_id' => $pengirim_id,
                    'penerima_id' => $penerima_id,
                    'pesan_diteruskan' => $request->pesan_diteruskan,
                    'pesan_khusus' => $request->pesan_khusus[$penerima_id] ?? null
                ]);

                // Handle file (File khusus atau file yang diteruskan)
                $forwardedFile = $myDisposisi ? $myDisposisi->file_lampiran : $briefing->file_lampiran;
                
                if ($request->hasFile("file_khusus.{$penerima_id}")) {
                    $fname = $disposisi->addFile([
                        'file' => $request->file("file_khusus.{$penerima_id}"),
                        'purpose' => 'briefing_lampiran_khusus_' . $disposisi->id,
                        'random_name'=>true,
                        'mime_type'=>['image/webp','application/pdf']
                    ]);
                    $disposisi->update(['file_lampiran' => $fname]);
                } else if ($forwardedFile) {
                    $disposisi->update(['file_lampiran' => $forwardedFile]);
                }

                // Simpan Tupoksi Khusus
                if (isset($request->tupoksi_khusus[$penerima_id]) && is_array($request->tupoksi_khusus[$penerima_id])) {
                    foreach ($request->tupoksi_khusus[$penerima_id] as $kegiatan) {
                        if (trim($kegiatan) !== '') {
                            BriefingChecklist::create([
                                'briefing_id' => $briefing->id,
                                'kegiatan' => $kegiatan,
                                'penerima_id' => $penerima_id
                            ]);
                        }
                    }
                }

                $pejabatTujuan = Pejabat::find($penerima_id);
                if ($pejabatTujuan && $pejabatTujuan->nohp) {
                    WaSender::dispatch([
                        'to' => $pejabatTujuan->nohp,
                        'type' => 'text',
                        'text' => "*Tugas/Briefing Diteruskan*\n\nJudul: " . $briefing->judul . "\nDari: " . ($pejabat?->nama ?? e_office_user()->name) . "\n\nSilakan cek E-Office untuk menindaklanjuti tugas ini.",
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

        $updateData = [
            'balasan_teks' => $request->balasan_teks,
            'dibalas_pada' => now()
        ];

        if ($request->hasFile('balasan_file')) {
            $fname = $disposisi->addFile([
                'file' => $request->file('balasan_file'),
                'purpose' => 'briefing_balasan_' . $disposisi->id,
                'random_name'=>true,
                'mime_type'=>['image/webp','application/pdf']

            ]);
            $updateData['balasan_file'] = $fname;
        }

        $disposisi->update($updateData);

        if ($request->checklists) {
            foreach ($request->checklists as $checklist_id => $val) {
                $chk = BriefingChecklist::where('id', $checklist_id)->where('briefing_id', $briefing->id)->first();
                if ($chk && !$chk->is_checked) {
                    $chk->update([
                        'is_checked' => true,
                        'checked_by_pejabat_id' => $pejabat->id,
                        'checked_at' => now()
                    ]);
                }
            }
        }

        return back()->with('success', 'Balasan tugas berhasil dikirim.');
    }

    public function feedback(Request $request, $id)
    {
        $request->validate(['feedback_kadis' => 'required|string']);
        $disposisi = BriefingDisposisi::findOrFail($id);
        
        $briefing = $disposisi->briefing;
        if ($briefing->user_id != e_office_user()->id) abort(403);

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
        if ($briefing->user_id !== e_office_user()->id) {
            return back()->with('error', 'Akses ditolak.');
        }

        $briefing->update([
            'status' => 'completed'
        ]);

        return redirect(plugin_route('e-office.briefing.index'))->with('success', 'Briefing/Tugas diselesaikan.');
    }
}
