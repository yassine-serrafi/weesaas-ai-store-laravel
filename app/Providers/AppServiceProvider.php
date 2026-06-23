<?php

namespace App\Providers;

use App\Models\Menu;
use App\Services\SettingsRepository;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Paramètres boutique partagés (chargés une fois, cache 1h).
        $this->app->singleton(SettingsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Partage $shop (tableau clé/valeur des settings) à toutes les vues front.
        // Les menus header/footer sont résolus dans le layout selon $lang_code.
        View::composer(['layouts.front', 'partials.*', 'catalog', 'product.*', 'page.*'], function ($view) {
            $settings = app(SettingsRepository::class);
            $view->with('shop', $settings->all());
        });

        // Menus de navigation (réutilisés par header/footer).
        View::composer('partials.front-header', function ($view) {
            $view->with('headerMenus', Menu::visible('header')->get());
        });
        View::composer('partials.front-footer', function ($view) {
            $view->with('footerMenus', Menu::visible('footer')->get());
        });
    }
}
