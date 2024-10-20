<?php

namespace App\Models\regonline;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class reservasi extends Model
{
    protected $connection = 'db_regonline';
    protected $table = 'reservasi';
    use HasFactory;
}
