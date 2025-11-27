<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckNivelAcesso
{
    public function handle(Request $request, Closure $next, $nivelNecessario)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Se o nível do usuário for MAIOR (ou seja, menos poder) que o necessário
        if (Auth::user()->nivel_acesso > $nivelNecessario) {
            abort(403, 'Acesso não autorizado.');
        }

        return $next($request);
    }
}
