<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    use HasFactory;

    protected $table = 'vendedores'; // Nome exato da tabela no banco
    protected $primaryKey = 'ID_Vendedor'; // Chave primária personalizada
    public $timestamps = false; // Tabela não tem created_at/updated_at

    protected $fillable = [
        'id_organizacao',
        'NomeVendedor',
        'SeletorPreco',
        'Ativo',
        'PercentualDescontoAVista',
        'SeletorMarca',
        'LinkConcorrente',
        'FiltroLinkProduto'
    ];
}
