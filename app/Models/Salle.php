<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salle extends Model
{
    protected $table = 'SALLE';
    protected $primaryKey = 'SALLE_ID';

    protected $fillable = [
        'SALLE_EM', 'SALLE_KAP', 'FAK_ID',
    ];
}
