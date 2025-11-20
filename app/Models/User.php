<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable; 

    // CORRIGIDO: Mapeia para a sua tabela real 'Usuarios'
    protected $table = 'Usuarios'; 

    protected $fillable = [
        'email',
        // O campo 'senha_hash' da sua tabela SQL
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

    // CRÍTICO: Sobrescreve o método para usar a coluna 'senha_hash' na autenticação
    public function getAuthPassword()
    {
        return $this->senha_hash;
    }
}