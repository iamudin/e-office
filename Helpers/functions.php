<?php

// ─── Bootstrap earsip config (replaces ServiceProvider mergeConfigFrom) ───
if (!config('earsip')) {
    config(['earsip' => [
        'name'        => 'E-Arsip',
        'url'         => env('APP_URL_EARSIP', null),
        'path'        => 'earsip',
        'logo'        => env('APP_LOGO_EARSIP', '/noimage.webp'),
        'title'       => env('APP_TITLE_EARSIP', 'e-Arsip'),
        'description' => env('APP_DESCRIPTION_EARSIP', 'Arsip Surat Masuk Elektronik'),
        'api'         => [
            'wa_sender' => [
                'url'     => env('WA_SENDER_URL', null),
                'session' => env('WA_SENDER_SESSION', null),
            ],
        ],
    ]]);
}

// ─── Helper: Get earsip user (lazy-loaded) ───
if (!function_exists('earsip_user')) {
    function earsip_user()
    {
        static $cached = null;
        if ($cached === null && session()->has('earsip_user_id')) {
            $cached = \App\Models\Plugins\Earsip\User::with('pejabat')->find(session('earsip_user_id'));
        }
        return $cached;
    }
}


// ─── Helper: Auth guard for earsip controllers ───
if (!function_exists('earsip_auth_check')) {
    function earsip_auth_check()
    {
        if (!session()->has('earsip_user_id')) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect(plugin_route('login'))->with('error', 'Silahkan login terlebih dahulu')
            );
        }
        $user = earsip_user();
        if (!$user || !in_array($user->level, ['earsip', 'admin'])) {
            session()->forget('earsip_user_id');
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect(plugin_route('login'))->with('error', 'Anda tidak memiliki akses ke sistem ini')
            );
        }
    }
}

// ─── Helper: Check if current user is admin ───
if (!function_exists('earsip_admin_only')) {
    function earsip_admin_only()
    {
        earsip_auth_check();
        if (!earsip_user()->isAdmin()) {
            abort(403, 'Hanya admin yang dapat mengakses halaman ini');
        }
    }
}
