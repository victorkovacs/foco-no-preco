<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaginasController;

Route::get('/', function () {
    return '<h1> estou testando como está sendo <h1>';
    
});

Route::get('/sobre', [PaginasController::class, 'sobre']);
Route::get('/produto/{id}', [PaginasController::class, 'produto']);

Route::get('/posts', [PaginasController::class, 'ListarPosts']);


