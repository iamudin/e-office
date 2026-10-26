<?php

// ─── Bootstrap earsip config (replaces ServiceProvider mergeConfigFrom) ───
if (!config('e-office')) {
    config([
        'e-office' => [
            'name' => 'E-Arsip',
            'url' => env('APP_URL_EARSIP', null),
            'path' => 'e-office',
            'logo' => env('APP_LOGO_EARSIP', '/noimage.webp'),
            'title' => env('APP_TITLE_EARSIP', 'e-Arsip'),
            'description' => env('APP_DESCRIPTION_EARSIP', 'Arsip Surat Masuk Elektronik'),
            'api' => [
                'wa_sender' => [
                    'url' => env('WA_SENDER_URL', null),
                    'session' => env('WA_SENDER_SESSION', null),
                ],
            ],
        ]
    ]);
}

// ─── Helper: Get earsip user (lazy-loaded) ───
if (!function_exists('e_office_user')) {
    function e_office_user()
    {
        static $cached = null;
        $sessionId = session('e_office_user_id');
        if (!$sessionId) {
            $cached = null;
            return null;
        }
        if ($cached === null || $cached->id != $sessionId) {
            $cached = \App\Models\Plugins\EOffice\User::with('pejabat')->find($sessionId);
        }
        return $cached;
    }
}


// ─── Helper: Auth guard for earsip controllers ───
if (!function_exists('e_office_auth_check')) {
    function e_office_auth_check()
    {
        if (!session()->has('e_office_user_id')) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect(plugin_route('login'))->with('error', 'Silahkan login terlebih dahulu')
            );
        }
        $user = e_office_user();
        if (!$user || !in_array($user->level, ['e-office', 'admin']) || ($user->status ?? 'active') !== 'active') {
            session()->forget(['e_office_user_id', 'e_office_login_ip', 'e_office_user_agent']);
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect(plugin_route('login'))->with('error', 'Sesi tidak valid atau akun dinonaktifkan')
            );
        }
    }
}

// ─── Helper: Check if current user is admin ───
if (!function_exists('e_office_admin_only')) {
    function e_office_admin_only()
    {
        e_office_auth_check();
        if (!e_office_user()->isAdmin()) {
            abort(403, 'Hanya admin yang dapat mengakses halaman ini');
        }
    }
}

// ─── Helper: Check if briefing feature is enabled via .env ───
if (!function_exists('e_briefing_enabled')) {
    function e_briefing_enabled(): bool
    {
        return config('e-office.e_briefing_enabled', false);
    }
}

// ─── Helper: Reverse geocode via OpenStreetMap Nominatim ───
if (!function_exists('e_office_reverse_geocode')) {
    function e_office_reverse_geocode($lat, $lng)
    {
        if (!$lat || !$lng)
            return null;

        $cacheKey = 'osm_geo_' . round($lat, 3) . '_' . round($lng, 3);
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () use ($lat, $lng) {
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'EOffice-App/1.0 (leazycms; contact@leazycms.test)'
                ])->timeout(3)->get('https://nominatim.openstreetmap.org/reverse', [
                            'format' => 'jsonv2',
                            'lat' => $lat,
                            'lon' => $lng,
                            'zoom' => 18,
                            'addressdetails' => 1,
                        ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['display_name'] ?? null;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('OSM reverse geocoding error: ' . $e->getMessage());
            }
            return null;
        });
    }
}

// ─── Helper: Static cache for e-office options ───
if (!function_exists('e_office_options_static_cache')) {
    function &e_office_options_static_cache()
    {
        static $cache = [];
        return $cache;
    }
}

// ─── Helper: Get e-office option from database (with static cache) ───
if (!function_exists('e_office_get_option')) {
    function e_office_get_option($key, $default = null, $refresh = false)
    {
        $cache = &e_office_options_static_cache();

        if (!$refresh && array_key_exists($key, $cache)) {
            return $cache[$key] !== null ? $cache[$key] : $default;
        }

        if (class_exists(\Leazycms\Web\Models\Option::class)) {
            $val = \Leazycms\Web\Models\Option::withoutGlobalScope('tenant')->where('name', $key)->value('value');
            if ($val !== null && $val !== '') {
                $cache[$key] = $val;
                return $val;
            }
        }

        $cache[$key] = null;
        return $default;
    }
}

