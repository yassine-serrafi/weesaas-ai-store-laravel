<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Diagnostic du moteur de génération : valide le chemin d'écriture produit
 * (casts JSON, mutateur status/statut, images_json, relation images) SANS appel IA.
 */
class CheckEngine extends Command
{
    protected $signature = 'weesaas:check-engine';
    protected $description = 'Valide le chemin d\'écriture du moteur de génération (sans appel API)';

    public function handle(): int
    {
        $p = Product::create([
            'slug'           => 'engine-test-' . substr(md5(uniqid()), 0, 6),
            'status'         => 'generating',
            'nom_produit'    => 'Engine Test',
            'prix'           => 250,
            'stock_quantite' => 30,
        ]);

        $p->update([
            'status'           => 'active',
            'images_json'      => [['path' => 'uploads/generated/x.webp', 'type' => 'generated']],
            'features'         => [['emoji' => '⭐', 'titre' => 'F1', 'texte' => 'T1']],
            'stats'            => [['val' => '1200+', 'label' => 'Clients']],
            'attrs_json'       => ['groupes' => [['id' => 'taille', 'label' => 'Taille', 'type' => 'pills', 'valeurs' => ['42', '43'], 'required' => true]]],
            'faqs'             => [['q' => 'Q1', 'a' => 'A1']],
            'temoignages_json' => [['prenom' => 'Ali', 'note' => 5, 'texte' => 'Service rapide']],
            'comparison_json'  => [['feature' => 'Livraison', 'nous' => true, 'concurrent' => false]],
            'sections_order'   => ['hero', 'description', 'order_form', 'faq'],
            'description_html' => '<p>Belle <strong>description</strong></p>',
        ]);

        ProductImage::create([
            'product_id'    => $p->id,
            'url_originale' => site_url('uploads/generated/x.webp'),
            'url_webp'      => site_url('uploads/generated/x.webp'),
            'position'      => 0,
            'statut'        => 1,
        ]);

        $f = $p->fresh();
        $raw = DB::table('products')->where('id', $f->id)->value('images_json');

        $this->line("status={$f->status} statut={$f->statut}  (sync mutateur status→statut)");
        $this->line('images_json is_array=' . (is_array($f->images_json) ? 'YES' : 'NO') . ' count=' . count($f->images_json));
        $this->line('attrs_json.groupes=' . count($f->attrs_json['groupes'] ?? []));
        $this->line('features=' . count($f->features) . ' faqs=' . count($f->faqs) . ' temoignages=' . count($f->temoignages_json) . ' comparison=' . count($f->comparison_json));
        $this->line('sections_order has "description"=' . (in_array('description', $f->sections_order, true) ? 'YES' : 'NO'));
        $this->line('images relation count=' . $f->images()->count() . ' | mainImage url=' . ($f->mainImage->url ?? 'none'));
        $this->line('DB raw images_json starts with: ' . substr((string) $raw, 0, 14));

        ProductImage::where('product_id', $p->id)->delete();
        $p->delete();
        $this->info('cleaned ✓');

        return self::SUCCESS;
    }
}
