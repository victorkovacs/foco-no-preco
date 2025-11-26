<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalLink extends Model
{
    use HasFactory;

    protected $table = 'global_links';

    protected $fillable = [
        'link',
        'ID_Vendedor',
        'status_link',
        'data_ultima_verificacao'
    ];

    /**
     * O Vendedor dono deste link (Ex: Amazon, Mercado Livre)
     */
    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class, 'ID_Vendedor', 'ID_Vendedor');
    }

    /**
     * Todas as vezes que este link foi cadastrado por organizações diferentes.
     */
    public function linksExternos()
    {
        return $this->hasMany(LinkExterno::class, 'global_link_id');
    }
}
