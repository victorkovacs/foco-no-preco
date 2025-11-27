<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - Foco no Preço</title>
    <link rel="icon" type="image/png" href="https://anhangueraferramentas.fbitsstatic.net/sf/img/favicon/apple-touch-icon.png">
    
    
    <!-- Scripts e Estilos -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js para Dropdowns -->
    <script src="//unpkg.com/alpinejs" defer></script>
    
    <!-- Ícones Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Fonte -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* --- CORES PERSONALIZADAS (AZUL E VERMELHO) --- */
        :root {
            --primary-dark: #002D5A; /* Azul do Fundo */
            --highlight-red: #D00000; /* Vermelho do Destaque */
        }

        .bg-primary-dark { background-color: var(--primary-dark); }
        .text-primary-dark { color: var(--primary-dark); }
        .text-highlight { color: var(--highlight-red); }
        .border-highlight { border-color: var(--highlight-red); }

        /* Estilo do Link Padrão (Inativo) */
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #d1d5db; /* gray-300 */
            transition: all 0.2s;
            font-size: 0.9rem;
            font-weight: 500;
            border-left: 4px solid transparent;
        }
        
        /* Hover: Fica com texto vermelho e fundo levemente escuro */
        .sidebar-link:hover {
            background-color: rgba(0, 0, 0, 0.2);
            color: var(--highlight-red);
        }
        .sidebar-link:hover i {
            color: var(--highlight-red);
        }

        /* Estilo do Link Ativo (Fundo Claro, Texto Azul, Borda Vermelha) */
        .sidebar-link.active {
            background-color: #f3f4f6; /* gray-100 */
            color: var(--primary-dark);
            border-left: 4px solid var(--highlight-red);
            font-weight: 600;
        }
        .sidebar-link.active i {
            color: var(--highlight-red);
        }

        /* Estilo para Submenus */
        .submenu-link {
            padding-left: 3.5rem;
            font-size: 0.85rem;
        }

        /* Barra de rolagem fina para o menu */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
        }
    </style>
