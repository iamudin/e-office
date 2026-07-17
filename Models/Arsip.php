<?php
namespace App\Models\Plugins\Earsip;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Leazycms\FLC\Traits\Fileable;
use Leazycms\Web\Models\Trait\Notificationable;

class Arsip extends Model
{

    use Fileable,SoftDeletes,HasUuids,Notificationable;
    protected $fillable = [
        'surat_dari',
        'hal',
        'tanggal_surat',
        'tanggal_terima',
        'nomor_agenda',
        'file_surat',
        'file_arsip',
        'nomor_surat',
        'sifat',
        'harapan',
        'catatan',
        'paraf_kasubagumum_pada',
        'diteruskan_ke_kadis',
        'kadis_id',
        'disposisi_pada',
        'user_id'
        ];
        protected $casts = [
            'harapan' => 'array',
            'tanggal_surat' => 'datetime',
            'tanggal_terima' => 'datetime',
            'diteruskan_ke_kadis' => 'datetime',
            'paraf_kasubagumum_pada' => 'datetime',
            'disposisi_pada' => 'datetime',

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
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function kadis()
    {
        return $this->belongsTo(Pejabat::class, 'kadis_id');
    }
     public function disposisis(){
        return $this->hasMany(Disposisi::class);
    }
    public function sudah_paraf(){
        return $this->paraf_kasubagumum_pada != null;
    }
    public function sudah_diteruskan_kekadis(){
        return $this->diteruskan_ke_kadis != null;
    }

}
