<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkExterno extends Model
{
    use HasFactory;

    protected $table = 'links_externos';
    public $timestamps = false;

    protected $fillable = [
        'id_organizacao',
        'global_link_id', // Agora usamos este ID em vez da URL direta
        'SKU',
        'ativo',
        'nome' // Mantive 'nome' pois pode ser um apelido interno da organização
    ];

    /**
     * Relacionamento com o Link Global (Dados reais do link)
     */
    public function globalLink()
    {
        return $this->belongsTo(GlobalLink::class, 'global_link_id');
    }

    // --- ACESSORES DE COMPATIBILIDADE ---
    // Isso permite usar $linkExterno->link e $linkExterno->vendedor
    // como se os campos ainda existissem nesta tabela.

    public function getLinkAttribute()
    {
        return $this->globalLink ? $this->globalLink->link : null;
    }

    public function getVendedorAttribute()
    {
        return $this->globalLink ? $this->globalLink->vendedor : null;
    }

    public function getStatusLinkAttribute()
    {
        return $this->globalLink ? $this->globalLink->status_link : null;
    }
}
