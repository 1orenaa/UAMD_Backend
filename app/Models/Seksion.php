<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seksion extends Model
{
    protected $table      = 'SEKSION';
    protected $primaryKey = 'SEK_ID';

    protected $fillable = [
        'SEK_DATA', 'SEK_DRAFILL', 'SEK_DRAMBIA', 'PED_ID', 'SALLE_ID', 'LEN_ID', 'SEM_ID',
    ];

    public function lende()
    {
        return $this->belongsTo(Lende::class, 'LEN_ID', 'LEN_ID');
    }

    public function pedagog()
    {
        return $this->belongsTo(Pedagog::class, 'PED_ID', 'PED_ID');
    }

    public function salle()
    {
        return $this->belongsTo(Salle::class, 'SALLE_ID', 'SALLE_ID');
    }

    public function regjistrime()
    {
        return $this->hasMany(Regjistrim::class, 'SEK_ID', 'SEK_ID');
    }

    public function provime()
    {
        return $this->hasMany(Provim::class, 'SEK_ID', 'SEK_ID');
    }
}
