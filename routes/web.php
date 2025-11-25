<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\VendedorController;
use App\Http\Controllers\CuradoriaController;
use App\Http\Controllers\CustosIaController;
use App\Http\Controllers\IaManualController;
use App\Http\Controllers\TemplateIaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DetalheController;

Route::get('/', function () {
    return redirect()->route('login');
});

// --- AUTENTICAÇÃO ---
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- ROTAS PROTEGIDAS ---
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/index', function () {
        return redirect()->route('dashboard');
    })->name('index');
    Route::get('/dashboard/detalhes', [DetalheController::class, 'index'])->name('dashboard.detalhes');

    // Produtos (Monitoramento)
    Route::get('/produtos', [ProdutoController::class, 'index'])->name('produtos.index');
    Route::get('/produtos/grafico', [ProdutoController::class, 'getDadosGrafico'])->name('produtos.grafico');
    Route::post('/produtos/monitorar', [ProdutoController::class, 'iniciarMonitoramento'])->name('produtos.monitorar');

    // Produtos (Gestão)
    Route::get('/produtos/gerenciar', [ProdutoController::class, 'gerenciar'])->name('produtos.gerenciar');

    // Produtos (Atualização em Massa) - IMPORTANTE: Antes das rotas com {id}
    Route::get('/produtos/massa', [ProdutoController::class, 'massUpdateForm'])->name('produtos.mass_update');
    Route::post('/produtos/massa', [ProdutoController::class, 'massUpdateProcess'])->name('produtos.mass_update_process');

    // Produtos (Rotas Específicas)
    Route::put('/produtos/alvo/{idAlvo}/link', [ProdutoController::class, 'updateAlvoLink'])->name('produtos.alvos.update');

    // Produtos (Edição/Exclusão com {id})
    Route::get('/produtos/{id}/fetch', [ProdutoController::class, 'edit'])->name('produtos.fetch');
    Route::get('/produtos/{id}/editar', [ProdutoController::class, 'edit'])->name('produtos.edit');
    Route::put('/produtos/{id}', [ProdutoController::class, 'update'])->name('produtos.update');
    Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy'])->name('produtos.destroy');

    // Produtos (Painel de Conteúdo / Cadastro)
    Route::get('/produtos/conteudo', [\App\Http\Controllers\ProdutoDashboardController::class, 'index'])->name('produtos_dashboard.index');
    Route::post('/produtos/conteudo', [\App\Http\Controllers\ProdutoDashboardController::class, 'store'])->name('produtos_dashboard.store');

    // Concorrentes (Vendedores) - CORRIGIDO AQUI
    Route::get('/concorrentes', [VendedorController::class, 'index'])->name('concorrentes.index');
    // ADICIONADO: Rota para atualizar concorrente
    Route::put('/concorrentes/{id}', [VendedorController::class, 'update'])->name('concorrentes.update');

    // Curadoria
    Route::get('/curadoria', [CuradoriaController::class, 'index'])->name('curadoria.index');
    Route::get('/curadoria/search', [CuradoriaController::class, 'search'])->name('curadoria.search');

    // Custos IA
    Route::get('/custos-ia', [CustosIaController::class, 'index'])->name('custos_ia.index');

    // IA Manual
    Route::get('/ia-manual', [IaManualController::class, 'index'])->name('ia_manual.index');
    Route::post('/ia-manual/process', [IaManualController::class, 'process'])->name('ia_manual.process');

    // Templates IA
    Route::get('/templates-ia', [TemplateIaController::class, 'index'])->name('templates_ia.index');
    Route::get('/templates-ia/list', [TemplateIaController::class, 'list'])->name('templates_ia.list');
    Route::get('/templates-ia/show/{id}', [TemplateIaController::class, 'show'])->name('templates_ia.show');
    Route::post('/templates-ia', [TemplateIaController::class, 'store'])->name('templates_ia.store');
    Route::delete('/templates-ia/{id}', [TemplateIaController::class, 'destroy'])->name('templates_ia.destroy');

    // Gestão de Usuários
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/list', [UserController::class, 'list'])->name('users.list');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

    // Perfil (Alterar Senha)
    Route::get('/perfil/senha', [ProfileController::class, 'edit'])->name('profile.password.edit');
    Route::post('/perfil/senha', [ProfileController::class, 'update'])->name('profile.password.update');
});
