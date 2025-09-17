<?php

namespace App\Models\master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ruangan extends Model
{
    protected $connection = 'db_master';
    protected $table = 'ruangan';
    use HasFactory;
}
