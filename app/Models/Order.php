<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $guarded = ['id'];

    protected $casts = [
        'attributs'            => 'array',
        'quantite'             => 'integer',
        'prix_unitaire'        => 'decimal:2',
        'total'                => 'decimal:2',
        'total_ttc'            => 'decimal:2',
        'frais_livraison'      => 'decimal:2',
        'temps_avant_commande' => 'integer',
        'date_commande'        => 'datetime',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(OrderHistory::class, 'order_id')->orderByDesc('created_at');
    }

    /** Liste des statuts possibles (FR admin). */
    public const STATUTS = ['nouvelle', 'confirmee', 'expediee', 'livree', 'annulee', 'retour'];
}
