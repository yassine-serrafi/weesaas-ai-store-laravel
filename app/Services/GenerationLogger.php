<?php

namespace App\Services;

use App\Models\GenerationLog;
use Illuminate\Support\Str;
use Throwable;

/**
 * Point d'écriture unique du journal de génération (table generation_logs).
 *
 * Usage :
 *   $log = GenerationLogger::start('product', $productId, $nom);
 *   $log->step(1, 'Analyse image')->info('Image reçue', ['size' => …]);
 *   $log->success('Terminé');
 *
 * La durée de chaque ligne est calculée automatiquement depuis la précédente.
 */
class GenerationLogger
{
    private float $lastTick;

    private int $currentStep = 0;

    private string $currentStepLabel = '';

    public function __construct(
        public string $runId,
        public string $source,
        public ?int $refId = null,
        public string $refLabel = '',
    ) {
        $this->lastTick = microtime(true);
    }

    /** Démarre un nouveau run (génère un run_id). */
    public static function start(string $source, ?int $refId = null, string $refLabel = ''): self
    {
        return new self((string) Str::uuid(), $source, $refId, $refLabel);
    }

    /** Définit l'étape courante et journalise son entrée. */
    public function step(int $step, string $label): self
    {
        $this->currentStep = $step;
        $this->currentStepLabel = $label;
        $this->write('info', $label, [], $label);
        return $this;
    }

    public function info(string $message, array $context = []): self
    {
        return $this->write('info', $message, $context);
    }

    public function success(string $message, array $context = []): self
    {
        return $this->write('success', $message, $context);
    }

    public function warning(string $message, array $context = []): self
    {
        return $this->write('warning', $message, $context);
    }

    public function error(string $message, array $context = []): self
    {
        return $this->write('error', $message, $context);
    }

    /** Met à jour le libellé affiché (ex. nom produit final). */
    public function setLabel(string $label): self
    {
        $this->refLabel = $label;
        return $this;
    }

    private function write(string $level, string $message, array $context, ?string $stepLabel = null): self
    {
        $now = microtime(true);
        $durationMs = (int) round(($now - $this->lastTick) * 1000);
        $this->lastTick = $now;

        try {
            GenerationLog::create([
                'run_id'       => $this->runId,
                'source'       => $this->source,
                'ref_id'       => $this->refId,
                'ref_label'    => mb_substr($this->refLabel, 0, 255),
                'level'        => $level,
                'step'         => $this->currentStep,
                'step_label'   => mb_substr($stepLabel ?? $this->currentStepLabel, 0, 255),
                'message'      => $message,
                'context_json' => $this->truncate($context),
                'duration_ms'  => $durationMs,
            ]);
        } catch (Throwable $e) {
            // Le logging ne doit jamais casser la génération.
            report($e);
        }

        return $this;
    }

    /** Tronque les valeurs volumineuses (réponses IA) pour garder la table légère. */
    private function truncate(array $context): array
    {
        array_walk_recursive($context, function (&$v) {
            if (is_string($v) && mb_strlen($v) > 2000) {
                $v = mb_substr($v, 0, 2000) . '… [tronqué, ' . mb_strlen($v) . ' car.]';
            }
        });
        return $context;
    }
}
