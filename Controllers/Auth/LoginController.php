<?php

namespace App\Http\Controllers\Plugins\EOffice\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        if (session()->has('e_office_user_id')) {
            $user = e_office_user();
            if ($user && in_array($user->level, ['e-office'])) {
                return redirect(plugin_route('dashboard'));
            }
        }
        return view('e-office::auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($request->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $request->username)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            if (!in_array($user->level, ['e-office'])) {
                return back()->with('error', 'Anda tidak memiliki akses ke sistem ini');
            }

            if ($user->status !== 'active') {
                return back()->with('error', 'Akun anda sedang dinonaktifkan');
            }

            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            session(['e_office_user_id' => $user->id]);
            $request->session()->regenerate();
            return redirect()->intended(plugin_route('dashboard'));

        }

        return back()->with('error', 'Username atau password salah')->withInput($request->only('username'));
    }

    public function logout(Request $request)
    {
        session()->forget('e_office_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect(plugin_route('login'));
    }
}
