<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExternalDataController;

// Rota para buscar concorrentes do dia
// URL Final: http://seusite/api/v1/concorrentes/hoje
Route::get('/v1/concorrentes/hoje', [ExternalDataController::class, 'getConcorrentesHoje']);
