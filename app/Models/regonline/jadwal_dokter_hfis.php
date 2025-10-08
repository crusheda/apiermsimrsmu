<?php

namespace App\Models\regonline;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jadwal_dokter_hfis extends Model
{
    use HasFactory;
    protected $connection = 'db_regonline';
    protected $table = 'jadwal_dokter_hfis';
    public $timestamps = false;
}
