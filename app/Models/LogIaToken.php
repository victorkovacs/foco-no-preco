<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogIaToken extends Model
{
    use HasFactory;

    protected $table = 'logiatokenssimples';
    public $timestamps = false;

    protected $fillable = [
        'id_organizacao',
        'tokens_in',
        'tokens_out',
        'modelo',
        'data_registro'
    ];
}
