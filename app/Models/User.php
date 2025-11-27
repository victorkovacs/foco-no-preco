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

    // CORREÇÃO DE SEGURANÇA:
    // Removemos 'nivel_acesso', 'id_organizacao' e 'ativo' para evitar Mass Assignment.
    // Esses campos agora só podem ser alterados via atribuição direta no código ($user->campo = valor).
    protected $fillable = [
        'email',
        'senha_hash',
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

    public function isAdmin(): bool
    {
        return $this->nivel_acesso === self::NIVEL_ADMIN;
    }

    public function isColaborador(): bool
    {
        return $this->nivel_acesso === self::NIVEL_COLABORADOR;
    }
}
