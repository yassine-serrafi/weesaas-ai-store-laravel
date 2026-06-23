<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Suivi d'une génération de page produit par IA (anciennement table `jobs`).
 */
class AiJob extends Model
{
    protected $table = 'ai_jobs';

    protected $guarded = ['id'];

    protected $casts = [
        'params_json'  => 'array',
        'result_data'  => 'array',
        'step_current' => 'integer',
        'step_total'   => 'integer',
        'progress_pct' => 'integer',
        'started_at'   => 'datetime',
        'finished_at'  => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
