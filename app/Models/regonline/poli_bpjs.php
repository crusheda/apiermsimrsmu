<?php

namespace App\Models\regonline;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class poli_bpjs extends Model
{
    use HasFactory;
    protected $connection = 'db_regonline';
    protected $table = 'poli_bpjs';
    public $timestamps = false;
}
