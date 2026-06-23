<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

/**
 * Diagnostic rapide : vérifie que chaque modèle Eloquent se mappe bien à sa table.
 *   php artisan weesaas:doctor
 */
class Doctor extends Command
{
    protected $signature = 'weesaas:doctor';
    protected $description = 'Vérifie la connexion DB et le mapping des modèles WeeSaaS';

    private array $models = [
        'Product', 'ProductImage', 'Order', 'OrderHistory', 'Setting', 'Admin',
        'AdminSession', 'LoginAttempt', 'IpCache', 'Menu', 'StaticPage', 'Avis',
        'CodePromo', 'DemandeInfo', 'Event', 'PageView', 'Notification', 'AiJob',
    ];

    public function handle(): int
    {
        $ok = true;
        foreach ($this->models as $m) {
            $class = "App\\Models\\$m";
            try {
                $n = $class::query()->count();
                $this->line(sprintf('  <fg=green>OK</>   %-14s count=%d', $m, $n));
            } catch (Throwable $e) {
                $ok = false;
                $this->line(sprintf('  <fg=red>FAIL</> %-14s %s', $m, $e->getMessage()));
            }
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
