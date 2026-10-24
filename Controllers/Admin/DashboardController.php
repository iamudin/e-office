<?php
namespace App\Http\Controllers\Plugins\EOffice\Admin;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\View;
use App\Models\Plugins\EOffice\Arsip;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Http\Controllers\Plugins\EOffice\Middleware\RedirectMiddleware;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(RedirectMiddleware::handle(), only: ['index']),
        ];
    }


    function pengaturan(Request $request){
        if (!auth()->user() || !auth()->user()->isAdmin()) abort(403, 'Akses ditolak');

        $customDomain = get_option('e-office-domain');

        if($request->isMethod('PUT')){
            $data = $request->validate([
                'api_url' => 'required|url',
                'api_session' => 'required|string',
                'custom_domain' => 'nullable|string|max:100',
            ]);
            
            rewrite_env([
                'WA_HOST' => $data['api_url'],
                'WA_SESSION' => $data['api_session'],
            ]);

            $domain = $request->custom_domain;
            if (filter_var($domain, FILTER_VALIDATE_URL)) {
                $domain = parse_url($domain, PHP_URL_HOST);
            }
            
            $optionKey = ['name' => 'e-office-domain'];
            if (config('modules.multisite_enabled')) {
                $optionKey['tenant_id'] = tenant() ? tenant()->id : null;
            }

            if ($domain) {
                \Leazycms\Web\Models\Option::updateOrCreate(
                    $optionKey,
                    ['value' => $domain, 'autoload' => 1]
                );
            } else {
                \Leazycms\Web\Models\Option::where($optionKey)->delete();
            }

            cache()->flush(); // Flush cache so new domain is immediately recognized
            
            return back()->with('success','Pengaturan berhasil disimpan');
        }
        return view('e-office::admin.appconfig', compact('customDomain'));
    }
    function judulPeriode($tanggalMulai, $tanggalAkhir)
    {
        Carbon::setLocale('id');
    
        $mulai = Carbon::parse($tanggalMulai);
        $akhir = Carbon::parse($tanggalAkhir);
    
        if ($mulai->gt($akhir)) {
            [$mulai, $akhir] = [$akhir, $mulai];
        }
    
        if ($mulai->eq($akhir)) {
            return $mulai->translatedFormat('j F Y');
        }
    
        if ($mulai->format('Y-m') === $akhir->format('Y-m')) {
            if ($mulai->day === 1 && $akhir->day === $mulai->copy()->endOfMonth()->day) {
                return $mulai->translatedFormat('F Y');
            }
            return $mulai->format('j') . ' s/d ' . $akhir->translatedFormat('j F Y');
        }
    
        if ($mulai->year === $akhir->year) {
            return $mulai->translatedFormat('j F') . ' s/d ' . $akhir->translatedFormat('j F Y');
        }
    
        return $mulai->translatedFormat('j F Y') . ' s/d ' . $akhir->translatedFormat('j F Y');
    }
public function index(){
    plugin_page_name('Dashboard - E-Office');
    e_office_auth_check();

    // Jalankan pemeriksaan pengiriman peringatan WA otomatis dengan cache throttle 3 menit
    if (e_briefing_enabled()) {
        try {
            $lastRun = \Illuminate\Support\Facades\Cache::get('briefing_reminder_last_run');
            if (!$lastRun || now()->diffInMinutes($lastRun) >= 3) {
                \Illuminate\Support\Facades\Cache::put('briefing_reminder_last_run', now(), 180);
                e_office_check_and_send_briefing_reminders();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Briefing reminder check error: ' . $e->getMessage());
        }
    }

    $user = e_office_user();
    $pejabat = $user?->pejabat;
    $isKadis = false;
    if ($pejabat && $pejabat->isKadis()) {
        $isKadis = true;
    }

    $jamForward = e_office_get_option('briefing_warning_time_forward', '09:00');
    $jamAction = e_office_get_option('briefing_warning_time_action', '14:00');
    $warningEnabled = e_office_get_option('briefing_warning_enabled', '1') == '1';
    $autoWeekend = e_office_get_option('briefing_warning_auto_weekend', '1') == '1';

    $dashboardReminders = [];
    if (e_briefing_enabled() && $pejabat) {
        $dashboardReminders = e_office_get_dashboard_reminders($pejabat);
    }

    $data = [
        'dashboardReminders' => $dashboardReminders,
        'jamForward' => $jamForward,
        'jamAction' => $jamAction,
        'warningEnabled' => $warningEnabled,
        'autoWeekend' => $autoWeekend,
        'isKadis' => $isKadis,
        'pejabat' => $pejabat,
    ];

    if($user && ($user->is_operator() || $user->is_kasubag())){
        $data['periode'] = $this->judulPeriode(
            request('tanggal_mulai',date('Y-m-d')),
            request('tanggal_akhir',date('Y-m-d'))
        );
        if(request('cetak_rekap') && request('tanggal_mulai')){
            $query = Arsip::with('user.pejabat', 'disposisis.pejabat')
            ->whereBetween('tanggal_terima', [request('tanggal_mulai',date('Y-m-d')),request('tanggal_akhir',date('Y-m-d'))])
            ->orderBy('nomor_agenda')->get();
            $html = View::make('e-office::pdf.rekap', [
                    'rekap' => $query,
                    'periode' => $data['periode'],
            ])->render();
            $pdf = PDF::loadHTML($html)->setOption('page-width', '330')->setPaper('a4', 'landscape');
            return $pdf->stream('rekap-'.date('Y-m-d').'.pdf');
        }
    }
  
    return view('e-office::admin.dashboard', $data);
}

}
