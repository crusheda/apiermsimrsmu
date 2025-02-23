<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pasien extends Model
{
    protected $connection = 'db_master';
    protected $table = 'pasien';
    use HasFactory;
}
