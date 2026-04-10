<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table      = 'STUDENT';
    protected $primaryKey = 'STD_ID';

    protected $fillable = [
        'STD_EM', 'STD_MB', 'STD_EMAIL', 'STD_DTL', 'STD_GJINI', 'DEP_ID',
    ];

    public function regjistrime()
    {
        return $this->hasMany(Regjistrim::class, 'STD_ID', 'STD_ID');
    }

    public function provime()
    {
        return $this->hasMany(Provim::class, 'STD_ID', 'STD_ID');
    }

    public function departament()
    {
        return $this->belongsTo(Departament::class, 'DEP_ID', 'DEP_ID');
    }
}
