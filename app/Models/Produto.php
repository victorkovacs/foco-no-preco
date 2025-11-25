<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    // Configuração para bater com teu banco legado
    protected $table = 'produtos';
    protected $primaryKey = 'ID';
    public $timestamps = false; // Não tens created_at/updated_at nesta tabela

    protected $fillable = [
        'id_organizacao',
        'SKU',
        'Nome',
        'LinkPesquisa',
        'LinkMeuSite',
        'marca',
        'Categoria',
        'SubCategoria',
        'PrecoVenda',
        'EncontrouConcorrentes',
        'ativo',
        'ia_processado'
    ];

    // Escopo global para filtrar sempre pela empresa do usuário (opcional, mas seguro)
    // Para simplificar, faremos o filtro no Controller.
}
