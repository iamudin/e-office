<?php

namespace App\Http\Controllers\Plugins\EOffice\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Plugins\EOffice\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Controllers\Plugins\EOffice\Middleware\RedirectMiddleware;

class LoginController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            RedirectMiddleware::handle(),
        ];
    }

    public function showLogin()
    {
        plugin_page_name('Login ke E-Office');
        if (session()->has('e_office_user_id')) {
            $user = e_office_user();
            if ($user && in_array($user->level, ['e-office', 'admin'])) {
                return redirect(plugin_route('dashboard'));
            }
        }
        return view('e-office::auth.login');
    }

    public function login(Request $request)
    {
        $ip = $request->ip();
        $rawUsername = Str::lower(trim((string) $request->input('username', '')));
        $throttleKey = 'e_office_login:' . $ip . '|' . $rawUsername;
        $ipThrottleKey = 'e_office_login_ip:' . $ip;

        // 1. Anti Brute Force & Credential Stuffing (Rate Limiter)
        // Batasi maksimal 5 percobaan per username/IP atau 15 percobaan per IP
        if (RateLimiter::tooManyAttempts($throttleKey, 5) || RateLimiter::tooManyAttempts($ipThrottleKey, 15)) {
            $seconds = max(
                RateLimiter::availableIn($throttleKey),
                RateLimiter::availableIn($ipThrottleKey)
            );

            Log::channel('daily')->warning("E-Office login throttled for IP [{$ip}] and username [{$rawUsername}]. Lockout: {$seconds}s.");

            return back()
                ->with('error', "Terlalu banyak percobaan login yang gagal. Demi keamanan, silakan tunggu {$seconds} detik sebelum mencoba kembali.")
                ->withInput($request->only('username'));
        }

        // 2. Strict Input Validation (SQL Injection, Type Juggling & Payload Sanitization)
        $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:128',
            'captcha' => 'required|string|max:10',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:100000',
            'location_name' => 'nullable|string|max:500',
        ], [
            'username.required' => 'Username atau email wajib diisi.',
            'username.max' => 'Username melebihi batas panjang karakter yang diizinkan.',
            'password.required' => 'Password wajib diisi.',
            'captcha.required' => 'Kode captcha wajib diisi.',
            'latitude.required' => 'Izin akses titik lokasi (GPS) wajib diaktifkan dan diizinkan pada browser Anda.',
            'latitude.between' => 'Titik koordinat latitude tidak valid.',
            'longitude.required' => 'Izin akses titik lokasi (GPS) wajib diaktifkan dan diizinkan pada browser Anda.',
            'longitude.between' => 'Titik koordinat longitude tidak valid.',
        ]);

        // 3. Captcha Check & Replay Prevention
        if (!captcha_check($request->captcha)) {
            RateLimiter::hit($throttleKey, 300);
            RateLimiter::hit($ipThrottleKey, 300);
            $request->session()->regenerateToken();

            return back()
                ->with('error', 'Kode Captcha salah atau sudah kedaluwarsa. Silakan ketik kode yang baru.')
                ->withInput($request->only('username'));
        }

        // 4. Timing Attack Mitigation & Credential Verification
        $field = filter_var($rawUsername, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $rawUsername)->first();

        // Dummy hash standard bcrypt cost 10 agar waktu komputasi Hash::check identik walau user tidak ada di database
        $dummyHash = '$2y$10$wT0Ems2/dFq25r/q05d1kOD9r3Yt1Bf5mQ3/vP4F6W2aPzL6l7Ofe';
        $isPasswordValid = false;

        if ($user) {
            $isPasswordValid = Hash::check($request->password, $user->password);
        } else {
            Hash::check($request->password, $dummyHash);
        }

        // 5. Auth Decision & Generic Error Messages (Preventing Username Enumeration)
        if (!$user || !$isPasswordValid) {
            RateLimiter::hit($throttleKey, 300);
            RateLimiter::hit($ipThrottleKey, 300);
            $request->session()->regenerateToken();

            Log::channel('daily')->notice("Gagal login E-Office untuk user: [{$rawUsername}] dari IP: {$ip}");

            return back()
                ->with('error', 'Username atau password salah.')
                ->withInput($request->only('username'));
        }

        if (!in_array($user->level, ['e-office', 'admin'])) {
            RateLimiter::hit($throttleKey, 300);
            RateLimiter::hit($ipThrottleKey, 300);
            $request->session()->regenerateToken();

            return back()
                ->with('error', 'Anda tidak memiliki hak akses ke sistem E-Office.')
                ->withInput($request->only('username'));
        }

        if ($user->status !== 'active') {
            RateLimiter::hit($throttleKey, 300);
            RateLimiter::hit($ipThrottleKey, 300);
            $request->session()->regenerateToken();

            return back()
                ->with('error', 'Akun Anda sedang dinonaktifkan. Silakan hubungi administrator.')
                ->withInput($request->only('username'));
        }

        // 6. Validasi Koordinat GPS & Sanitasi Lokasi (Stored XSS Prevention)
        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');
        $acc = $request->filled('accuracy') ? (float) $request->input('accuracy') : null;

        $rawLocName = strip_tags(trim((string) $request->input('location_name', '')));
        $locName = mb_substr($rawLocName, 0, 255);

        if ((!$locName || str_contains(strtolower($locName), 'memuat')) && $lat && $lng) {
            $geoLocName = e_office_reverse_geocode($lat, $lng);
            if ($geoLocName) {
                $locName = mb_substr(strip_tags(trim((string) $geoLocName)), 0, 255);
            }
        }

        $formattedLocName = $locName ?: ('Titik Koordinat (' . round($lat, 5) . ', ' . round($lng, 5) . ')');

        // 7. Update User & Pejabat Data
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);

        $pejabat = $user->pejabat;
        if ($pejabat) {
            $updateData = [
                'last_active_at' => now(),
                'last_activity' => 'Berhasil Login ke E-Office',
                'last_activity_url' => url()->current(),
                'login_at' => now(),
                'login_lat' => $lat,
                'login_lng' => $lng,
                'login_accuracy' => $acc,
                'login_location_name' => $formattedLocName,
                'last_lat' => $lat,
                'last_lng' => $lng,
                'last_accuracy' => $acc,
                'last_location_name' => $formattedLocName,
            ];

            \App\Models\Plugins\EOffice\PejabatLocation::create([
                'pejabat_id' => $pejabat->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => $acc,
                'location_name' => $formattedLocName,
                'activity' => 'Login ke E-Office',
                'activity_url' => plugin_route('login'),
                'ip_address' => $ip,
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'created_at' => now(),
            ]);

            $pejabat->update($updateData);
        }

        // 8. Bersihkan Rate Limiter setelah login sukses
        RateLimiter::clear($throttleKey);
        RateLimiter::clear($ipThrottleKey);

        // 9. Session Fixation & Hijacking Prevention
        $request->session()->regenerate();
        session([
            'e_office_user_id' => $user->id,
            'e_office_login_ip' => $ip,
            'e_office_user_agent' => md5((string) $request->userAgent()),
        ]);

        return redirect()->intended(plugin_route('dashboard'));
    }

    public function logout(Request $request)
    {
        session()->forget(['e_office_user_id', 'e_office_login_ip', 'e_office_user_agent']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(plugin_route('login'));
    }
}

