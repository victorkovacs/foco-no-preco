<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // --- CONSTANTES DE NÍVEL DE ACESSO ---
    const NIVEL_COLABORADOR = 2;
    const NIVEL_ADMIN = 1;

    protected $table = 'Usuarios';
    public $timestamps = false;

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

    // --- AQUI ESTÃO AS FUNÇÕES QUE FALTAVAM ---

    public function isAdmin(): bool
    {
        // Verifica se a coluna 'nivel_acesso' é igual a 1
        return $this->nivel_acesso === self::NIVEL_ADMIN;
    }

    public function isColaborador(): bool
    {
        return $this->nivel_acesso === self::NIVEL_COLABORADOR;
    }
}
