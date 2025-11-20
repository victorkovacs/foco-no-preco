<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PaginasController;
// NOVO: Importa o Controller de Login
use App\Http\Controllers\Auth\LoginController;


Route::get('/', function () {
    return '<h1> estou testando como está sendo <h1>';
});

// Rotas existentes
Route::get('/sobre', [PaginasController::class, 'sobre']);
Route::get('/produto/{id}', [PaginasController::class, 'produto']);
Route::get('/posts', [PaginasController::class, 'ListarPosts']);


/* NOVAS ROTAS DE AUTENTICAÇÃO */

// 1. Rota GET: Exibe o formulário de login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');

// 2. Rota POST: Processa o envio do formulário (Login)
Route::post('/login', [LoginController::class, 'authenticate']);

// 3. Rota POST: Faz o Logout (O método POST é mais seguro para esta ação)
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


/* ROTA DE PAINEL PROTEGIDA PARA TESTE */
Route::get('/index', function () {
    $user = Auth::user();
    return "<h1>Sucesso! Você está logado no Laravel.</h1><p>Bem-vindo, {$user->email}!</p><p>Seu nível de acesso é: {$user->nivel_acesso}</p>
    <form method='POST' action='".route('logout')."'><input type='hidden' name='_token' value='".csrf_token()."'> <button type='submit'>Sair (Logout)</button></form>";
})->middleware('auth'); // CRÍTICO: Garante que só usuários logados acessem