<?php

// ─── Bootstrap earsip config (replaces ServiceProvider mergeConfigFrom) ───
if (!config('e-office')) {
    config(['e-office' => [
        'name'        => 'E-Arsip',
        'url'         => env('APP_URL_EARSIP', null),
        'path'        => 'e-office',
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
if (!function_exists('e_office_user')) {
    function e_office_user()
    {
        static $cached = null;
        if ($cached === null && session()->has('e_office_user_id')) {
            $cached = \App\Models\Plugins\EOffice\User::with('pejabat')->find(session('e_office_user_id'));
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
        if (!$user || !in_array($user->level, ['e-office', 'admin'])) {
            session()->forget('e_office_user_id');
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect(plugin_route('login'))->with('error', 'Anda tidak memiliki akses ke sistem ini')
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
