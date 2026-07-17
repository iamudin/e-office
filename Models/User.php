<?php
namespace App\Models\Plugins\EOffice;
use Leazycms\Web\Models\User as BaseUser;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends BaseUser
{
    use SoftDeletes;
    protected $casts = [
        'last_login_at' => 'datetime'
    ];
    public function is_operator()
    {
        return $this->pejabat?->alias_jabatan == 'OPERATOR';
    }
    public function is_kadis()
    {
        return $this->pejabat?->alias_jabatan == 'KADIS';
    }
    public function is_kasubag()
    {
        return $this->pejabat?->alias_jabatan == 'KASUBAGUMUM';
    }
    public function is_kabid()
    {
        return in_array($this->pejabat?->alias_jabatan, ['KABID', 'SEKRETARIS']);
    }
    public function isActive()
    {
        return $this->status == 'active';
    }

    public function pejabat()
    {
        return $this->hasOne(Pejabat::class);
    }
    public function arsips()
    {
        return $this->hasMany(Arsip::class);
    }
}
