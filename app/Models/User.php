<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'Usuarios'; // Fonte da verdade

    // Timestamps do User
    const CREATED_AT = 'data_criacao';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_organizacao', // FK obrigatória
        'email',
        'senha_hash',
        'nivel_acesso',
        'ativo',
        'api_key',
    ];

    protected $hidden = [
        'senha_hash',
        'remember_token',
    ];

    protected $casts = [
        'data_criacao' => 'datetime',
        'senha_hash' => 'hashed',
        'ativo' => 'boolean',
        'nivel_acesso' => 'integer',
    ];

    // Sobrescreve a senha padrão do Laravel
    public function getAuthPassword()
    {
        return $this->senha_hash;
    }

    // Relacionamento: Usuário pertence a uma Organização
    public function organizacao()
    {
        // belongsTo(Model, FK, OwnerKey)
        return $this->belongsTo(Organizacao::class, 'id_organizacao', 'id_organizacao');
    }

    // Helpers de Nível
    const NIVEL_MESTRE   = 1;
    const NIVEL_ADMIN    = 2;
    const NIVEL_CADASTRO = 3;
    const NIVEL_USUARIO  = 4;

    public function getNivelSeguro()
    {
        return $this->nivel_acesso ?? 99;
    }

    public function isMaster()
    {
        return $this->nivel_acesso === self::NIVEL_MESTRE;
    }
    public function isAdmin()
    {
        return $this->nivel_acesso <= self::NIVEL_ADMIN;
    }
    public function canEdit()
    {
        return $this->nivel_acesso <= self::NIVEL_CADASTRO;
    }
}
