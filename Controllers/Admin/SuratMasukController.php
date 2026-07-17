<?php

namespace App\Http\Controllers\Plugins\EOffice\Admin;
use setasign\Fpdi\Fpdi;
use Illuminate\Http\Request;
use Leazycms\FLC\Models\File;
use Illuminate\Http\UploadedFile;
use App\Models\Plugins\EOffice\Arsip;
use App\Models\Plugins\EOffice\WaSender;
use App\Models\Plugins\EOffice\Pejabat;
use App\Models\Plugins\EOffice\Disposisi;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\File as Filestorage;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Controllers\Plugins\EOffice\Middleware\RedirectMiddleware;

class SuratMasukController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            RedirectMiddleware::handle(),
        ];
    }

    public function merge_pdf($pdf1, $pdf2)
    {
        $pdf = new Fpdi();
        $files = [$pdf1, $pdf2];
        try {
            foreach ($files as $file) {
                $pageCount = $pdf->setSourceFile($file);
                for ($page = 1; $page <= $pageCount; $page++) {
                    $template = $pdf->importPage($page);
                    $size = $pdf->getTemplateSize($template);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($template);
                }
            }
            return $pdf->Output('S');
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    function storePdfPathToConvert(string $fullPdfPath): void
    {
        $directory = 'pdf-path-to-convert';
        $fileName = 'idfile_' . now()->format('Ymd_His_u') . '.txt';
        Storage::put("$directory/$fileName", $fullPdfPath);
    }

    public function store_to_path_converter($arsip_id, $files = null)
    {
        $folderName = 'converter-pdf/pdf-in';
        $path = storage_path("app/{$folderName}");
        if (!Filestorage::exists($path)) {
            Filestorage::makeDirectory($path, 0755, true);
        }
        $listPath = $path . '/' . $arsip_id;
        file_put_contents($listPath, implode(' ', array_map('escapeshellarg', $files)));
    }

    public function arsip_utama($request, $arsip)
    {
        $surat = Arsip::with('disposisis.pejabat', 'kadis')->find($arsip);
        $penerima = Pejabat::select('id', 'jabatan', 'nama')->wherePenerimaDisposisi(1)->orderBy('jabatan', 'desc')->get();
        $pdf = PDF::loadView('e-office::pdf.disposisis', [
            'data' => $surat,
            'penerima' => $penerima
        ]);
        try {
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $pdfdisposisi = $tempDir . '/arsip-disposisi-' . $surat->id . '.pdf';
            $filesurat = Storage::disk('local')->path(File::whereFileName(basename($surat->file_surat))->first()?->file_path);
            file_put_contents($pdfdisposisi, $pdf->output());
            $pdfString = $this->merge_pdf($pdfdisposisi, $filesurat);
            $tmpPath = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tmpPath, $pdfString);
            $uploaded = new UploadedFile($tmpPath, 'arsip-' . $surat->id . '.pdf', 'application/pdf', null, true);
            $newRequest = Request::createFromBase(new SymfonyRequest(
                $request->query->all(),
                $request->request->all(),
                $request->attributes->all(),
                $request->cookies->all(),
                ['surat' => $uploaded],
                $request->server->all(),
                $request->getContent()
            ));
            $fname = $surat->addFile([
                'file' => $newRequest->file('surat'),
                'mime_type' => ['application/pdf'],
                'purpose' => 'Arsip Dispoisi PDF ' . $surat->id
            ]);
            $surat->update(['file_arsip' => $fname]);
            unlink($pdfdisposisi);
            return $fname;
        } catch (\Exception $e) {
            return back()->with('warning', 'Proses gagal ' . $e->getMessage());
        }
    }

    public function arsip_surat($request, $surat, $disposisi_id)
    {
        $surat = Arsip::withWhereHas('disposisis', function ($q) use ($disposisi_id) {
            $q->where('id', '=', $disposisi_id)->with('pejabat');
        })->with('kadis')->find($surat);
        $penerima = Pejabat::select('id', 'jabatan', 'nama')->wherePenerimaDisposisi(1)->orderBy('jabatan', 'desc')->get();
        $disposisi = Disposisi::find($disposisi_id);
        $pdf = PDF::loadView('e-office::pdf.disposisi', [
            'data' => $surat,
            'penerima' => $penerima
        ]);
        try {
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            $pdfdisposisi = $tempDir . '/disposisi-' . $surat->id . '.pdf';
            $filesurat = Storage::disk('local')->path(File::whereFileName(basename($surat->file_surat))->first()?->file_path);
            file_put_contents($pdfdisposisi, $pdf->output());
            $pdfString = $this->merge_pdf($pdfdisposisi, $filesurat);
            $tmpPath = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tmpPath, $pdfString);
            $uploaded = new UploadedFile($tmpPath, 'arsip-' . $surat->id . '.pdf', 'application/pdf', null, true);
            $newRequest = Request::createFromBase(new SymfonyRequest(
                $request->query->all(),
                $request->request->all(),
                $request->attributes->all(),
                $request->cookies->all(),
                ['surat' => $uploaded],
                $request->server->all(),
                $request->getContent()
            ));
            $fname = $disposisi->addFile([
                'file' => $newRequest->file('surat'),
                'mime_type' => ['application/pdf'],
                'purpose' => 'Disposisi Arsip PDF ' . $disposisi_id
            ]);
            $disposisi->update(['disposisi_pdf' => $fname]);
            unlink($pdfdisposisi);
            return url($fname);
        } catch (\Exception $e) {
            return back()->with('warning', 'Proses gagal ' . $e->getMessage());
        }
    }

    public function create()
    {
        e_office_auth_check();
        return view('e-office::admin.surat-masuk.form', ['data' => null, 'noagenda' => Arsip::count() + 1]);
    }

    public function edit(Arsip $surat_masuk)
    {
        e_office_auth_check();
        return view('e-office::admin.surat-masuk.form', ['data' => $surat_masuk]);
    }

    public function disposisi(Request $request, Arsip $arsip)
    {
        e_office_auth_check();
        if ($request->perbarui_dipsosisi && e_office_user()->is_kadis()) {
            $arsip->update(['disposisi_pada' => null]);
            $arsip->disposisis()->forceDelete();
            return redirect(plugin_route('e-office.surat-masuk.show', $arsip->id))->with('success', 'Disposisi berhasil diperbarui');
        }
        if ($request->cetak_disposisi) {
            $file = $this->arsip_utama($request, $arsip->id);
            $arsip->update(['file_arsip' => $file]);
            $f = File::whereFileName(basename($file))->first();
            return response()->download(Storage::path($f->file_path), 'disposisi-' . str($arsip->nomor_surat)->slug() . '.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        }
        if ($request->kirim_wa && $request->pejabat_id) {
            $pejabat = Pejabat::find($request->pejabat_id);
            $d = $arsip->disposisis()->updateOrCreate([
                'arsip_id' => $arsip->id,
                'pejabat_id' => e_office_user()->pejabat->id,
            ], [
                'whatsapp_pejabat' => $pejabat->id,
                'teruskan_ke_whatsapp_pada' => now(),
                'catatan' => strip_tags($request->pesan),
            ]);
            $filearsip = $this->arsip_surat($request, $arsip->id, $d->id);
            if (is_local()) {
                $filearsip = "https://disbun.bengkaliskab.go.id/media/940b37e73902286ce666acd667541dad-ansm.pdf";
            }
            WaSender::dispatch([
                'to' => $pejabat->nohp,
                'document_name' => basename($filearsip),
                'document_url' => $filearsip,
                'type' => 'file',
                'text' => 'Disposisi surat masuk untuk ditindak lanjuti',
            ]);
            return redirect(plugin_route('e-office.surat-masuk.index'))->with('success', 'Surat berhasil diteruskan ke ke ' . $pejabat->jabatan);
        }

        if ($request->paraf_kasubag && $request->teruskan_ke_kadis) {
            $pejabat = Pejabat::whereHas('user', function ($q) {
                $q->whereStatus('active');
            })->select('user_id', 'nohp', 'id')->whereAliasJabatan('KADIS')->first();
            if (empty($pejabat)) {
                return back()->with('warning', 'Data Kepala Dinas tidak ditemukan atau user belum diaktifkan');
            }
            $arsip->update([
                'paraf_kasubagumum_pada' => now(),
                'diteruskan_ke_kadis' => now(),
                'kadis_id' => $pejabat->id,
            ]);
            $notif = $arsip->addNotification([
                'to_user' => $pejabat->user_id,
                'title' => 'Surat Baru NO. ' . $request->nomor_surat,
                'message' => 'Ada surat Terbaru untuk anda',
                'url' => plugin_route('e-office.surat-masuk.show', $arsip->id),
            ]);
            WaSender::dispatch([
                'to' => $pejabat->nohp,
                'text' => "Surat masuk dari " . $arsip->surat_dari . " untuk di disposisi di link berikut :\n" . $notif,
            ]);
            return redirect(plugin_route('e-office.surat-masuk.index'))->with('success', 'Surat berhasil di paraf dan diteruskan ke Kepala Dinas');
        }

        if ($request->respon_disposisi) {
            $arsip->disposisis()->whereBelongsTo(e_office_user()->pejabat)->update([
                'catatan' => $request->catatan,
                'dibalas_pada' => now(),
            ]);
            return back()->with('success', 'Balasan dikirim');
        }
        if ($request->kadis_meneruskan) {
            $request->validate([
                'pejabat_id' => 'array|required',
                'harapan' => 'array|required',
                'catatan' => 'string|nullable',
            ], [
                'pejabat_id.required' => 'Silahkan ceklis pejabat yang ingin diteruskan',
                'harapan.required' => 'Silahkan ceklis salah satu harapan dipsosisi',
            ]);
            $arsip->update([
                'catatan' => $request->catatan,
                'harapan' => $request->harapan,
                'disposisi_pada' => now(),
            ]);
            $pejabatnya = Pejabat::whereIn('id', $request->pejabat_id)->get();
            foreach ($pejabatnya as $row) {
                $notif = $arsip->addNotification([
                    'to_user' => $row->user_id,
                    'title' => 'Surat Baru NO. ' . $arsip->nomor_surat,
                    'message' => 'Ada surat Terbaru untuk anda',
                    'url' => plugin_route('e-office.surat-masuk.show', $arsip->id),
                ]);
                $arsip->disposisis()->updateOrCreate([
                    'arsip_id' => $arsip->id,
                    'user_id' => $row->user_id,
                    'pejabat_id' => $row->id,
                ]);
                WaSender::dispatch([
                    'to' => $row->nohp,
                    'text' => "Disposisi Kepala Dinas surat masuk dari " . $arsip->surat_dari . ".\n Klik tautan berikut untuk melihat.\n" . $notif,
                ]);
            }
            return redirect(plugin_route('e-office.surat-masuk.index'))->with('success', 'Surat berhasil di disposisi');
        }
    }

    public function show($surat)
    {
        e_office_auth_check();
        $surat = Arsip::with('disposisis.pejabat')->find($surat);
        if (empty($surat)) {
            return redirect(plugin_route('e-office.surat-masuk.index'))->with('danger', 'Surat Tidak ditemukan');
        }
        if (!in_array(e_office_user()->pejabat->alias_jabatan, ['KASUBAGUMUM', 'KADIS', 'OPERATOR'])) {
            $cek = $surat->disposisis->where('pejabat_id', e_office_user()->pejabat->id)->first();
            if (empty($cek)) {
                return redirect(plugin_route('e-office.surat-masuk.index'))->with('danger', 'Surat Tidak ditemukan');
            }
            if (is_null($cek->dibaca_pada)) {
                $cek->update(['dibaca_pada' => now()]);
            }
        }
        if (e_office_user()->is_kabid()) {
            $staff = Pejabat::whereAtasanId(e_office_user()->pejabat->id)->whereHas('user', function ($u) {
                $u->whereStatus('active');
            })->get();
        }
        $surat->notificationCleaner();
        return view(
            'e-office::admin.surat-masuk.show',
            [
                'data' => $surat,
                'pejabat' => Pejabat::wherePenerimaDisposisi(1)->whereNotIn('alias_jabatan', ['OPERATOR', 'KASUBAG', 'KADIS'])->orderBy('alias_jabatan', 'desc')->whereHas('user', function ($u) {
                    $u->whereStatus('active');
                })->get(),
                'staff' => $staff ?? [],
            ]
        );
    }

    public function store(Request $request)
    {
        e_office_auth_check();
        $data = e_office_user()->arsips()->create([
            'tanggal_surat' => $request->tanggal_surat,
            'nomor_surat' => $request->nomor_surat,
            'nomor_agenda' => Arsip::count() + 1,
            'surat_dari' => $request->surat_dari,
            'hal' => $request->hal,
            'sifat' => $request->sifat,
            'tanggal_terima' => $request->tanggal_terima,
        ]);
        if ($request->hasFile('file_surat')) {
            $fname = $data->addFile([
                'file' => $request->file('file_surat'),
                'mime_type' => ['application/pdf'],
                'purpose' => 'file-surat-' . $data->id,
            ]);
            $data->update(['file_surat' => $fname]);
            if (!empty($fname)) {
                $pdfifle = File::whereFileName(basename($fname))->first()->file_path;
                $this->storePdfPathToConvert(Storage::path($pdfifle));
                $pejabat = Pejabat::select('user_id', 'nohp')->whereAliasJabatan('KASUBAGUMUM')->first();
                if (empty($pejabat)) {
                    return back()->with('warning', 'Belum bisa input surat, Kasubag Umum belum tersedia');
                }
                $notif = $data->addNotification([
                    'to_user' => $pejabat->user_id,
                    'title' => 'Surat Baru NO. ' . $request->nomor_surat,
                    'message' => 'Ada surat Terbaru untuk anda',
                    'url' => plugin_route('e-office.surat-masuk.show', $data->id),
                ]);
                WaSender::dispatch([
                    'to' => $pejabat->nohp,
                    'text' => "Surat masuk dari " . $data->surat_dari . ".\nKlik tautan berikut untuk melihat\n" . $notif,
                ]);
            }
        }
        return redirect(plugin_route('e-office.surat-masuk.index'))->with('success', 'Berhasil dibuat');
    }

    public function update(Request $request, Arsip $surat_masuk)
    {
        e_office_auth_check();
        $surat_masuk->update([
            'tanggal_surat' => $request->tanggal_surat,
            'nomor_surat' => $request->nomor_surat,
            'nomor_agenda' => Arsip::count() + 1,
            'surat_dari' => $request->surat_dari,
            'hal' => $request->hal,
            'sifat' => $request->sifat,
            'tanggal_terima' => $request->tanggal_terima,
        ]);
        if ($request->hasFile('file_surat')) {
            $fname = $surat_masuk->addFile([
                'file' => $request->file('file_surat'),
                'mime_type' => ['application/pdf'],
                'purpose' => 'file_surat_' . $surat_masuk->id,
            ]);
            $surat_masuk->update(['file_surat' => $fname]);
        }
        return back()->with('success', 'Berhasil di perbaharui');
    }

    public function index()
    {
        e_office_auth_check();

        $data = [];
        return view('e-office::admin.surat-masuk.index', $data);
    }

    public function datatable(Request $request)
    {
        e_office_auth_check();
        $user = e_office_user();
        $data = Arsip::with('user.pejabat', 'disposisis.pejabat');
        if (e_office_user()->is_kasubag()) {
            if (strpos($request->header('referer'), 'selesai') === false) {
                $data = $data->whereNull('paraf_kasubagumum_pada')->whereNull('diteruskan_ke_kadis')->latest('diteruskan_ke_kadis');
            } else {
                $data = $data->whereNotNull('paraf_kasubagumum_pada')->whereNotNull('diteruskan_ke_kadis');
            }
        }
        if ($user->is_operator()) {
            if (strpos($request->header('referer'), 'selesai') === false) {
                $data = $data->whereNull('paraf_kasubagumum_pada')->whereNull('diteruskan_ke_kadis')->doesntHave('disposisis');
            } else {
                $data = $data->whereNotNull('paraf_kasubagumum_pada')->whereNotNull('diteruskan_ke_kadis')->latest('diteruskan_ke_kadis');
            }
        }
        if (e_office_user()->is_kadis()) {
            if (strpos($request->header('referer'), 'selesai') === false) {
                $data = $data->whereNotNull('diteruskan_ke_kadis')->doesntHave('disposisis');
            } else {
                $data = $data->whereNotNull('diteruskan_ke_kadis')->whereNotNull('disposisi_pada')->latest('disposisi_pada');
            }
        }
        if (e_office_user()->is_kabid()) {
            if (strpos($request->header('referer'), 'selesai') === false) {
                $data = $data->whereHas('disposisis', function ($q) {
                    $q->where('pejabat_id', e_office_user()->pejabat->id)->whereNull('teruskan_ke_whatsapp_pada');
                });
            } else {
                $data = $data->whereHas('disposisis', function ($q) {
                    $q->where('pejabat_id', e_office_user()->pejabat->id)->whereNotNull('dibaca_pada')->WhereNotNull('teruskan_ke_whatsapp_pada')->orWhereNotNull('diarsip_pada');
                });
            }
        }
        $data->latest('created_at');
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('status', function ($row) use ($user) {
                $status = null;
                if ($user->is_kabid()) {
                    $up = $row->disposisis->where('pejabat_id', $user->pejabat->id)->first();
                    if ($up?->dibaca_pada && $up?->teruskan_ke_whatsapp_pada) {
                        $status = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Dibaca & Diteruskan</span>';
                    } elseif ($up?->dibaca_pada && !$up?->teruskan_ke_whatsapp_pada) {
                        $status = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Sudah Dibaca</span>';
                    } else {
                        $status = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Belum Dibaca</span>';
                    }
                } elseif ($user->is_kadis()) {
                    $status = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700">Belum Disposisi</span>';
                    if ($row->disposisi_pada) {
                        $status = '<span class="text-xs text-gray-500">Diteruskan kepada</span><br>';
                        $status .= collect($row->disposisis)->map(fn($item) => strtoupper($item->pejabat->jabatan))->join(', ');
                        $status .= '<br><span class="inline-flex items-center gap-1 px-2 py-0.5 mt-1 rounded text-[10px] font-medium bg-orange-100 text-gray-500"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' . $row->disposisi_pada?->diffForHumans() . '</span>';
                    }
                } elseif ($user->is_kasubag()) {
                    $status = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700">Belum Diparaf</span>';
                    if ($row->sudah_paraf()) {
                        $status = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Sudah Diparaf</span>';
                        $status .= '<br><span class="inline-flex items-center gap-1 px-2 py-0.5 mt-1 rounded text-[10px] font-medium bg-orange-100 text-gray-500"><svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' . $row->paraf_kasubagumum_pada?->diffForHumans() . '</span>';
                    }
                } elseif ($user->is_operator()) {
                    $status = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Belum Diproses</span>';
                    if ($row->paraf_kasubagumum_pada && $row->diteruskan_ke_kadis) {
                        $status = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Sudah Diproses</span>';
                    }
                }
                return $status;
            })
            ->addColumn('tanggal_terima', function ($row) {
                return $row->tanggal_terima->translatedFormat('d F Y');
            })
            ->addColumn('tanggal_surat', function ($row) {
                return $row->tanggal_surat->translatedFormat('d F Y');
            })
            ->addColumn('action', function ($row) use ($user) {
                $btn = '<div class="flex items-center gap-1">';
                $arsip = $row->disposisis->where('pejabat_id', $user->pejabat->id)->first();
                if ($arsip && $arsip->disposisi_pdf && media_exists($arsip->disposisi_pdf)) {
                    $btn .= '<a href="' . $arsip->disposisi_pdf . '" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors"><i class="fa fa-download"></i></a>';
                }
                $btn .= '<a href="' . plugin_route('e-office.surat-masuk.show', $row->id) . '" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-colors"><i class="fa fa-eye"></i></a>';
                $btn .= $user->is_kasubag() || $user->is_operator() ? '<a href="' . plugin_route('e-office.surat-masuk.edit', $row->id) . '" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors"><i class="fa fa-edit"></i></a>' : null;
                $btn .= $user->is_kasubag() || $user->is_operator() ? '<a href="' . plugin_route('e-office.surat-masuk.destroy', $row->id) . '" onclick="return confirm(\'Anda yakin untuk menghapus ?\')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors"><i class="fa fa-trash-o"></i></a>' : null;
                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['status', 'action'])
            ->toJson();
    }

    public function destroy(Arsip $arsip)
    {
        e_office_auth_check();
        if (e_office_user()->is_operator() || e_office_user()->is_kasubag()) {
            $arsip->delete();
            return redirect(plugin_route('e-office.surat-masuk.index'))->with('success', 'Surat berhasil dihapus');
        }
        abort(403, 'Akses dibatasi');
    }

    public function riwayat(Request $request)
    {
        e_office_auth_check();
        $data = Arsip::with('user.pejabat', 'disposisis.pejabat')->orderBy('nomor_agenda');
        return DataTables::of($data)
            ->addIndexColumn()
            ->addColumn('status', function ($row) {
                $status = null;
                if ($row->disposisis->count()) {
                    $status .= 'Sudah disposisi ke : <br>';
                    $status .= collect($row->disposisis)->map(fn($item) => strtoupper($item->pejabat->jabatan))->join(', ');
                } else {
                    $status = 'Belum Disposisi';
                }
                return $status;
            })
            ->filter(function ($instance) use ($request) {
                if ($cari = $request->cari) {
                    $instance->where('nomor_surat', 'like', '%' . $cari . '%')
                        ->orWhere('surat_dari', 'like', '%' . $cari . '%')
                        ->orWhere('tanggal_surat', 'like', '%' . $cari . '%')
                        ->orWhere('tanggal_terima', 'like', '%' . $cari . '%')
                        ->orWhere('hal', 'like', '%' . $cari . '%');
                }
                if ($request->tanggal_mulai && !$request->tanggal_akhir) {
                    $instance->whereDate('tanggal_terima', '>=', $request->tanggal_mulai);
                }
                if ($request->tanggal_mulai && $request->tanggal_akhir) {
                    $instance->whereBetween('tanggal_terima', [$request->tanggal_mulai, $request->tanggal_akhir]);
                }
            })
            ->addColumn('tanggal_terima', function ($row) {
                return $row->tanggal_terima->format('d/m/Y');
            })
            ->addColumn('tanggal_surat', function ($row) {
                return $row->tanggal_surat->format('d/m/Y');
            })
            ->rawColumns(['status'])
            ->toJson();
    }
}
