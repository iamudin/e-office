<?php

namespace App\Models\Plugins\EOffice;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PejabatLocation extends Model
{
    use HasUuids;

    protected $table = 'e_office_pejabat_locations';
    public $timestamps = false;

    protected $fillable = [
        'pejabat_id',
        'latitude',
        'longitude',
        'accuracy',
        'location_name',
        'activity',
        'activity_url',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'created_at' => 'datetime',
    ];

    public function pejabat()
    {
        return $this->belongsTo(Pejabat::class, 'pejabat_id');
    }
}
