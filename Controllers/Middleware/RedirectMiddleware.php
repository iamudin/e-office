<?php

namespace App\Http\Controllers\Plugins\EOffice\Middleware;

use Illuminate\Http\Request;
use Closure;

class RedirectMiddleware
{
    public static function handle()
    {
        return function (Request $request, Closure $next) {
            $customDomain = get_option('e-office-domain');
            $host = $request->getHost();
            $path = $request->path();

            // Cek apakah ini rute versi "pendek" (tanpa e-office/)
            $isShortPath = !preg_match('/^e-office(\/|$)/', ltrim($path, '/'));

            if ($customDomain) {
                if ($host !== $customDomain) {
                    // Jika akses dari domain utama tapi punya custom domain -> redirect ke custom domain
                    $newPath = preg_replace('/^e-office\/?/', '', ltrim($path, '/'));
                    $url = $request->getScheme() . '://' . $customDomain . '/' . ltrim($newPath, '/');
                    if ($request->getQueryString()) {
                        $url .= '?' . $request->getQueryString();
                    }
                    return redirect()->to($url);
                } else {
                    // Jika di custom domain tapi pakai rute panjang -> redirect ke rute pendek
                    if (!$isShortPath) {
                        $newPath = preg_replace('/^e-office\/?/', '', ltrim($path, '/'));
                        $url = $request->getScheme() . '://' . $customDomain . '/' . ltrim($newPath, '/');
                        if ($request->getQueryString()) {
                            $url .= '?' . $request->getQueryString();
                        }
                        return redirect()->to($url);
                    }
                }
            } else {
                // Jika TIDAK punya custom domain, maka WAJIB pakai rute panjang (e-office/...)
                if ($isShortPath) {
                    abort(404);
                }

                // Jika diakses dari host asing (misal bekas custom domain) saat tidak ada setting custom domain,
                // arahkan kembali ke domain utama/tenant aktif.
                $activeDomain = config('modules.multisite_enabled') && function_exists('tenant') && tenant()
                    ? tenant()->domain
                    : parse_url(config('app.url'), PHP_URL_HOST);

                if ($host !== $activeDomain && $activeDomain) {
                    $url = $request->getScheme() . '://' . $activeDomain . '/' . ltrim($path, '/');
                    if ($request->getQueryString()) {
                        $url .= '?' . $request->getQueryString();
                    }
                    return redirect()->to($url);
                }
            }

            return $next($request);
        };
    }
}
