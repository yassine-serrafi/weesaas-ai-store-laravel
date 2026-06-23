<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Importe les données de l'ancienne base WeeSaaS (PHP vanilla) vers la base Laravel.
 *
 * Pré-requis : importer le dump dans une base MySQL, puis pointer la connexion
 * `legacy` dessus (variables LEGACY_DB_* du .env).
 *
 *   mysql -u root -e "CREATE DATABASE shupaz CHARACTER SET utf8mb4"
 *   mysql -u root shupaz < wpaulepk_shupaz.sql
 *   php artisan weesaas:import-legacy --fresh
 *
 * La table legacy `jobs` (générations IA) est mappée vers `ai_jobs`
 * pour ne pas entrer en collision avec la table `jobs` de la file Laravel.
 */
class ImportLegacyData extends Command
{
    protected $signature = 'weesaas:import-legacy
        {--fresh : Vide les tables cibles avant import}
        {--chunk=300 : Taille des lots d\'insertion}';

    protected $description = 'Importe les données de l\'ancienne base WeeSaaS vers Laravel';

    /**
     * Mapping table legacy → table Laravel.
     * Les colonnes sont identiques (migrations fidèles), copie directe.
     */
    private array $map = [
        'settings'       => 'settings',
        'admins'         => 'admins',
        'admin_sessions' => 'admin_sessions',
        'login_attempts' => 'login_attempts',
        'ip_cache'       => 'ip_cache',
        'menus'          => 'menus',
        'static_pages'   => 'static_pages',
        'products'       => 'products',
        'product_images' => 'product_images',
        'orders'         => 'orders',
        'order_history'  => 'order_history',
        'avis'           => 'avis',
        'codes_promo'    => 'codes_promo',
        'demandes_info'  => 'demandes_info',
        'events'         => 'events',
        'page_views'     => 'page_views',
        'notifications'  => 'notifications',
        'jobs'           => 'ai_jobs', // renommage anti-collision
    ];

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');

        // 1. Vérifier la connexion legacy
        try {
            DB::connection('legacy')->getPdo();
        } catch (Throwable $e) {
            $this->error('Connexion `legacy` impossible : ' . $e->getMessage());
            $this->line('Vérifiez les variables LEGACY_DB_* du .env et que la base existe.');
            return self::FAILURE;
        }

        $this->info('Connexion legacy OK → import vers la base `' . config('database.connections.mysql.database') . '`');

        if ($this->option('fresh')) {
            $this->warn('Mode --fresh : les tables cibles vont être vidées.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $totalRows = 0;

        foreach ($this->map as $source => $target) {
            if (! $this->legacyTableExists($source)) {
                $this->line("  <fg=yellow>skip</> $source (absente de la base legacy)");
                continue;
            }
            if (! Schema::hasTable($target)) {
                $this->line("  <fg=yellow>skip</> $source → $target (table cible absente)");
                continue;
            }

            if ($this->option('fresh')) {
                DB::table($target)->truncate();
            }

            $targetColumns = Schema::getColumnListing($target);
            $count = 0;

            DB::connection('legacy')->table($source)->orderBy($this->keyOf($source))
                ->chunk($chunk, function ($rows) use ($target, $targetColumns, &$count) {
                    $payload = [];
                    foreach ($rows as $row) {
                        // Ne garde que les colonnes présentes dans la table cible
                        $payload[] = array_intersect_key((array) $row, array_flip($targetColumns));
                    }
                    if ($payload) {
                        DB::table($target)->insert($payload);
                        $count += count($payload);
                    }
                });

            $totalRows += $count;
            $this->line("  <fg=green>ok</>   $source → $target : <options=bold>$count</> lignes");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->newLine();
        $this->info("Import terminé — $totalRows lignes copiées.");

        return self::SUCCESS;
    }

    private function legacyTableExists(string $table): bool
    {
        try {
            return Schema::connection('legacy')->hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    /** Clé d'ordonnancement pour le chunk (ip_cache n'a pas d'id). */
    private function keyOf(string $table): string
    {
        return $table === 'ip_cache' ? 'ip' : 'id';
    }
}
