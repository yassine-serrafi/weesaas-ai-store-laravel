<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\DemandeInfoController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\ThankYouController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Front public WeeSaaS — URLs gelées (compat SEO avec l'ancien site)
|--------------------------------------------------------------------------
*/

Route::get('/', [CatalogController::class, 'index'])->name('catalog');
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// Page produit — canonique avec slash final (comme le legacy).
Route::get('/pages/{slug}', [ProductPageController::class, 'show'])
    ->where('slug', '[a-z0-9_-]+')
    ->name('product.show');

// Tunnel de commande
Route::post('/commande', [OrderController::class, 'store'])->middleware('throttle:15,1')->name('order.store');
Route::get('/merci', [ThankYouController::class, 'show'])->name('order.merci');
Route::get('/suivi', [OrderTrackingController::class, 'show'])->name('order.suivi');

// Endpoints AJAX front
Route::get('/villes', [CityController::class, 'index'])->name('villes');
Route::post('/demande-info', [DemandeInfoController::class, 'store'])->middleware('throttle:15,1')->name('demande-info');
Route::post('/track', [TrackingController::class, 'store'])->middleware('throttle:200,1')->name('track'); // CSRF exclu (sendBeacon)

/*
|--------------------------------------------------------------------------
| Panneau d'administration /weeadmin
|--------------------------------------------------------------------------
*/
Route::prefix('weeadmin')->name('admin.')->group(function () {
    Route::get('/', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth.admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Commandes
        Route::get('/commandes', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('/commandes/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('orders.show');
        Route::post('/commandes/{order}/statut', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('/commandes/{order}/confirmer', [\App\Http\Controllers\Admin\OrderController::class, 'markConfirmed'])->name('orders.confirm');
        Route::post('/commandes/{order}/livrer', [\App\Http\Controllers\Admin\OrderController::class, 'markDelivered'])->name('orders.deliver');

        // Création de produit par IA (avant les routes {product})
        Route::get('/produits/generer', [\App\Http\Controllers\Admin\ProductGenerationController::class, 'create'])->name('products.create');
        Route::post('/produits/generer', [\App\Http\Controllers\Admin\ProductGenerationController::class, 'store'])->name('products.generate.store');
        Route::get('/produits/generer/{aiJob}/progress', [\App\Http\Controllers\Admin\ProductGenerationController::class, 'progress'])->name('products.generate.progress');
        Route::get('/produits/generer/{aiJob}/status', [\App\Http\Controllers\Admin\ProductGenerationController::class, 'status'])->name('products.generate.status');

        // Produits
        Route::get('/produits', [\App\Http\Controllers\Admin\ProductController::class, 'index'])->name('products.index');
        Route::get('/produits/{product}/editer', [\App\Http\Controllers\Admin\ProductController::class, 'edit'])->name('products.edit');
        Route::put('/produits/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'update'])->name('products.update');
        Route::post('/produits/{product}/images/{index}/regenerer', [\App\Http\Controllers\Admin\ProductController::class, 'regenerateImage'])->where('index', '[0-9]+')->name('products.image.regenerate');
        Route::post('/produits/{product}/statut', [\App\Http\Controllers\Admin\ProductController::class, 'toggleStatus'])->name('products.status');
        Route::delete('/produits/{product}', [\App\Http\Controllers\Admin\ProductController::class, 'destroy'])->name('products.destroy');

        // Pages institutionnelles
        Route::get('/pages', [\App\Http\Controllers\Admin\StaticPageController::class, 'index'])->name('pages.index');
        Route::get('/pages/creer', [\App\Http\Controllers\Admin\StaticPageController::class, 'create'])->name('pages.create');
        Route::post('/pages', [\App\Http\Controllers\Admin\StaticPageController::class, 'store'])->name('pages.store');
        Route::post('/pages/{page}/statut', [\App\Http\Controllers\Admin\StaticPageController::class, 'toggleStatus'])->name('pages.status');
        Route::delete('/pages/{page}', [\App\Http\Controllers\Admin\StaticPageController::class, 'destroy'])->name('pages.destroy');

        // Clients
        Route::get('/clients', [\App\Http\Controllers\Admin\ClientController::class, 'index'])->name('clients.index');

        // Analytics
        Route::get('/analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/live', [\App\Http\Controllers\Admin\AnalyticsController::class, 'live'])->name('analytics.live');

        // Promotions (codes promo)
        Route::get('/promotions', [\App\Http\Controllers\Admin\PromoController::class, 'index'])->name('promotions.index');
        Route::post('/promotions', [\App\Http\Controllers\Admin\PromoController::class, 'store'])->name('promotions.store');
        Route::post('/promotions/{codePromo}/statut', [\App\Http\Controllers\Admin\PromoController::class, 'toggle'])->name('promotions.toggle');
        Route::delete('/promotions/{codePromo}', [\App\Http\Controllers\Admin\PromoController::class, 'destroy'])->name('promotions.destroy');

        // Gestion des menus
        Route::get('/menus', [\App\Http\Controllers\Admin\MenuController::class, 'index'])->name('menus.index');
        Route::post('/menus', [\App\Http\Controllers\Admin\MenuController::class, 'store'])->name('menus.store');
        Route::put('/menus/{menu}', [\App\Http\Controllers\Admin\MenuController::class, 'update'])->name('menus.update');
        Route::post('/menus/{menu}/statut', [\App\Http\Controllers\Admin\MenuController::class, 'toggle'])->name('menus.toggle');
        Route::delete('/menus/{menu}', [\App\Http\Controllers\Admin\MenuController::class, 'destroy'])->name('menus.destroy');

        // Avis
        Route::get('/avis', [\App\Http\Controllers\Admin\AvisController::class, 'index'])->name('avis.index');
        Route::post('/avis/{avis}/statut', [\App\Http\Controllers\Admin\AvisController::class, 'updateStatus'])->name('avis.status');

        // Paramètres
        Route::get('/parametres', [\App\Http\Controllers\Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/parametres', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::post('/parametres/reset-api', [\App\Http\Controllers\Admin\SettingsController::class, 'resetApi'])->name('settings.reset-api');

        // Notifications (fil admin)
        Route::get('/notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/lu-tout', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/vider-lues', [\App\Http\Controllers\Admin\NotificationController::class, 'clearRead'])->name('notifications.clear-read');
        Route::post('/notifications/{notification}/lu', [\App\Http\Controllers\Admin\NotificationController::class, 'markRead'])->name('notifications.read');
        Route::delete('/notifications/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('notifications.destroy');

        // Logs (journal de génération produit/page + console système)
        Route::get('/logs', [\App\Http\Controllers\Admin\LogsController::class, 'index'])->name('logs.index');
        Route::get('/logs/feed', [\App\Http\Controllers\Admin\LogsController::class, 'feed'])->name('logs.feed');
        Route::post('/logs/purge', [\App\Http\Controllers\Admin\LogsController::class, 'purge'])->name('logs.purge');
        Route::get('/logs/{runId}', [\App\Http\Controllers\Admin\LogsController::class, 'show'])->name('logs.show');
    });
});
