<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user1 extends Model
{
    protected $connection = 'mysql';
    protected $table = 'users';
    use HasFactory;
}
