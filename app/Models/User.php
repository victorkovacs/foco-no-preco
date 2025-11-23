<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable; 

    // 1. Nome da Tabela
    protected $table = 'Usuarios'; 

    // 2. DESATIVAR TIMESTAMPS PADRÃO (A Correção do Erro)
    // Isto impede o Laravel de procurar 'created_at' e 'updated_at'
    public $timestamps = false;

    // 3. Campos que podem ser preenchidos
    protected $fillable = [
        'email',
        'senha_hash', 
        'nivel_acesso',
        'ativo',
        'id_organizacao', 
        'api_key', 
        // Removi 'data_criacao' daqui porque o MySQL preenche sozinho (DEFAULT CURRENT_TIMESTAMP)
    ];

    protected $hidden = [
        'senha_hash', 
        'remember_token',
    ];

    // 4. Dizer ao Laravel que a senha está na coluna 'senha_hash'
    public function getAuthPassword()
    {
        return $this->senha_hash;
    }
}