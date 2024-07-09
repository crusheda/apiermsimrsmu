<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class rujukan_keluar extends Model
{
    protected $connection = 'db_pendaftaran';
    protected $table = 'rujukan_keluar';
    use HasFactory;
}
