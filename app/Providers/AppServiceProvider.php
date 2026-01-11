<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Temporarily disabled until SSL is configured
        // if (config('app.env') === 'production') {
        //     \Illuminate\Support\Facades\URL::forceScheme('https');
        // }

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('Administrator') ? true : null;
        });
    }
}
