<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mercado Scan</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Cores do Logo */
        .bg-primary-dark {
            background-color: #002D5A;
        }
        .bg-highlight {
            background-color: #D00000;
        }
        .text-highlight {
            color: #D00000;
        }
        .border-highlight {
            border-color: #D00000;
        }
        .hover\:bg-highlight-dark:hover {
            background-color: #B00000;
        }
    </style>
</head>
<body class="bg-primary-dark min-h-screen flex items-center justify-center p-4">

    <div class="bg-white p-8 md:p-12 rounded-2xl shadow-2xl w-full max-w-md border-t-4 border-highlight">
        
        <img src="https://anhangueraferramentas.fbitsstatic.net/sf/img/logo.svg?theme=main&v=202510311402" alt="Logo Mercado Scan" class="w-48 mx-auto mb-6">

        @error('email') 
            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg" role="alert">
                <p class="font-bold">Erro de Login</p>
                <p>{{ $message }}</p>
            </div>
        @enderror
        
        <form action="{{ url('/login') }}" method="POST">
            
            @csrf
            
            <div class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-highlight focus:border-transparent" 
                        placeholder="voce@exemplo.com"
                        required 
                        autocomplete="email"
                        value="{{ old('email') }}"
                    >
                </div>
                
                <div>
                    <label for="senha" class="block text-sm font-semibold text-gray-700 mb-1">Senha</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-highlight focus:border-transparent pr-10" 
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" onclick="togglePasswordVisibility('senha', this)"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-blue-600 focus:outline-none">
                            <i class="icon-eye" data-lucide="eye" width="18" height="18"></i>
                            <i class="icon-eye-off hidden" data-lucide="eye-off" width="18" height="18"></i>
                        </button>
                    </div>
                </div>

                <button 
                    type="submit" 
                    class="w-full text-white p-3 rounded-lg font-bold text-lg transition-colors duration-200"
                    style="background-color: #002D5A;" 
                    onmouseover="this.style.backgroundColor='#004182'"
                    onmouseout="this.style.backgroundColor='#002D5A'"
                >
                    Entrar
                </button>
            </div>
        </form>
    </div>

    <script>
        function togglePasswordVisibility(inputId, buttonElement) {
            const input = document.getElementById(inputId);
            const iconEye = buttonElement.querySelector('.icon-eye');
            const iconEyeOff = buttonElement.querySelector('.icon-eye-off');

            if (input.type === 'password') {
                input.type = 'text';
                iconEye.classList.add('hidden');
                iconEyeOff.classList.remove('hidden');
            } else {
                input.type = 'password';
                iconEye.classList.remove('hidden');
                iconEyeOff.classList.add('hidden');
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>