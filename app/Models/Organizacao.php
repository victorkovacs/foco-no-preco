<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organizacao extends Model
{
    use HasFactory;

    // 1. Nome exato da tabela
    protected $table = 'Organizacoes';

    // 2. Chave Primária correta (não é 'id')
    protected $primaryKey = 'id_organizacao';

    // 3. Timestamps
    // Sua tabela tem 'data_cadastro', mas não 'updated_at'
    const CREATED_AT = 'data_cadastro';
    const UPDATED_AT = null;

    protected $fillable = [
        'nome_empresa', // Conforme sua migration
        'cnpj_cpf',
        'api_key',
        'plano',
        'ativa',
    ];

    protected $casts = [
        'data_cadastro' => 'datetime',
        'ativa' => 'boolean',
    ];

    // Relacionamento: Uma organização tem muitos usuários
    public function users()
    {
        // hasMany(Model, FK, LocalKey)
        return $this->hasMany(User::class, 'id_organizacao', 'id_organizacao');
    }
}
