<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateIa extends Model
{
    // 1. Nome da tabela (Correto)
    protected $table = 'templates_ia';

    // 2. Timestamps (CORREÇÃO: Deve ser true para funcionar o ->format() na view)
    public $timestamps = true;

    // 3. Campos permitidos para salvar (CORREÇÃO: Adicionado id_organizacao)
    protected $fillable = [
        'id_organizacao',
        'nome_template',
        'prompt_sistema',
        'json_schema_saida',
        'ativo'
    ];

    // 4. Casting (Garante que o ativo venha como boolean e datas como Carbon)
    protected $casts = [
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // 5. Relacionamento com Organização (Mapeamento correto das chaves)
    public function organizacao()
    {
        return $this->belongsTo(Organizacao::class, 'id_organizacao', 'id_organizacao');
    }
}