// ─── Helper: Set e-office option in database ───
if (!function_exists('e_office_set_option')) {
    function e_office_set_option($key, $value)
    {
        $cache = &e_office_options_static_cache();
        $cache[$key] = $value;

        if (class_exists(\Leazycms\Web\Models\Option::class)) {
            \Leazycms\Web\Models\Option::withoutGlobalScope('tenant')->updateOrCreate(
                ['name' => $key],
                ['value' => $value, 'autoload' => 1]
            );
            return true;
        }
        return false;
    }
}

// ─── Helper: Delete e-office option from database ───
if (!function_exists('e_office_delete_option')) {
    function e_office_delete_option($key)
    {
        $cache = &e_office_options_static_cache();
        unset($cache[$key]);

        if (class_exists(\Leazycms\Web\Models\Option::class)) {
            return \Leazycms\Web\Models\Option::withoutGlobalScope('tenant')
                ->where('name', $key)
                ->delete();
        }
        return false;
    }
}

// ─── Helper: Delete e-office options by pattern (like) from database ───
if (!function_exists('e_office_delete_options_like')) {
    function e_office_delete_options_like($pattern, $excludeKey = null)
    {
        $cache = &e_office_options_static_cache();
        $cache = [];

        if (class_exists(\Leazycms\Web\Models\Option::class)) {
            $query = \Leazycms\Web\Models\Option::withoutGlobalScope('tenant')
                ->where('name', 'like', $pattern);
            if ($excludeKey) {
                $query->where('name', '!=', $excludeKey);
            }
            return $query->delete();
        }
        return false;
    }
}


