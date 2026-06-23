<?php

namespace App\Console\Commands;

use App\Services\SettingsRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Remet la boutique en « version propre / début » :
 *  - vide le catalogue + les données transactionnelles (produits, commandes,
 *    images, analytics, avis, leads, promos, notifications, jobs IA) ;
 *  - conserve la configuration boutique : settings, admins, menus, pages statiques ;
 *  - bascule la langue par défaut en français.
 *
 *   php artisan weesaas:fresh-start --force
 */
class FreshStart extends Command
{
    protected $signature = 'weesaas:fresh-start {--force : Ne pas demander de confirmation}';
    protected $description = 'Réinitialise le catalogue/commandes pour repartir d\'une boutique propre (FR)';

    /** Tables vidées (catalogue + transactionnel + analytics). */
    private array $tablesToClear = [
        'products', 'product_images', 'orders', 'order_history',
        'events', 'page_views', 'ai_jobs', 'demandes_info',
        'avis', 'codes_promo', 'notifications', 'login_attempts',
    ];

    public function handle(SettingsRepository $settings): int
    {
        if (! $this->option('force') && ! $this->confirm('Vider le catalogue + toutes les commandes/analytics (config boutique conservée) ?')) {
            $this->warn('Annulé.');
            return self::SUCCESS;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->tablesToClear as $table) {
            DB::table($table)->truncate();
            $this->line("  <fg=green>vidée</> $table");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Langue par défaut → français (boutique francophone).
        $settings->set('langue_defaut', 'fr');
        $settings->flush();
        $this->line('  <fg=green>réglé</> langue_defaut = fr');

        // Nettoyage des fichiers d'images générées/uploadées localement.
        foreach (['uploads/generated', 'uploads/originals'] as $dir) {
            $path = public_path($dir);
            if (is_dir($path)) {
                File::cleanDirectory($path);
                $this->line("  <fg=green>nettoyé</> public/$dir");
            }
        }

        $this->newLine();
        $this->info('Boutique réinitialisée en version propre (français). Catalogue vide, config conservée.');

        return self::SUCCESS;
    }
}
