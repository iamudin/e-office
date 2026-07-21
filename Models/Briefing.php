<?php
namespace App\Models\Plugins\EOffice;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Leazycms\FLC\Traits\Fileable;
use Leazycms\Web\Models\Trait\Notificationable;

class Briefing extends Model
{
    use HasUuids, SoftDeletes, Fileable, Notificationable;

    protected $table = 'e_office_briefings';

    protected $fillable = [
        'user_id',
        'judul',
        'pesan',
        'file_lampiran',
        'status',
        'feedback_kadis'
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($briefing) {
            if ($briefing->isForceDeleting()) {
                foreach($briefing->files as $row){
                    $row->deleteFile();
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checklists()
    {
        return $this->hasMany(BriefingChecklist::class, 'briefing_id');
    }

    public function disposisis()
    {
        return $this->hasMany(BriefingDisposisi::class, 'briefing_id');
    }
}
