<?php

namespace App\Http\Controllers\Plugins\EOffice\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugins\EOffice\Pejabat;
use App\Models\Plugins\EOffice\PejabatLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitoringLokasiController extends Controller
{
    public function __construct()
    {
        e_office_auth_check();
    }

    /**
     * Dapatkan daftar bawahan yang berhak dilihat oleh user saat ini
     */
    protected function getAuthorizedSubordinates($currentPejabat, $user)
    {
        $isKadis = false;
        if ($currentPejabat && (
            str_contains(strtolower($currentPejabat->alias_jabatan ?? ''), 'kadis') ||
            $currentPejabat->alias_jabatan === 'KADIS' ||
            is_null($currentPejabat->atasan_id)
        )) {
            $isKadis = true;
        }

        $isAdmin = $user && (method_exists($user, 'isAdmin') ? $user->isAdmin() : $user->level === 'admin');

        // Pimpinan Utama / Admin berhak melihat semua bawahan
        if ($isKadis || $isAdmin) {
            return Pejabat::whereHas('user', function ($q) {
                    $q->where('status', 'active');
                })
                ->when($currentPejabat, function ($q) use ($currentPejabat) {
                    $q->where('id', '!=', $currentPejabat->id);
                })
                ->orderBy('nama', 'asc')
                ->get();
        }

        // Bawahan Pimpinan Utama (Kabid, Kasi, dll) melihat staf di bawah hirarkinya
        if ($currentPejabat) {
            return $currentPejabat->getAllSubordinates();
        }

        return collect();
    }

    /**
     * Format data bawahan menjadi array terstruktur
     */
    protected function transformSubordinates($subordinates)
    {
        return $subordinates->map(function ($p) {
            $isOnline = $p->isOnline();
            $status = $p->online_status;

            return [
                'id' => $p->id,
                'nama' => $p->nama ?: ($p->user?->name ?? 'Tanpa Nama'),
                'nip' => $p->nip ?? '-',
                'jabatan' => $p->jabatan ?: ($p->alias_jabatan ?: 'Staf'),
                'alias_jabatan' => $p->alias_jabatan,
                'avatar_letter' => strtoupper(substr($p->nama ?: ($p->user?->name ?? 'B'), 0, 1)),
                'lat' => $p->last_lat,
                'lng' => $p->last_lng,
                'accuracy' => $p->last_accuracy,
                'location_name' => $p->last_location_name ?: ($p->login_location_name ?: 'Lokasi belum terdeteksi'),
                'last_active_at' => $p->last_active_at ? $p->last_active_at->translatedFormat('d M Y, H:i') : null,
                'last_active_human' => $p->last_active_at ? $p->last_active_at->diffForHumans() : 'Belum pernah aktif',
                'last_activity' => $p->last_activity ?: 'Sedang membuka aplikasi',
                'last_activity_url' => $p->last_activity_url,
                'login_at' => $p->login_at ? $p->login_at->translatedFormat('d M Y, H:i') : null,
                'login_human' => $p->login_at ? $p->login_at->diffForHumans() : null,
                'login_lat' => $p->login_lat,
                'login_lng' => $p->login_lng,
                'login_location_name' => $p->login_location_name,
                'is_online' => $isOnline,
                'status' => $status,
            ];
        });
    }

    /**
     * Halaman Utama Monitoring Lokasi Bawahan
     */
    public function index(Request $request)
    {
        plugin_page_name('Monitoring Lokasi Bawahan');
        $user = e_office_user();
        $currentPejabat = $user?->pejabat;

        $isKadis = false;
        if ($currentPejabat && (
            str_contains(strtolower($currentPejabat->alias_jabatan ?? ''), 'kadis') ||
            $currentPejabat->alias_jabatan === 'KADIS' ||
            is_null($currentPejabat->atasan_id)
        )) {
            $isKadis = true;
        }

        $isAdmin = $user && (method_exists($user, 'isAdmin') ? $user->isAdmin() : $user->level === 'admin');
        $hasSubs = $currentPejabat && $currentPejabat->hasSubordinates();

        if (!$isKadis && !$isAdmin && !$hasSubs) {
            return redirect(plugin_route('dashboard'))->with('warning', 'Anda belum memiliki bawahan yang dapat dimonitor.');
        }

        $subordinates = $this->getAuthorizedSubordinates($currentPejabat, $user);
        $transformed = $this->transformSubordinates($subordinates);

        // Statistik
        $totalBawahan = $transformed->count();
        $totalOnline = $transformed->where('is_online', true)->count();
        $totalWithLocation = $transformed->filter(fn($i) => !is_null($i['lat']) && !is_null($i['lng']))->count();
        $initialSubordinates = $transformed->values()->all();

        return view('e-office::admin.monitoring_lokasi.index', compact(
            'currentPejabat',
            'isKadis',
            'isAdmin',
            'subordinates',
            'initialSubordinates',
            'totalBawahan',
            'totalOnline',
            'totalWithLocation'
        ));
    }

    /**
     * Endpoint Data Realtime untuk Peta dan List Bawahan (JSON)
     */
    public function getData(Request $request)
    {
        $user = e_office_user();
        $currentPejabat = $user?->pejabat;

        $subordinates = $this->getAuthorizedSubordinates($currentPejabat, $user);
        $data = $this->transformSubordinates($subordinates);

        return response()->json([
            'success' => true,
            'total' => $data->count(),
            'online' => $data->where('is_online', true)->count(),
            'with_coords' => $data->filter(fn($i) => !is_null($i['lat']) && !is_null($i['lng']))->count(),
            'data' => $data->values(),
        ]);
    }

    /**
     * Endpoint Riwayat Jejak Lokasi & Aktivitas Bawahan
     */
    public function getHistory(Request $request, $pejabatId)
    {
        $user = e_office_user();
        $currentPejabat = $user?->pejabat;

        $target = Pejabat::findOrFail($pejabatId);

        // Validasi hak akses melihat target
        $authorized = $this->getAuthorizedSubordinates($currentPejabat, $user);
        if (!$authorized->contains('id', $target->id)) {
            return response()->json(['success' => false, 'message' => 'Anda tidak berhak melihat riwayat bawahan ini.'], 403);
        }

        $perPage = 5;
        $page = max(1, (int) $request->input('page', 1));

        $query = PejabatLocation::where('pejabat_id', $target->id)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $logs = collect($paginated->items())->map(function ($log) {
            return [
                'id' => $log->id,
                'lat' => $log->latitude,
                'lng' => $log->longitude,
                'accuracy' => $log->accuracy,
                'location_name' => $log->location_name ?: 'Titik Koordinat',
                'activity' => $log->activity ?: 'Aktivitas E-Office',
                'activity_url' => $log->activity_url,
                'ip_address' => $log->ip_address,
                'time' => $log->created_at ? $log->created_at->translatedFormat('d M Y, H:i:s') : '-',
                'time_human' => $log->created_at ? $log->created_at->diffForHumans() : '-',
            ];
        });

        return response()->json([
            'success' => true,
            'pejabat' => [
                'id' => $target->id,
                'nama' => $target->nama ?: 'Tanpa Nama',
                'jabatan' => $target->jabatan ?: ($target->alias_jabatan ?: 'Staf'),
            ],
            'logs' => $logs,
            'current_page' => $paginated->currentPage(),
            'has_more' => $paginated->hasMorePages(),
            'total' => $total,
        ]);
    }

    /**
     * Ping Tracker Koordinat & Aktivitas Terakhir Pejabat yang Sedang Aktif
     */
    public function ping(Request $request)
    {
        $user = e_office_user();
        if (!$user || !$user->pejabat) {
            return response()->json(['success' => false, 'message' => 'Pejabat tidak ditemukan.'], 401);
        }

        $pejabat = $user->pejabat;

        $lat = $request->input('latitude');
        $lng = $request->input('longitude');
        $accuracy = $request->input('accuracy');
        $locationName = $request->input('location_name');
        $activity = $request->input('activity', 'Mengakses Halaman');
        $activityUrl = $request->input('activity_url', $request->header('referer'));

        // Jika ada koordinat tapi belum ada nama tempat, lakukan reverse geocode via Nominatim
        if ($lat && $lng && (!$locationName || str_contains(strtolower($locationName), 'memuat') || str_contains(strtolower($locationName), 'koordinat'))) {
            $locationName = $this->reverseGeocode($lat, $lng) ?: $locationName;
        }

        $updateData = [
            'last_active_at' => now(),
            'last_activity' => mb_substr($activity, 0, 250),
            'last_activity_url' => $activityUrl,
        ];

        $hasNewCoords = false;
        if ($lat && $lng && is_numeric($lat) && is_numeric($lng)) {
            $updateData['last_lat'] = $lat;
            $updateData['last_lng'] = $lng;
            $updateData['last_accuracy'] = $accuracy;
            if ($locationName) {
                $updateData['last_location_name'] = $locationName;
            }
            $hasNewCoords = true;

            // Jika belum punya login_lat, isi sekaligus
            if (is_null($pejabat->login_lat)) {
                $updateData['login_lat'] = $lat;
                $updateData['login_lng'] = $lng;
                $updateData['login_accuracy'] = $accuracy;
                $updateData['login_location_name'] = $locationName;
                $updateData['login_at'] = now();
            }
        }

        $pejabat->update($updateData);

        // Catat riwayat jika koordinat tersedia
        if ($hasNewCoords) {
            // Ambil log terakhir
            $lastLog = PejabatLocation::where('pejabat_id', $pejabat->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $shouldInsertLog = false;
            if (!$lastLog) {
                $shouldInsertLog = true;
            } else {
                // Selisih jarak atau waktu > 10 menit
                $timeDiffMin = $lastLog->created_at ? $lastLog->created_at->diffInMinutes(now()) : 999;
                $latDiff = abs($lastLog->latitude - $lat);
                $lngDiff = abs($lastLog->longitude - $lng);

                // Perbedaan koordinat ~ 0.0005 (sekitar 50 meter) atau waktu >= 10 menit atau aktivitas berubah
                if ($latDiff > 0.0005 || $lngDiff > 0.0005 || $timeDiffMin >= 10 || $lastLog->activity !== $activity) {
                    $shouldInsertLog = true;
                }
            }

            if ($shouldInsertLog) {
                PejabatLocation::create([
                    'pejabat_id' => $pejabat->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'accuracy' => $accuracy,
                    'location_name' => $locationName ?: 'Lokasi Koordinat (' . round($lat, 5) . ', ' . round($lng, 5) . ')',
                    'activity' => $activity,
                    'activity_url' => $activityUrl,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'created_at' => now(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'location_name' => $locationName,
        ]);
    }

    /**
     * Helper Reverse Geocoding OSM Nominatim
     */
    protected function reverseGeocode($lat, $lng)
    {
        if (!$lat || !$lng) return null;

        $cacheKey = 'geocode_' . round($lat, 3) . '_' . round($lng, 3);
        return Cache::remember($cacheKey, 86400, function () use ($lat, $lng) {
            try {
                $response = Http::withHeaders([
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
                Log::warning('OSM reverse geocoding error: ' . $e->getMessage());
            }
            return null;
        });
    }
}
