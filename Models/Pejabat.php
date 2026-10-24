<?php
namespace App\Models\Plugins\EOffice;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Leazycms\FLC\Traits\Fileable;

class Pejabat extends Model
{
    use Fileable, SoftDeletes, HasUuids;

    protected $fillable = [
        'nama',
        'pangkat_golongan',
        'nip',
        'nohp',
        'jabatan',
        'atasan_id',
        'alias_jabatan',
        'penerima_disposisi',
        'urutan',
        'last_lat',
        'last_lng',
        'last_accuracy',
        'last_location_name',
        'last_active_at',
        'last_activity',
        'last_activity_url',
        'login_lat',
        'login_lng',
        'login_accuracy',
        'login_location_name',
        'login_at',
    ];

    protected $casts = [
        'last_lat' => 'float',
        'last_lng' => 'float',
        'last_accuracy' => 'float',
        'last_active_at' => 'datetime',
        'login_lat' => 'float',
        'login_lng' => 'float',
        'login_accuracy' => 'float',
        'login_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function atasan()
    {
        return $this->belongsTo(Pejabat::class, 'atasan_id');
    }

    public function staff()
    {
        return $this->hasMany(Pejabat::class, 'atasan_id');
    }

    public function disposisis()
    {
        return $this->hasMany(Disposisi::class);
    }

    public function tupoksis()
    {
        return $this->hasMany(PejabatTupoksi::class, 'pejabat_id');
    }

    public function locationLogs()
    {
        return $this->hasMany(PejabatLocation::class, 'pejabat_id')->orderBy('created_at', 'desc');
    }

    /**
     * Dapatkan semua bawahan (langsung maupun turunan berantai)
     */
    public function getAllSubordinates()
    {
        $all = collect();
        $queue = $this->staff()->with('user')->get();

        while ($queue->isNotEmpty()) {
            $current = $queue->shift();
            $all->push($current);
            $children = $current->staff()->with('user')->get();
            foreach ($children as $child) {
                $queue->push($child);
            }
        }

        return $all;
    }

    /**
     * Dapatkan array ID semua bawahan (langsung maupun turunan berantai)
     */
    public function getAllSubordinateIds()
    {
        return $this->getAllSubordinates()->pluck('id')->toArray();
    }

    /**
     * Cek apakah pejabat ini memiliki bawahan
     */
    public function hasSubordinates()
    {
        return $this->staff()->exists();
    }

    /**
     * Cek apakah pejabat adalah Kepala Dinas (Pimpinan Utama)
     */
    public function isKadis(): bool
    {
        return str_contains(strtolower($this->alias_jabatan ?? ''), 'kadis')
            || (!$this->atasan_id && str_contains(strtolower($this->jabatan ?? ''), 'kepala'));
    }

    /**
     * Cek apakah pejabat sedang online (aktif <= 5 menit lalu)
     */
    public function isOnline(): bool
    {
        return $this->last_active_at && $this->last_active_at->diffInMinutes(now()) <= 5;
    }

    /**
     * Cek status aktivitas
     * online: <= 5 menit
     * recent: <= 60 menit
     * offline: > 60 menit / null
     */
    public function getOnlineStatusAttribute(): string
    {
        if (!$this->last_active_at) {
            return 'offline';
        }
        $diff = $this->last_active_at->diffInMinutes(now());
        if ($diff <= 5) {
            return 'online';
        } elseif ($diff <= 60) {
            return 'recent';
        }
        return 'offline';
    }
}
