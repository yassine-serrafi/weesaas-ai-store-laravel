<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $table = 'product_images';

    public $timestamps = true;
    const UPDATED_AT = null; // table legacy : created_at uniquement

    protected $guarded = ['id'];

    protected $casts = [
        'statut'     => 'boolean',
        'position'   => 'integer',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** URL d'affichage : WebP si dispo, sinon original. */
    public function getUrlAttribute(): string
    {
        return $this->url_webp ?: $this->url_originale ?: '';
    }
}
