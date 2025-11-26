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
use App\Http\Controllers\ProdutoDashboardController;
use App\Http\Controllers\ExportController;

// Redirecionamento inicial
Route::get('/', function () {
    return redirect()->route('login');
});

// --- AUTENTICAÇÃO (Rotas Públicas) ---
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// --- ROTAS PROTEGIDAS (Qualquer usuário logado) ---
Route::middleware('auth')->group(function () {

    // --- DASHBOARD & PERFIL ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/index', function () {
        return redirect()->route('dashboard');
    })->name('index');
    Route::get('/dashboard/detalhes', [DetalheController::class, 'index'])->name('dashboard.detalhes');

    // Exportação de Dados
    Route::get('/export/concorrentes', [ExportController::class, 'exportConcorrentes'])
        ->name('export.concorrentes');

    // API de Saúde do Sistema (usada pelo Widget via AJAX)
    Route::get('/api/health-check', [App\Http\Controllers\SystemHealthController::class, 'check'])->name('api.health_check');

    // Alterar Senha
    Route::get('/perfil/senha', [ProfileController::class, 'edit'])->name('profile.password.edit');
    Route::post('/perfil/senha', [ProfileController::class, 'update'])->name('profile.password.update');

    // --- OPERACIONAL (Acesso para Colaboradores e Admins) ---

    // Produtos (Monitoramento e Gestão Básica)
    Route::get('/produtos', [ProdutoController::class, 'index'])->name('produtos.index');
    Route::get('/produtos/grafico', [ProdutoController::class, 'getDadosGrafico'])->name('produtos.grafico');
    Route::post('/produtos/monitorar', [ProdutoController::class, 'iniciarMonitoramento'])->name('produtos.monitorar');
    Route::get('/produtos/gerenciar', [ProdutoController::class, 'gerenciar'])->name('produtos.gerenciar');

    // Produtos (Edição e Atualização em Massa)
    Route::get('/produtos/massa', [ProdutoController::class, 'massUpdateForm'])->name('produtos.mass_update');
    Route::post('/produtos/massa', [ProdutoController::class, 'massUpdateProcess'])->name('produtos.mass_update_process');
    Route::put('/produtos/alvo/{idAlvo}/link', [ProdutoController::class, 'updateAlvoLink'])->name('produtos.alvos.update');

    // Produtos (CRUD Individual - Exceto Delete)
    Route::get('/produtos/{id}/fetch', [ProdutoController::class, 'edit'])->name('produtos.fetch');
    Route::get('/produtos/{id}/editar', [ProdutoController::class, 'edit'])->name('produtos.edit');
    Route::put('/produtos/{id}', [ProdutoController::class, 'update'])->name('produtos.update');

    // Painel de Conteúdo / Geração de IA
    Route::get('/produtos/conteudo', [ProdutoDashboardController::class, 'index'])->name('produtos_dashboard.index');
    Route::post('/produtos/conteudo', [ProdutoDashboardController::class, 'store'])->name('produtos_dashboard.store');
    Route::post('/produtos/conteudo/processar', [ProdutoDashboardController::class, 'sendBatch'])->name('produtos_dashboard.processar');

    // Concorrentes
    Route::get('/concorrentes', [VendedorController::class, 'index'])->name('concorrentes.index');
    Route::put('/concorrentes/{id}', [VendedorController::class, 'update'])->name('concorrentes.update');

    // Curadoria
    Route::get('/curadoria', [CuradoriaController::class, 'index'])->name('curadoria.index');
    Route::get('/curadoria/search', [CuradoriaController::class, 'search'])->name('curadoria.search');

    // IA Manual
    Route::get('/ia-manual', [IaManualController::class, 'index'])->name('ia_manual.index');
    Route::post('/ia-manual/process', [IaManualController::class, 'process'])->name('ia_manual.process');


    // --- ÁREA ADMINISTRATIVA (Apenas Nível Admin) ---
    // Tudo aqui dentro exige que $user->isAdmin() seja true
    Route::middleware(['role:admin'])->group(function () {

        // Gestão de Usuários (Acesso Crítico)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/list', [UserController::class, 'list'])->name('users.list');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

        // Configurações de Sistema e Financeiro
        Route::get('/custos-ia', [CustosIaController::class, 'index'])->name('custos_ia.index');

        // Monitoramento de Infraestrutura (Grafana)
        Route::get('/admin/infra', [App\Http\Controllers\SystemHealthController::class, 'index'])->name('infra.index');

        // Gestão de Templates de IA
        Route::get('/templates-ia', [TemplateIaController::class, 'index'])->name('templates_ia.index');
        Route::get('/templates-ia/list', [TemplateIaController::class, 'list'])->name('templates_ia.list');
        Route::get('/templates-ia/show/{id}', [TemplateIaController::class, 'show'])->name('templates_ia.show');
        Route::post('/templates-ia', [TemplateIaController::class, 'store'])->name('templates_ia.store');
        Route::delete('/templates-ia/{id}', [TemplateIaController::class, 'destroy'])->name('templates_ia.destroy');

        // Ações Destrutivas (Ex: Apenas Admin pode excluir produtos definitivamente)
        Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy'])->name('produtos.destroy');

        // ROTA NOVA: Monitor DLQ
        Route::get('/admin/dlq', [App\Http\Controllers\DlqController::class, 'index'])->name('dlq.index');
        Route::delete('/admin/dlq/clear', [App\Http\Controllers\DlqController::class, 'clear'])->name('dlq.clear');
    });
});
