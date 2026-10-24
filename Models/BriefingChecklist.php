<?php
namespace App\Models\Plugins\EOffice;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Leazycms\FLC\Traits\Fileable;

class BriefingChecklist extends Model
{
    use HasUuids, Fileable;

    protected $table = 'e_office_briefing_checklists';

    protected $fillable = [
        'briefing_id',
        'kegiatan',
        'status',
        'is_checked',
        'penerima_id',
        'checked_by_pejabat_id',
        'checked_at',
        'file_lampiran'
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($checklist) {
            foreach ($checklist->files as $row) {
                $row->deleteFile();
            }
        });
    }

    protected $casts = [
        'is_checked' => 'boolean',
        'checked_at' => 'datetime'
    ];

    public function briefing()
    {
        return $this->belongsTo(Briefing::class, 'briefing_id');
    }

    public function checkedByPejabat()
    {
        return $this->belongsTo(Pejabat::class, 'checked_by_pejabat_id');
    }

    public function penerima()
    {
        return $this->belongsTo(Pejabat::class, 'penerima_id');
    }
}
