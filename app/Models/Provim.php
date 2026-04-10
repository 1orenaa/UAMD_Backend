<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provim extends Model
{
    protected $table      = 'PROVIM';
    protected $primaryKey = 'PRV_ID';

    protected $fillable = [
        'PRV_DBA', 'PRV_TIP', 'PRV_DTFILL', 'PRV_DTMBA', 'SEK_ID', 'STD_ID',
    ];

    public function seksion()
    {
        return $this->belongsTo(Seksion::class, 'SEK_ID', 'SEK_ID');
    }
}
