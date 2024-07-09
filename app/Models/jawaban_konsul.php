<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class jawaban_konsul extends Model
{
    protected $connection = 'db_pendaftaran';
    protected $table = 'jawaban_konsul';
    use HasFactory;
}
