<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodePromo extends Model
{
    protected $table = 'codes_promo';

    public $timestamps = true;
    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = [
        'valeur'     => 'decimal:2',
        'min_achat'  => 'decimal:2',
        'max_usage'  => 'integer',
        'nb_usage'   => 'integer',
        'actif'      => 'boolean',
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
