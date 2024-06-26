<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user2 extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'pegawai';
    use HasFactory;
}
