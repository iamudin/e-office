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
                'url' => 'required|url',
                'logo' => 'required|url',
                'api_url' => 'required|url',
                'api_session' => 'required|string',
                'custom_domain' => 'nullable|string|max:100',
            ]);
            
            rewrite_env([
                'APP_URL_EARSIP' => $data['url'],
                'APP_LOGO_EARSIP' => $data['logo'],
                'WA_SENDER_URL' => $data['api_url'],
                'WA_SENDER_SESSION' => $data['api_session'],
            ]);

            $domain = $request->custom_domain;
            if (filter_var($domain, FILTER_VALIDATE_URL)) {
                $domain = parse_url($domain, PHP_URL_HOST);
            }
            
            if ($domain) {
                \Leazycms\Web\Models\Option::updateOrCreate(
                    ['name' => 'e-office-domain', 'tenant_id' => tenant() ? tenant()->id : null],
                    ['value' => $domain, 'autoload' => 1]
                );
            } else {
                \Leazycms\Web\Models\Option::where('name', 'e-office-domain')
                    ->where('tenant_id', tenant() ? tenant()->id : null)
                    ->delete();
            }

            Artisan::call('config:cache');
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
    $data = [];
    if(e_office_user()->is_operator() || e_office_user()->is_kasubag()){
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
  

    return view('e-office::admin.dashboard',$data);
}

}
