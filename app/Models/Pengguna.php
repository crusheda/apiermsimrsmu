<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Pengguna extends Authenticatable
{
    use HasRoles;

    protected $connection = 'db_aplikasi';
    protected $table = 'pengguna';
    protected $guard_name = 'web';
    protected $primaryKey = 'ID'; // atau sesuaikan
    public $timestamps = false;

    protected $fillable = ['LOGIN', 'PASSWORD'];

    public function username()
    {
        return 'LOGIN';
    }

    public function getAuthIdentifierName()
    {
        return 'LOGIN';
    }
    
    public function getAuthPassword()
    {
        return $this->PASSWORD;
    }
}