</head>
<body class="bg-gray-100 h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-primary-dark text-white flex-shrink-0 flex flex-col transition-all duration-300 shadow-xl z-20 hidden md:flex" id="sidebar">
        
        <!-- Logo -->
        <div class="h-20 flex items-center justify-center border-b border-white/10 bg-white">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQa1GDjBUL-mWF8fCQkjkEGV51v23_epHkOmq7hsGGtZSq0pKCveXozuAaQ9LvV-_Mh2A&usqp=CAU" 
                 alt="Foco no Preço" 
                 class="h-12 w-auto object-contain">
        </div>

        <!-- Menu Navigation -->
        <nav class="flex-1 overflow-y-auto py-4 sidebar-scroll">
            @php 
                $user = Auth::user();
                $nivel = $user->nivel_acesso;
                $rotaAtual = Route::currentRouteName();
            @endphp

            <!-- 1. Dashboard -->
            @if($nivel != 3)
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ $rotaAtual == 'dashboard' ? 'active' : '' }}">
                    <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i>
                    Dashboard
                </a>
            @endif

            <!-- 2. Produtos -->
            @if(in_array($nivel, [1, 2, 4]))
                <a href="{{ route('produtos.index') }}" class="sidebar-link {{ $rotaAtual == 'produtos.index' ? 'active' : '' }}">
                    <i data-lucide="package" class="w-5 h-5 mr-3"></i>
                    Produtos
                </a>
            @endif

            <!-- DROPDOWN: OPERACIONAL -->
            <div x-data="{ open: {{ in_array($rotaAtual, ['produtos.mass_update', 'curadoria.index', 'ia_manual.index', 'produtos.gerenciar', 'templates_ia.index']) ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full sidebar-link justify-between cursor-pointer focus:outline-none">
                    <div class="flex items-center">
                        <i data-lucide="database" class="w-5 h-5 mr-3"></i>
                        <span>Operacional</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                </button>
                
                <div x-show="open" class="bg-black/20" x-transition>
                    
                    <!-- Cadastrar Produto -->
                    @if(in_array($nivel, [1, 2, 4]))
                    <a href="{{ route('produtos.gerenciar') }}" class="sidebar-link submenu-link {{ $rotaAtual == 'produtos.gerenciar' ? 'active' : '' }}">
                        Editar Produto
                    </a>
                    @endif
                    <!-- Curadoria -->
                    @if(in_array($nivel, [1, 2]))
                        <a href="{{ route('curadoria.index') }}" class="sidebar-link submenu-link {{ request()->is('curadoria*') ? 'active' : '' }}">
                            Curadoria
                        </a>
                    @endif

                    <!-- IA Manual -->
                    @if(in_array($nivel, [1, 2, 4]))
                        <a href="{{ route('ia_manual.index') }}" class="sidebar-link submenu-link {{ request()->is('ia-manual*') ? 'active' : '' }}">
                            IA Mapeamento
                        </a>
                    @endif

                    

                    <!-- Templates IA -->
                    

                    <a href="{{ route('produtos_dashboard.index') }}" class="sidebar-link submenu-link {{ $rotaAtual == 'produtos.mass_update' ? 'active' : '' }}">
                        Cadastro de produto
                    </a>
                    <a href="{{ route('templates_ia.index') }}" class="sidebar-link submenu-link {{ request()->is('templates_ia*') ? 'active' : '' }}">
                        Templates IA
                    </a>

                
                </div>
            </div>


            <!-- DROPDOWN: ADMINISTRAÇÃO -->
            @if(in_array($nivel, [1, 2]))
            <div x-data="{ open: {{ in_array($rotaAtual, ['users.index', 'concorrentes.index', 'custos_ia.index']) ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full sidebar-link justify-between cursor-pointer focus:outline-none">
                    <div class="flex items-center">
                        <i data-lucide="shield" class="w-5 h-5 mr-3"></i>
                        <span>Administração</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                </button>
                
                <div x-show="open" class="bg-black/20" x-transition>
                    <a href="{{ route('users.index') }}" class="sidebar-link submenu-link {{ request()->is('users*') ? 'active' : '' }}">
                        Equipe
                    </a>
                    <a href="{{ route('concorrentes.index') }}" class="sidebar-link submenu-link {{ request()->is('concorrentes*') ? 'active' : '' }}">
                        Concorrentes
                    </a>
                    <!-- Custos IA movido para cá -->
                    <a href="{{ route('custos_ia.index') }}" class="sidebar-link submenu-link {{ request()->is('custos-ia*') ? 'active' : '' }}">
                        Custos IA
                    </a>
                </div>
            </div>
            @endif


            <!-- DROPDOWN: SISTEMA -->
            @if(in_array($nivel, [1, 2]))
            <div x-data="{ open: {{ request()->is('admin/infra*') || request()->is('admin/dlq*') || request()->is('admin/configuracoes*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="w-full sidebar-link justify-between cursor-pointer focus:outline-none">
                    <div class="flex items-center">
                        <i data-lucide="server" class="w-5 h-5 mr-3"></i>
                        <span>Sistema</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200" :class="{'rotate-180': open}"></i>
                </button>
                
                <div x-show="open" class="bg-black/20" x-transition>
                    <a href="{{ route('infra.index') }}" class="sidebar-link submenu-link {{ request()->is('admin/infra*') ? 'active' : '' }}">
                        Infraestrutura
                    </a>
                    <a href="{{ route('dlq.index') }}" class="sidebar-link submenu-link {{ request()->is('admin/dlq*') ? 'active' : '' }}">
                        Erros (DLQ)
                    </a>
                    <!-- Config. Globais movido para cá -->
                    <a href="{{ route('admin.configuracoes.index') }}" class="sidebar-link submenu-link {{ request()->is('admin/configuracoes*') ? 'active' : '' }}">
                        Config. Globais
                    </a>
                </div>
            </div>
            @endif

            <!-- Alterar Senha (Fora de dropdown) -->
            <a href="{{ route('profile.password.edit') }}" class="sidebar-link {{ request()->is('perfil/senha*') ? 'active' : '' }} mt-4 border-t border-white/10 pt-4">
                <i data-lucide="key" class="w-5 h-5 mr-3"></i>
                Alterar Senha
            </a>

        </nav>
        
        <!-- User Info Footer Ajustado -->
        <div class="border-t border-white/10 p-4 bg-black/10 flex items-center justify-between">
            <div class="flex items-center overflow-hidden">
                <!-- Avatar com a primeira letra do Email -->
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-white text-primary-dark flex items-center justify-center text-sm font-bold shadow-md uppercase">
                    {{ substr(Auth::user()->email, 0, 1) }}
                </div>
                <div class="ml-3 overflow-hidden">
                    <p class="text-sm font-bold text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-gray-400 truncate" title="{{ Auth::user()->email }}">
                        {{ Auth::user()->email }}
                    </p>
                </div>
            </div>
            
            <!-- Botão Sair (Apenas Ícone) -->
            <form method="POST" action="{{ route('logout') }}" class="ml-2">
                @csrf
                <button type="submit" class="p-2 rounded hover:bg-red-600 hover:text-white text-gray-400 transition-colors" title="Sair">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </button>
            </form>
        </div>
    </aside>

    <!-- Mobile Header -->
    <div class="md:hidden fixed w-full z-30 bg-primary-dark text-white shadow-md flex items-center justify-between p-4">
        <span class="font-bold text-lg">Foco no Preço</span>
        <button onclick="document.getElementById('sidebar').classList.toggle('hidden');" class="p-2 rounded hover:bg-white/10">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
    </div>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-gray-100 p-4 md:p-8 pt-20 md:pt-8 w-full">
        @if(session('success'))
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm flex items-center" role="alert">
                <i data-lucide="check-circle" class="w-5 h-5 mr-2"></i>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm flex items-center" role="alert">
                <i data-lucide="alert-circle" class="w-5 h-5 mr-2"></i>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
                <div class="flex items-center mb-2">
                    <i data-lucide="x-circle" class="w-5 h-5 mr-2 text-red-600"></i>
                    <p class="font-bold text-red-600">Atenção!</p>
                </div>
                <ul class="list-disc list-inside text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>