// ─── Helper: Check & dispatch briefing WhatsApp & In-App reminders ───
if (!function_exists('e_office_check_and_send_briefing_reminders')) {
    function e_office_check_and_send_briefing_reminders()
    {
        if (!e_briefing_enabled()) {
            return;
        }

        $now = now();
        $currentTime = $now->format('H:i');
        $today = $now->toDateString();

        // Bersihkan tanda peringatan hari-hari sebelumnya agar tabel options tidak menumpuk
        if (class_exists(\Leazycms\Web\Models\Option::class)) {
            \Leazycms\Web\Models\Option::withoutGlobalScope('tenant')
                ->where('name', 'like', 'briefing_wa_forward_sent_%')
                ->where('name', 'not like', '%_' . $today)
                ->delete();
        }

        $warningEnabled = e_office_get_option('briefing_warning_enabled', '1') == '1';
        $autoWeekend = e_office_get_option('briefing_warning_auto_weekend', '1') == '1';

        // Cek jika peringatan dinonaktifkan oleh Pimpinan (Hari Libur / Cuti)
        if (!$warningEnabled) {
            return;
        }

        // Cek jika hari ini akhir pekan (Sabtu/Minggu) dan opsi auto weekend aktif
        if ($autoWeekend && $now->isWeekend()) {
            return;
        }

        $jamForward = e_office_get_option('briefing_warning_time_forward', '09:00');
        $jamAction = e_office_get_option('briefing_warning_time_action', '14:00');

        $kadis = \App\Models\Plugins\EOffice\Pejabat::where('alias_jabatan', 'KADIS')->first()
            ?? \App\Models\Plugins\EOffice\Pejabat::whereNull('atasan_id')->first();

        // ══════════════════════════════════════════════════════════════════
        // ATURAN 1: Bawahan Pimpinan (Sekretaris / Kabid) Belum Memberikan Tugas
        // Waktu: >= $jamForward (default 09:00)
        // ══════════════════════════════════════════════════════════════════
        if ($currentTime >= $jamForward) {
            $pejabatSatuTingkat = \App\Models\Plugins\EOffice\Pejabat::with(['user', 'staff'])
                ->where(function ($q) use ($kadis) {
                    if ($kadis) {
                        $q->where('atasan_id', $kadis->id);
                    }
                    $q->orWhereIn('alias_jabatan', ['SEKRETARIS', 'KABID']);
                })
                ->where('id', '!=', $kadis?->id)
                ->whereNotNull('user_id')
                ->get();

            foreach ($pejabatSatuTingkat as $p) {
                if (!$p->hasSubordinates()) {
                    continue;
                }

                $sudahBuatHariIni = \App\Models\Plugins\EOffice\Briefing::where('user_id', $p->user_id)
                    ->whereDate('created_at', $today)
                    ->exists();

                $sudahTeruskanHariIni = \App\Models\Plugins\EOffice\BriefingDisposisi::where('pengirim_id', $p->id)
                    ->whereDate('created_at', $today)
                    ->exists();

                if ($sudahBuatHariIni || $sudahTeruskanHariIni) {
                    // Jika pejabat sudah menerbitkan/meneruskan tugas hari ini, bersihkan tanda peringatan di options
                    if (class_exists(\Leazycms\Web\Models\Option::class)) {
                        \Leazycms\Web\Models\Option::withoutGlobalScope('tenant')
                            ->where('name', 'like', 'briefing_wa_forward_sent_' . $p->id . '_%')
                            ->delete();
                    }
                    continue;
                }

                $sentKey = 'briefing_wa_forward_sent_' . $p->id . '_' . $today;
                if (!e_office_get_option($sentKey)) {
                    $urlBriefing = plugin_route('e-office.briefing.create');

                    // 1. WhatsApp Reminder
                    if ($p->nohp) {
                        $pesanWa = "*PERINGATAN BRIEFING / TUGAS*\n\n"
                            . "Yth. Bapak/Ibu *{$p->nama}*\n"
                            . "({$p->jabatan})\n\n"
                            . "Berdasarkan monitoring sistem E-Office, hingga batas waktu pukul *{$jamForward} WIB* Anda tercatat *BELUM MEMBUAT ATAU MENERUSKAN ARAHAN TUGAS* kepada staf bawahan Anda untuk hari ini (" . $now->translatedFormat('l, d F Y') . ").\n\n"
                            . "Sesuai arahan Kepala Dinas (Pimpinan), mohon segera menerbitkan atau meneruskan instruksi tugas kepada staf bawahan Anda melalui sistem E-Office di tautan berikut:\n"
                            . $urlBriefing . "\n\n"
                            . "Terima kasih.";

                        \App\Models\Plugins\EOffice\WaSender::dispatch([
                            'to' => $p->nohp,
                            'type' => 'text',
                            'text' => $pesanWa,
                        ]);
                    }

                    // 2. In-App Notification
                    if ($p->user_id && class_exists(\Leazycms\Web\Models\Notification::class)) {
                        \Leazycms\Web\Models\Notification::create([
                            'user_id' => $p->user_id,
                            'title' => 'Peringatan: Belum Memberikan Arahan Tugas',
                            'message' => "Hingga batas waktu pukul {$jamForward} WIB Anda belum membuat atau meneruskan arahan tugas kepada staf bawahan hari ini.",
                            'type' => 'warning',
                            'url' => $urlBriefing,
                            'is_read' => 0,
                        ]);
                    }

                    // Hapus key lama sebelumnya milik pejabat ini dari tabel options agar tidak menumpuk
                    if (class_exists(\Leazycms\Web\Models\Option::class)) {
                        \Leazycms\Web\Models\Option::withoutGlobalScope('tenant')
                            ->where('name', 'like', 'briefing_wa_forward_sent_' . $p->id . '_%')
                            ->where('name', '!=', $sentKey)
                            ->delete();
                    }

                    e_office_set_option($sentKey, $now->toDateTimeString());
                }
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // ATURAN 2: Bawahan Yang Sudah Menerima Instruksi Tapi Belum Buka / Aksi
        // Waktu: >= $jamAction (default 14:00)
        // ══════════════════════════════════════════════════════════════════
        if ($currentTime >= $jamAction) {
            $disposisis = \App\Models\Plugins\EOffice\BriefingDisposisi::with(['briefing.user.pejabat', 'penerima.user', 'pengirim'])
                ->whereNull('notif_wa_14_at')
                ->whereHas('briefing', function ($q) {
                    $q->where('status', '!=', 'completed');
                })
                ->whereDate('created_at', $today)
                ->get();

            foreach ($disposisis as $disp) {
                $penerima = $disp->penerima;
                if (!$penerima)
                    continue;

                $isBelumBuka = is_null($disp->dibaca_pada);
                $hasAksi = !is_null($disp->dibalas_pada) || \App\Models\Plugins\EOffice\BriefingChecklist::where('briefing_id', $disp->briefing_id)
                    ->where('penerima_id', $penerima->id)
                    ->whereIn('status', ['proses', 'selesai'])
                    ->exists();

                if ($isBelumBuka || !$hasAksi) {
                    $urlDetail = plugin_route('e-office.briefing.show', $disp->briefing_id);
                    $judulBriefing = $disp->briefing?->judul ?? 'Tugas Baru';
                    $pengirimNama = $disp->pengirim?->nama ?? ($disp->briefing?->user?->pejabat?->nama ?? 'Atasan');
                    $pengirimJabatan = $disp->pengirim?->jabatan ?? ($disp->briefing?->user?->pejabat?->jabatan ?? 'Atasan');
                    $statusKet = $isBelumBuka ? 'belum dibuka' : 'belum ditanggapi / belum ada progres aksi';

                    // 1. WhatsApp Reminder
                    if ($penerima->nohp) {
                        $pesanWa = "*PERINGATAN TINDAK LANJUT TUGAS*\n\n"
                            . "Yth. Bapak/Ibu *{$penerima->nama}*\n"
                            . "({$penerima->jabatan})\n\n"
                            . "Berdasarkan monitoring sistem E-Office, hingga batas waktu pukul *{$jamAction} WIB* tugas berikut tercatat *{$statusKet}*:\n\n"
                            . "• Judul Tugas: *{$judulBriefing}*\n"
                            . "• Dari: *{$pengirimNama}* ({$pengirimJabatan})\n"
                            . "• Waktu Diterima: " . $disp->created_at->format('d M Y, H:i') . " WIB\n\n"
                            . "Sesuai arahan Kepala Dinas (Pimpinan), mohon segera membuka dan menindaklanjuti tugas tersebut di sistem E-Office melalui link berikut:\n"
                            . $urlDetail . "\n\n"
                            . "Terima kasih.";

                        \App\Models\Plugins\EOffice\WaSender::dispatch([
                            'to' => $penerima->nohp,
                            'type' => 'text',
                            'text' => $pesanWa,
                        ]);
                    }

                    // 2. In-App Notification
                    if ($disp->briefing) {
                        $disp->briefing->addNotification([
                            'to_user' => $penerima->user_id,
                            'title' => 'Peringatan: Tugas Belum Ditindaklanjuti',
                            'message' => "Hingga batas waktu pukul {$jamAction} WIB tugas '{$judulBriefing}' belum Anda tindaklanjuti.",
                            'url' => $urlDetail,
                            'type' => 'warning'
                        ]);
                    } elseif ($penerima->user_id && class_exists(\Leazycms\Web\Models\Notification::class)) {
                        \Leazycms\Web\Models\Notification::create([
                            'user_id' => $penerima->user_id,
                            'title' => 'Peringatan: Tugas Belum Ditindaklanjuti',
                            'message' => "Hingga batas waktu pukul {$jamAction} WIB tugas '{$judulBriefing}' belum Anda tindaklanjuti.",
                            'type' => 'warning',
                            'url' => $urlDetail,
                            'is_read' => 0,
                        ]);
                    }

                    $disp->update(['notif_wa_14_at' => $now]);
                }
            }
        }
    }
}

// ─── Helper: Get active dashboard reminder alerts for a pejabat ───
if (!function_exists('e_office_get_dashboard_reminders')) {
    function e_office_get_dashboard_reminders($pejabat)
    {
        $reminders = [];
        if (!$pejabat || !e_briefing_enabled()) {
            return $reminders;
        }

        $now = now();
        $currentTime = $now->format('H:i');
        $today = $now->toDateString();

        $warningEnabled = e_office_get_option('briefing_warning_enabled', '1') == '1';
        $autoWeekend = e_office_get_option('briefing_warning_auto_weekend', '1') == '1';

        // Cek jika peringatan dinonaktifkan oleh Pimpinan (Hari Libur / Cuti)
        if (!$warningEnabled) {
            return $reminders;
        }

        // Cek jika hari ini akhir pekan (Sabtu/Minggu) dan opsi auto weekend aktif
        if ($autoWeekend && $now->isWeekend()) {
            return $reminders;
        }

        $jamForward = e_office_get_option('briefing_warning_time_forward', '09:00');
        $jamAction = e_office_get_option('briefing_warning_time_action', '14:00');

        $kadis = \App\Models\Plugins\EOffice\Pejabat::where('alias_jabatan', 'KADIS')->first()
            ?? \App\Models\Plugins\EOffice\Pejabat::whereNull('atasan_id')->first();

        // 1. Cek peringatan belum memberi tugas (Khusus Pejabat 1 Tingkat ber-bawahan, bukan Kadis)
        $isKadis = $pejabat->isKadis();
        $isPejabatSatuTingkat = !$isKadis && ($pejabat->atasan_id == $kadis?->id || in_array($pejabat->alias_jabatan, ['SEKRETARIS', 'KABID']));

        if ($isPejabatSatuTingkat && $pejabat->hasSubordinates()) {
            if ($currentTime >= $jamForward) {
                $sudahBuatHariIni = \App\Models\Plugins\EOffice\Briefing::where('user_id', $pejabat->user_id)
                    ->whereDate('created_at', $today)
                    ->exists();

                $sudahTeruskanHariIni = \App\Models\Plugins\EOffice\BriefingDisposisi::where('pengirim_id', $pejabat->id)
                    ->whereDate('created_at', $today)
                    ->exists();

                if (!$sudahBuatHariIni && !$sudahTeruskanHariIni) {
                    $reminders[] = [
                        'type' => 'forward',
                        'level' => 'danger',
                        'title' => 'Belum Memberikan Arahan Tugas Hari Ini',
                        'message' => "Hingga batas waktu pukul <strong>{$jamForward} WIB</strong>, Anda belum membuat briefing arahan tugas atau meneruskan tugas kepada staf bawahan Anda.",
                        'action_text' => 'Buat Briefing Tugas',
                        'action_url' => plugin_route('e-office.briefing.create'),
                        'icon' => 'fa-exclamation-triangle',
                        'deadline' => $jamForward,
                    ];
                }
            }
        }

        // 2. Cek peringatan belum buka / tindaklanjuti tugas yang diterima
        if ($currentTime >= $jamAction) {
            $disposisis = \App\Models\Plugins\EOffice\BriefingDisposisi::with(['briefing.user.pejabat', 'pengirim'])
                ->where('penerima_id', $pejabat->id)
                ->whereHas('briefing', function ($q) {
                    $q->where('status', '!=', 'completed');
                })
                ->whereDate('created_at', $today)
                ->get();

            foreach ($disposisis as $disp) {
                $isBelumBuka = is_null($disp->dibaca_pada);
                $hasAksi = !is_null($disp->dibalas_pada) || \App\Models\Plugins\EOffice\BriefingChecklist::where('briefing_id', $disp->briefing_id)
                    ->where('penerima_id', $pejabat->id)
                    ->whereIn('status', ['proses', 'selesai'])
                    ->exists();

                if ($isBelumBuka || !$hasAksi) {
                    $judul = $disp->briefing?->judul ?? 'Tugas';
                    $pengirim = $disp->pengirim?->nama ?? ($disp->briefing?->user?->pejabat?->nama ?? 'Atasan');
                    $status = $isBelumBuka ? 'belum dibuka' : 'belum ada respon tindak lanjut';

                    $reminders[] = [
                        'type' => 'action',
                        'level' => 'warning',
                        'title' => 'Tugas Belum Ditindaklanjuti',
                        'message' => "Tugas <strong>'" . e($judul) . "'</strong> dari " . e($pengirim) . " masih {$status} (batas waktu pukul <strong>{$jamAction} WIB</strong>).",
                        'action_text' => 'Buka & Tindak Lanjuti',
                        'action_url' => plugin_route('e-office.briefing.show', $disp->briefing_id),
                        'icon' => 'fa-clock-o',
                        'deadline' => $jamAction,
                    ];
                }
            }
        }

        return $reminders;
    }
}

// ─── Helper: Get application branding name ───
if (!function_exists('e_office_app_name')) {
    function e_office_app_name()
    {
        return e_office_get_option('e_office_app_name', config('e-office.title', 'e-Arsip'));
    }
}

// ─── Helper: Get application branding description ───
if (!function_exists('e_office_app_description')) {
    function e_office_app_description()
    {
        return e_office_get_option('e_office_app_description', config('e-office.description', 'Sistem Arsip Elektronik & Disposisi'));
    }
}

// ─── Helper: Get application branding logo URL ───
if (!function_exists('e_office_app_logo')) {
    function e_office_app_logo()
    {
        $logo = e_office_get_option('e_office_app_logo', null);
        if ($logo) {
            return str_starts_with($logo, 'http') ? $logo : asset($logo);
        }
        $configLogo = config('e-office.logo');
        if ($configLogo && $configLogo !== '/noimage.webp') {
            return str_starts_with($configLogo, 'http') ? $configLogo : asset($configLogo);
        }
        return null;
    }
}

// ─── Helper: Get application branding icon / favicon URL ───
if (!function_exists('e_office_app_icon')) {
    function e_office_app_icon()
    {
        $icon = e_office_get_option('e_office_app_icon', null);
        if ($icon) {
            return str_starts_with($icon, 'http') ? $icon : asset($icon);
        }
        return null;
    }
}
