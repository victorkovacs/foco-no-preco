<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define a regra de autorização 'admin-access'
        // Verifica se o nível de acesso do usuário é 1 (Admin)
        Gate::define('admin-access', function (User $user) {
            return $user->nivel_acesso === 1;
        });
    }
}
