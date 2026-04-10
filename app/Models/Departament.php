<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departament extends Model
{
    protected $table      = 'DEPARTAMENT';
    protected $primaryKey = 'DEP_ID';

    protected $fillable = ['DEP_EM', 'FAK_ID', 'PED_ID'];
}
