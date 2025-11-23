<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateIa extends Model
{
    protected $table = 'templates_ia';
    public $timestamps = false;
    protected $fillable = ['nome_template', 'prompt_sistema', 'json_schema_saida', 'ativo'];
}
