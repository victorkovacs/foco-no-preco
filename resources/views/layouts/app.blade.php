<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anhanguera Ferramentas - @yield('title', 'Dashboard')</title>
    
    <link rel="icon" type="image/png" href="https://anhangueraferramentas.fbitsstatic.net/sf/img/favicon/apple-touch-icon.png?theme=main&v=202510311402">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="flex min-h-screen bg-gray-100 font-sans">

    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 opacity-0 pointer-events-none transition-opacity z-40 lg:hidden" onclick="toggleSidebar()"></div>

    <header class="bg-white shadow-md p-4 flex items-center fixed top-0 left-0 w-full z-30 lg:hidden h-16">
        <button id="toggle-btn" class="text-gray-700 hover:text-highlight mr-4" onclick="toggleSidebar()">
            <i data-lucide="menu"></i>
        </button>
        <h1 class="text-xl font-semibold text-gray-800">Anhanguera Tools</h1>
    </header>

    <aside id="sidebar" class="sidebar-scrollbar fixed top-0 left-0 h-full w-64 bg-primary-dark text-white z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 overflow-y-auto">
        
        <div class="bg-white border-b border-gray-200 flex items-center justify-center h-16 p-2">
            <img src="https://anhangueraferramentas.fbitsstatic.net/sf/img/logo.svg?theme=main&v=202510311402" alt="Logo" class="h-10">
        </div>

        <nav class="p-4 space-y-2">
            
            @if(Auth::user()->nivel_acesso < 3)
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center p-3 rounded-lg {{ request()->routeIs('dashboard') ? 'nav-item-active' : 'nav-item-inactive' }}">
                    <i data-lucide="layout-dashboard" class="h-5 w-5 mr-3"></i>
                    <span class="font-medium">Dashboard</span>
                </a>
            @endif

            @if(Auth::user()->nivel_acesso < 3)
                <a href="{{ route('produtos.index') }}" class="flex items-center p-3 rounded-lg nav-item-inactive">
                    <i data-lucide="package" class="h-5 w-5 mr-3"></i>
                    <span class="font-medium">Produtos</span>
                </a>
            @endif

            @if(Auth::user()->nivel_acesso < 3)
                <div x-data="{ open: {{ request()->routeIs('produtos.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open" class="flex items-center justify-between w-full p-3 rounded-lg nav-item-inactive focus:outline-none">
                        <span class="flex items-center">
                            <i data-lucide="database" class="h-5 w-5 mr-3"></i>
                            <span class="font-medium">Gestão DB</span>
                        </span>
                        <i data-lucide="chevron-down" class="h-4 w-4 transform transition-transform" :class="{'rotate-180': open}"></i>
                    </button>
                    
                    <div x-show="open" class="pl-6 mt-1 space-y-1 text-sm">
                        <a href="#" class="block p-2 rounded-lg text-gray-400 hover:text-highlight transition-colors">
                            Logs de Operação
                        </a>
                    </div>
                </div>
            @endif

        </nav>

        <div class="absolute bottom-0 w-full p-4 border-t border-gray-700 bg-primary-dark">
            <div class="flex items-center p-2 rounded-lg">
                <img class="h-10 w-10 rounded-full object-cover mr-3" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->email) }}&background=random" alt="Avatar">
                <div class="overflow-hidden">
                    <p class="text-sm font-semibold truncate text-white" title="{{ Auth::user()->email }}">
                        {{ Auth::user()->email }}
                    </p>
                    <p class="text-xs text-green-400">Online</p>
                </div>
                
                <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                    @csrf
                    <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" title="Sair">
                        <i data-lucide="log-out" class="h-5 w-5"></i>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    <div class="flex-1 flex flex-col min-h-screen lg:pl-64 transition-all duration-300"> 
        <main class="flex-1 p-4 lg:p-8 mt-16 lg:mt-0">
            @yield('content')
        </main>
    </div>

    <script>
        // Inicializa ícones
        lucide.createIcons();

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const isClosed = sidebar.classList.contains('-translate-x-full');

            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('opacity-0', 'pointer-events-none');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0', 'pointer-events-none');
            }
        }
    </script>
</body>
</html>