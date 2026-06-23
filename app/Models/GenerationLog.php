<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une ligne du journal de génération (page Logs admin).
 * Regroupées par `run_id`, les lignes forment la timeline d'une exécution.
 */
class GenerationLog extends Model
{
    protected $table = 'generation_logs';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'context_json' => 'array',
        'step'         => 'integer',
        'duration_ms'  => 'integer',
        'created_at'   => 'datetime',
    ];

    /** Couleur de badge associée au niveau. */
    public function levelBadge(): string
    {
        return match ($this->level) {
            'success' => 'badge-active',
            'warning' => 'badge-paused',
            'error'   => 'badge-annulee',
            default   => 'badge-confirmee',
        };
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            'product' => '🖼️ Produit',
            'page'    => '📄 Page',
            'system'  => '⚙️ Système',
            default   => $this->source,
        };
    }
}
