<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkExterno extends Model
{
    use HasFactory;

    protected $table = 'links_externos'; // Nome exato no banco
    public $timestamps = false; // Sem created_at/updated_at padrão

    protected $fillable = [
        'id_organizacao',
        'SKU',
        'ativo',
        'status_link',
        'ID_Vendedor',
        'link'
    ];

    public function vendedor()
    {
        return $this->belongsTo(Vendedor::class, 'ID_Vendedor', 'ID_Vendedor');
    }
}
