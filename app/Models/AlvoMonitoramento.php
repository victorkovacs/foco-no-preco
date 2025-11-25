<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlvoMonitoramento extends Model
{
    use HasFactory;

    protected $table = 'alvosmonitoramento'; // Nome exato da tabela
    protected $primaryKey = 'id_alvo';
    public $timestamps = false;

    protected $fillable = [
        'id_organizacao',
        'ID_Produto',
        'id_link_externo', // Assumindo que isto liga ao Vendedor ou Link
        'ativo',
        'status_verificacao', // Ex: 'OK', 'ERRO'
        'data_ultima_verificacao'
    ];

    // Relacionamento com Produto
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'ID_Produto', 'ID');
    }

    // Relacionamento com Vendedor (Assumindo que id_link_externo liga ao Vendedor)
    // Se a tua estrutura for diferente, avisa-me para ajustar.
    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class, 'id_link_externo', 'ID_Vendedor');
    }
}
