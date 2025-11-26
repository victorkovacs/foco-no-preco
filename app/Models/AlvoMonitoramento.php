<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlvoMonitoramento extends Model
{
    use HasFactory;

    protected $table = 'AlvosMonitoramento';
    protected $primaryKey = 'id_alvo';
    public $timestamps = false;

    protected $fillable = [
        'id_organizacao',
        'ID_Produto',
        'id_link_externo',
        'ativo',
        'status_verificacao',
        'data_ultima_verificacao'
    ];

    // --- MAGIA DO LARAVEL ---
    // Isso garante que 'vendedor' e 'url' apareçam no JSON da API
    protected $appends = ['vendedor', 'url'];

    // Relacionamentos
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'ID_Produto', 'ID');
    }

    public function linkExterno()
    {
        return $this->belongsTo(LinkExterno::class, 'id_link_externo', 'id');
    }

    // --- ACESSORES (Atributos Virtuais) ---

    /**
     * Pega o Vendedor navegando: Alvo -> LinkExterno -> GlobalLink -> Vendedor
     */
    public function getVendedorAttribute()
    {
        // Verifica se as relações foram carregadas para evitar erro
        if (
            $this->relationLoaded('linkExterno') && $this->linkExterno &&
            $this->linkExterno->relationLoaded('globalLink') && $this->linkExterno->globalLink
        ) {
            return $this->linkExterno->globalLink->vendedor;
        }

        // Fallback (Lazy load se não tiver sido carregado com 'with')
        return $this->linkExterno?->globalLink?->vendedor;
    }

    /**
     * Pega a URL final
     */
    public function getUrlAttribute()
    {
        return $this->linkExterno?->globalLink?->link;
    }
}
