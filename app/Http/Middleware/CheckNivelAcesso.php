<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CheckNivelAcesso
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role  (admin, colaborador, etc)
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // 1. Verifica se está logado
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 2. Lógica de verificação baseada no parametro $role
        if ($role === 'admin' && !$user->isAdmin()) {
            abort(403, 'Acesso não autorizado. Apenas administradores.');
        }

        // Se quisermos ser estritos com colaboradores no futuro:
        // if ($role === 'colaborador' && ... )

        return $next($request);
    }
}
