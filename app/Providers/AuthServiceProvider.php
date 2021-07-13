<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Utilice el método Gate :: guessPolicyNamesUsing para personalizar la lógica de búsqueda del archivo de política
        Gate::guessPolicyNamesUsing(function ($class) {
            // class_basename es una función auxiliar proporcionada por Laravel para obtener el nombre corto de la clase
            // Por ejemplo, pasar \ App \ Models \ User devolverá User
            return '\\App\\Policies\\'.class_basename($class).'Policy';
        });
    }
}
