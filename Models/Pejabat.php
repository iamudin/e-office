<?php
namespace App\Models\Plugins\EOffice;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Leazycms\FLC\Traits\Fileable;

class Pejabat extends Model
{

    use Fileable,SoftDeletes,HasUuids;
    protected $fillable = [
        'nama',
        'pangkat_golongan',
        'nip',
        'nohp',
        'jabatan',
        'atasan_id',
        'alias_jabatan',
        'penerima_disposisi',
        'urutan'
        ];
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function staff(){
        return $this->hasMany(Pejabat::class,'atasan_id');
    }
  public function disposisis(){
        return $this->hasMany(Disposisi::class);
    }
    
    public function tupoksis(){
        return $this->hasMany(PejabatTupoksi::class, 'pejabat_id');
    }
}
