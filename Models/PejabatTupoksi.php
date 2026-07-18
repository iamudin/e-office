<?php
namespace App\Models\Plugins\EOffice;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PejabatTupoksi extends Model
{
    use HasUuids;

    protected $table = 'e_office_pejabat_tupoksis';

    protected $fillable = [
        'pejabat_id',
        'tugas_fungsi'
    ];

    public function pejabat()
    {
        return $this->belongsTo(Pejabat::class, 'pejabat_id');
    }
}
