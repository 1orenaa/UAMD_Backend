<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Regjistrim extends Model
{
    protected $table      = 'REGJISTRIM';
    protected $primaryKey = 'REGJL_ID';

    protected $fillable = [
        'REGJL_DT', 'REGJL_STATUS', 'REGJL_NOTA',
        'REGJL_PRV_NOR', 'REGJL_PRV_FIN', 'REGJL_PRU',
        'STD_ID', 'SEK_ID',
    ];

    public function seksion()
    {
        return $this->belongsTo(Seksion::class, 'SEK_ID', 'SEK_ID');
    }
}
