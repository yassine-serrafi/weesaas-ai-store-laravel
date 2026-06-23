<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Produit / page de vente.
 *
 * Le schéma legacy comporte de nombreuses colonnes dupliquées (status/statut,
 * stock/stock_quantite, pays_vente/pays, rating/note_produit, …). Les accesseurs
 * ci-dessous exposent une lecture unifiée ; le mutateur de statut/stock garde les
 * deux colonnes synchronisées pour ne casser ni l'ancien code ni les imports.
 */
class Product extends Model
{
    protected $table = 'products';

    protected $guarded = ['id'];

    protected $casts = [
        // Drapeaux
        'livraison_gratuite' => 'boolean',
        'timer_actif'        => 'boolean',
        'stock_affiche'      => 'boolean',
        'stock_dynamique'    => 'boolean',
        'urgency_actif'      => 'boolean',
        'whatsapp_actif'     => 'boolean',
        'featured'           => 'boolean',
        'stock_desactive'    => 'boolean',
        // Montants
        'prix'            => 'decimal:2',
        'prix_barre'      => 'decimal:2',
        'frais_livraison' => 'decimal:2',
        'ca_total'        => 'decimal:2',
        'note_produit'    => 'decimal:1',
        'rating'          => 'decimal:1',
        // Configuration sections + contenu structuré (JSON)
        'sections_json'       => 'array',
        'sections_order'      => 'array',
        'sections_disabled'   => 'array',
        'attrs_json'          => 'array',
        'attributs'           => 'array',
        'features'            => 'array',
        'stats'               => 'array',
        'garanties_json'      => 'array',
        'faqs'                => 'array',
        'faq_json'            => 'array',
        'testimonials'        => 'array',
        'temoignages_json'    => 'array',
        'comparison_json'     => 'array',
        'images_json'         => 'array',
        'preuve_sociale_json' => 'array',
        'seo_json'            => 'array',
        'historique_json'     => 'array',
        // Dates
        'page_generated_at' => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    /* ───────────────────────── Relations ───────────────────────── */

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')
            ->where('statut', 1)
            ->orderBy('position');
    }

    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'product_id')
            ->where('statut', 1)
            ->where('position', 0);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'product_id');
    }

    public function avis(): HasMany
    {
        return $this->hasMany(Avis::class, 'product_id');
    }

    public function aiJob(): HasOne
    {
        return $this->hasOne(AiJob::class, 'product_id')->latestOfMany();
    }

    /* ───────────────────────── Scopes ───────────────────────── */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /* ─────────────── Lecture unifiée des doublons legacy ─────────────── */

    /** Stock effectif : prend la colonne non nulle la plus pertinente. */
    public function getStockDispoAttribute(): int
    {
        return (int) ($this->attributes['stock_quantite'] ?? $this->attributes['stock'] ?? 0);
    }

    /** Le suivi de stock est-il désactivé (produit « illimité ») ? */
    public function getStockUntrackedAttribute(): bool
    {
        return (bool) ($this->attributes['stock_desactive'] ?? false);
    }

    /** Disponible à la commande : illimité, ou stock réel > 0. */
    public function getIsAvailableAttribute(): bool
    {
        return $this->stock_untracked || $this->stock_dispo > 0;
    }

    /** Pays de vente effectif. */
    public function getPaysEffectifAttribute(): string
    {
        return (string) ($this->attributes['pays_vente'] ?: $this->attributes['pays'] ?: 'maroc');
    }

    /* ─────────────── Synchronisation des doublons en écriture ─────────────── */

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = $value;
        $this->attributes['statut'] = $value; // garde le doublon cohérent
    }

    public function setStockQuantiteAttribute($value): void
    {
        $this->attributes['stock_quantite'] = $value;
        $this->attributes['stock'] = $value;
    }
}
