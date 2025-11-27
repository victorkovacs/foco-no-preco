<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- O Sanctum que instalamos

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'nivel_acesso',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // --- DEFINIÇÃO DOS NÍVEIS (Automação da Lógica) ---
    const NIVEL_MESTRE   = 1;
    const NIVEL_ADMIN    = 2;
    const NIVEL_CADASTRO = 3;
    const NIVEL_USUARIO  = 4;

    // --- HELPERS PARA O SISTEMA ---

    public function isMaster()
    {
        return $this->nivel_acesso === self::NIVEL_MESTRE;
    }

    public function isAdmin()
    {
        // Mestre também é Admin
        return $this->nivel_acesso <= self::NIVEL_ADMIN;
    }

    public function canEdit()
    {
        // Mestre, Admin e Cadastro podem editar
        return $this->nivel_acesso <= self::NIVEL_CADASTRO;
    }

    // Helper de compatibilidade (se ainda usar no código antigo)
    public function isColaborador()
    {
        return $this->canEdit();
    }
}
