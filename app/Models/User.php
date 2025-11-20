<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'Usuarios';

    protected $fillable = [
        'email',
        'senha_hash',
        'nivel_acesso',
        'ativo',
        'id_organizacao',
        'api_key',
    ];

    
    protected $hidden = [
        'senha_hash',
        'remember_token',
    ];

    public function getAuthPassword()
    {
        return $this->senha_hash;
    }


}
