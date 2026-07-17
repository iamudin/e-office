<?php
namespace App\Models\Plugins\Earsip;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class BriefingChecklist extends Model
{
    use HasUuids;

    protected $table = 'earsip_briefing_checklists';

    protected $fillable = [
        'briefing_id',
        'kegiatan',
        'is_checked',
        'penerima_id',
        'checked_by_pejabat_id',
        'checked_at'
    ];

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
