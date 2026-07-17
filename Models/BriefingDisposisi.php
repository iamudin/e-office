<?php
namespace App\Models\Plugins\Earsip;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Leazycms\FLC\Traits\Fileable;

class BriefingDisposisi extends Model
{
    use HasUuids, SoftDeletes, Fileable;

    protected $table = 'earsip_briefing_disposisis';

    protected $fillable = [
        'briefing_id',
        'pengirim_id',
        'penerima_id',
        'pesan_diteruskan',
        'pesan_khusus',
        'file_lampiran',
        'dibaca_pada',
        'dibalas_pada',
        'balasan_teks',
        'balasan_file',
        'feedback_kadis',
        'is_validated_by_atasan'
    ];

    protected $casts = [
        'dibaca_pada' => 'datetime',
        'dibalas_pada' => 'datetime'
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($disposisi) {
            if ($disposisi->isForceDeleting()) {
                foreach($disposisi->files as $row){
                    $row->deleteFile();
                }
            }
        });
    }

    public function briefing()
    {
        return $this->belongsTo(Briefing::class, 'briefing_id');
    }

    public function pengirim()
    {
        return $this->belongsTo(Pejabat::class, 'pengirim_id');
    }

    public function penerima()
    {
        return $this->belongsTo(Pejabat::class, 'penerima_id');
    }
}
