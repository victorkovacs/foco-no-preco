<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Controllers
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
use App\Http\Controllers\ConfiguracaoController;
use App\Http\Controllers\DlqController;
use App\Http\Controllers\SystemHealthController;

// Redirecionamento inicial
Route::get('/', function () {
    return redirect()->route('login');
});

// --- AUTENTICAÇÃO (Públicas) ---
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [LoginController::class, 'authenticate']);
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


// ==============================================================================
//  ROTAS PROTEGIDAS (LOGADOS)
// ==============================================================================
Route::middleware('auth')->group(function () {

    // --- ROTA DE ENTRADA INTELIGENTE ---
    Route::get('/index', function () {
        $user = Auth::user();
        // Se for nivel CADASTRO (3), vai direto para gestão de produtos
        if ($user->nivel_acesso == User::NIVEL_CADASTRO) {
            return redirect()->route('produtos_dashboard.index');
        }
        // Os demais vão para o Dashboard
        return redirect()->route('dashboard');
    })->name('index');


    // --- ACESSO BÁSICO (Nível 4 - Usuário Comum e superiores) ---
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/detalhes', [DetalheController::class, 'index'])->name('dashboard.detalhes');

    // Exportação do Dashboard (Filtrada)
    Route::get('/export/dashboard', [ExportController::class, 'exportDashboardDetalhes'])->name('export.dashboard');

    // Perfil
    Route::get('/perfil/senha', [ProfileController::class, 'edit'])->name('profile.password.edit');
    Route::post('/perfil/senha', [ProfileController::class, 'update'])->name('profile.password.update');

    // Visualização de Produtos (Leitura)
    Route::get('/produtos', [ProdutoController::class, 'index'])->name('produtos.index');
    Route::get('/produtos/grafico', [ProdutoController::class, 'getDadosGrafico'])->name('produtos.grafico');

    // API Saúde (Widget)
    Route::get('/api/health-check', [SystemHealthController::class, 'check'])->name('api.health_check');


    // ==============================================================================
    //  GRUPO OPERACIONAL (Nível 3 - Cadastro e superiores)
    // ==============================================================================
    Route::middleware(['role:' . User::NIVEL_CADASTRO])->group(function () {

        // Produtos (Edição, Monitoramento, Ações)
        Route::post('/produtos/monitorar', [ProdutoController::class, 'iniciarMonitoramento'])->name('produtos.monitorar');
        Route::get('/produtos/gerenciar', [ProdutoController::class, 'gerenciar'])->name('produtos.gerenciar');

        // --- TEMPLATES DE IA ---
        Route::get('/templates-ia/list', [TemplateIaController::class, 'list'])->name('templates_ia.list');
        Route::post('/templates-ia/gerar-automatico', [TemplateIaController::class, 'gerarPromptAutomatico'])->name('templates_ia.gerar_auto');

        // Resource com URL em kebab-case (hífen) e Nomes em snake_case (underline)
        Route::resource('templates-ia', TemplateIaController::class)->names([
            'index' => 'templates_ia.index',
            'create' => 'templates_ia.create',
            'store' => 'templates_ia.store',
            'show' => 'templates_ia.show',
            'edit' => 'templates_ia.edit',
            'update' => 'templates_ia.update',
            'destroy' => 'templates_ia.destroy',
        ]);

        // Atualização em Massa
        Route::get('/produtos/massa', [ProdutoController::class, 'massUpdateForm'])->name('produtos.mass_update');
        Route::post('/produtos/massa', [ProdutoController::class, 'massUpdateProcess'])->name('produtos.mass_update_process');
        Route::put('/produtos/alvo/{idAlvo}/link', [ProdutoController::class, 'updateAlvoLink'])->name('produtos.alvos.update');

        // Edição Individual
        Route::get('/produtos/{id}/fetch', [ProdutoController::class, 'edit'])->name('produtos.fetch');
        Route::get('/produtos/{id}/editar', [ProdutoController::class, 'edit'])->name('produtos.edit');
        Route::put('/produtos/{id}', [ProdutoController::class, 'update'])->name('produtos.update');

        // Geração de Conteúdo IA
        Route::get('/produtos/conteudo', [ProdutoDashboardController::class, 'index'])->name('produtos_dashboard.index');
        Route::post('/produtos/conteudo', [ProdutoDashboardController::class, 'store'])->name('produtos_dashboard.store');
        Route::post('/produtos/conteudo/processar', [ProdutoDashboardController::class, 'sendBatch'])->name('produtos_dashboard.processar');
        Route::post('/produtos/conteudo/import', [ProdutoDashboardController::class, 'import'])->name('produtos_dashboard.import');
        Route::get('/produtos/conteudo/template', [ProdutoDashboardController::class, 'downloadTemplate'])->name('produtos_dashboard.template');

        // Curadoria e IA Manual
        Route::get('/curadoria', [CuradoriaController::class, 'index'])->name('curadoria.index');
        Route::get('/curadoria/search', [CuradoriaController::class, 'search'])->name('curadoria.search');
        Route::get('/ia-manual', [IaManualController::class, 'index'])->name('ia_manual.index');
        Route::post('/ia-manual/process', [IaManualController::class, 'process'])->name('ia_manual.process');

        // Exportações (Antiga - Concorrentes Gerais)
        Route::get('/export/concorrentes', [ExportController::class, 'exportConcorrentes'])->name('export.concorrentes');
    });


    // ==============================================================================
    //  GRUPO GESTÃO / ADMIN (Nível 2 - Admin e superiores)
    // ==============================================================================
    Route::middleware(['role:' . User::NIVEL_ADMIN])->group(function () {

        // Gestão de Time (Users)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/list', [UserController::class, 'list'])->name('users.list');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

        // Gestão de Concorrentes/Vendedores
        Route::get('/concorrentes', [VendedorController::class, 'index'])->name('concorrentes.index');
        Route::put('/concorrentes/{id}', [VendedorController::class, 'update'])->name('concorrentes.update');

        // [NOVO] Rota de Teste de Seletor
        Route::post('/concorrentes/testar', [VendedorController::class, 'testarSeletor'])->name('concorrentes.testar');

        // Infra e Monitoramento
        Route::get('/admin/infra', [SystemHealthController::class, 'index'])->name('infra.index');
        Route::get('/admin/dlq', [DlqController::class, 'index'])->name('dlq.index');
        Route::delete('/admin/dlq/clear', [DlqController::class, 'clear'])->name('dlq.clear');

        // Ações Destrutivas em Produtos (Excluir)
        Route::delete('/produtos/{id}', [ProdutoController::class, 'destroy'])->name('produtos.destroy');
    });


    // ==============================================================================
    //  GRUPO MESTRE / DONO (Nível 1 - Apenas Mestre)
    // ==============================================================================
    Route::middleware(['role:' . User::NIVEL_MESTRE])->group(function () {

        // Configurações Globais do Sistema
        Route::get('/admin/configuracoes', [ConfiguracaoController::class, 'index'])->name('admin.configuracoes.index');
        Route::post('/admin/configuracoes', [ConfiguracaoController::class, 'update'])->name('admin.configuracoes.update');
        Route::post('/admin/configuracoes/gerar-token', [ConfiguracaoController::class, 'gerarTokenOrganizacao'])
            ->name('admin.configuracoes.gerar_token');

        // Financeiro / Custos
        Route::get('/custos-ia', [CustosIaController::class, 'index'])->name('custos_ia.index');
    });
});
