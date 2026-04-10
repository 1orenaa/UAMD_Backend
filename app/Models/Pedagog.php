<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedagog extends Model
{
    protected $table      = 'PEDAGOG';
    protected $primaryKey = 'PED_ID';

    protected $fillable = [
        'PED_EM', 'PED_MR', 'PED_EMAIL', 'PED_TIT', 'PED_DTL', 'DEP_ID',
    ];

    public function seksione()
    {
        return $this->hasMany(Seksion::class, 'PED_ID', 'PED_ID');
    }

    public function departament()
    {
        return $this->belongsTo(Departament::class, 'DEP_ID', 'DEP_ID');
    }
}
