<?php

namespace App\Models\mr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jadwal_kontrol extends Model
{
    protected $connection = 'db_medicalrecord';
    protected $table = 'jadwal_kontrol';
    use HasFactory;
}
