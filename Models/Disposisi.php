<?php
namespace App\Models\Plugins\EOffice;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Leazycms\FLC\Traits\Fileable;

class Disposisi extends Model
{

    use SoftDeletes,HasUuids,Fileable;
    protected $fillable = [
        'user_id',
        'pejabat_id',
        'arsip_id',
        'dibaca_pada',
        'diarsip_pada',
        'dibalas_pada',
        'disposisi_pdf',
        'catatan',
        'balasan',
        'whatsapp_pejabat',
        'teruskan_ke_whatsapp_pada'
        ];
            protected $casts = [
            'dibalas_pada' => 'datetime',
            'diarsip_pada' => 'datetime',
            'teruskan_ke_whatsapp_pada' => 'datetime',
        ];

        public static function boot()
        {
            parent::boot();
    
            static::deleting(function ($arsip) {
                if ($arsip->isForceDeleting()) {
                    foreach($arsip->files as $row){
                        $row->deleteFile();
                    }
                }
            });
    
        }
    public function pejabat(){
        return $this->belongsTo(Pejabat::class);
    }
    public function arsip(){
        return $this->belongsTo(Arsip::class);
    }
    public function wa_pejabat(){
        return $this->belongsTo(Pejabat::class, 'whatsapp_pejabat');
    }
      public function getDibacaAttribute($key)
      {
        return $this->dibaca_pada != null ? '<code>( dilihat pada '.$this->dibaca_pada.' <i class="fa fa-check text-emerald-500"></i>)</code>': null;
    }
    public function belum_dibalas(){
        return $this->dibalas_pada == null;
    }
      public function user(){
        return $this->belongsTo(User::class);
    }
}
