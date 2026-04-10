<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lende extends Model
{
    protected $table      = 'LENDE';
    protected $primaryKey = 'LEN_ID';

    protected $fillable = ['LEN_EM', 'LEN_KOD', 'LEN_KRED', 'DEP_ID'];

    public function seksione()
    {
        return $this->hasMany(Seksion::class, 'LEN_ID', 'LEN_ID');
    }
}
