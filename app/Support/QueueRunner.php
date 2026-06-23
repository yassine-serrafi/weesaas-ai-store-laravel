<?php

namespace App\Support;

/**
 * Démarre un worker de file d'attente à la volée, en arrière-plan (détaché),
 * pour traiter immédiatement les jobs sans worker permanent.
 *
 * Utilisé après le dispatch d'une génération produit : un seul clic suffit,
 * le worker traite le job puis s'arrête (--stop-when-empty).
 */
class QueueRunner
{
    public static function spawn(): void
    {
        // Inutile si la queue est synchrone ou si la fonctionnalité est désactivée.
        if (config('queue.default') === 'sync') {
            return;
        }
        if (! config('weesaas.queue.auto_worker', true)) {
            return;
        }

        $php = config('weesaas.php_binary') ?: PHP_BINARY;
        $artisan = base_path('artisan');

        if (! is_file($php)) {
            return; // binaire PHP introuvable : on laisse le job en file (worker manuel).
        }

        $args = 'queue:work --stop-when-empty --tries=1 --timeout=1800 --sleep=1';

        try {
            if (stripos(PHP_OS_FAMILY, 'Windows') === 0) {
                // Détaché, sans fenêtre. `start` rend la main immédiatement.
                $cmd = 'start /B "" "' . $php . '" "' . $artisan . '" ' . $args . ' > NUL 2>&1';
                pclose(popen('cmd /C ' . $cmd, 'r'));
            } else {
                exec('nohup ' . escapeshellarg($php) . ' ' . escapeshellarg($artisan) . ' ' . $args . ' > /dev/null 2>&1 &');
            }
        } catch (\Throwable $e) {
            report($e); // ne jamais casser la requête à cause du spawn.
        }
    }
}
