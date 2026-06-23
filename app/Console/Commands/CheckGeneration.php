<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\StaticPage;
use Illuminate\Console\Command;

/**
 * Vérifie qu'une future génération (produit OU page) a tout ce qu'il faut :
 * crée un produit actif complet (avec image WebP réelle) + une page statique
 * avec tous les types de blocs. À rendre ensuite via le navigateur, puis nettoyer
 * avec --clean.
 */
class CheckGeneration extends Command
{
    protected $signature = 'weesaas:check-generation {--clean : Supprime les données de test}';
    protected $description = 'Prépare/vérifie un produit et une page de test pour la génération';

    private const PID = 999001;

    public function handle(): int
    {
        if ($this->option('clean')) {
            ProductImage::where('product_id', self::PID)->delete();
            Product::whereKey(self::PID)->delete();
            StaticPage::where('slug', 'test-page-blocks')->delete();
            $dir = public_path('uploads/generated/' . self::PID);
            if (is_dir($dir)) {
                array_map('unlink', glob("$dir/*"));
                @rmdir($dir);
            }
            $this->info('Données de test supprimées ✓');
            return self::SUCCESS;
        }

        // 1. Image WebP réelle (teste GD + le dossier).
        $dir = public_path('uploads/generated/' . self::PID);
        @mkdir($dir, 0755, true);
        $img = imagecreatetruecolor(600, 600);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 107, 0));
        $webpOk = imagewebp($img, "$dir/test.webp", 85);
        imagedestroy($img);
        $imgUrl = site_url('uploads/generated/' . self::PID . '/test.webp');

        // 2. Produit actif complet (toutes les sections renseignées).
        Product::whereKey(self::PID)->delete();
        $p = new Product();
        $p->id = self::PID;
        $p->forceFill([
            'slug' => 'test-generation', 'status' => 'active', 'nom_produit' => 'Produit Test Génération',
            'prix' => 299, 'prix_barre' => 499, 'frais_livraison' => 0, 'livraison_gratuite' => true,
            'devise' => 'MAD', 'symbole_devise' => 'DH', 'position_symbole' => 'apres',
            'langue' => 'fr', 'direction' => 'ltr', 'pays_vente' => 'maroc', 'stock_quantite' => 25,
            'rating' => 4.7, 'reviews_count' => 132, 'badge_hero' => 'Best-seller',
            'texte_hero' => 'Un produit de test pour valider le rendu complet.',
            'urgency_actif' => true, 'urgency_text' => 'Offre limitée', 'urgency_sub' => 'Stock bientôt épuisé',
            'meta_title' => 'Produit Test', 'meta_description' => 'Test de génération',
            'sections_order' => ['hero', 'description', 'guarantee_bar', 'features_grid', 'size_selector', 'gallery', 'stats_bar', 'testimonials', 'comparison_table', 'order_form', 'faq'],
            'description_html' => '<p>Une <strong>description</strong> riche en HTML.</p>',
            'attrs_json' => ['type_produit' => 'chaussure', 'groupes' => [
                ['id' => 'taille', 'label' => 'Taille', 'type' => 'pills', 'valeurs' => ['40', '41', '42', '43'], 'required' => true],
                ['id' => 'couleur', 'label' => 'Couleur', 'type' => 'color_pills', 'valeurs' => ['noir', 'blanc'], 'required' => false],
            ]],
            'features' => [['emoji' => '🚚', 'titre' => 'Livraison rapide', 'texte' => '2-4 jours'], ['emoji' => '🛡️', 'titre' => 'Garantie', 'texte' => '30 jours']],
            'stats' => [['val' => '1200+', 'label' => 'Clients'], ['val' => '98%', 'label' => 'Satisfaits']],
            'testimonials' => [['prenom' => 'Karim', 'ville' => 'Casablanca', 'note' => 5, 'texte' => 'Service rapide.'], ['prenom' => 'Sara', 'ville' => 'Rabat', 'note' => 4, 'texte' => 'Bien emballé.']],
            'faqs' => [['q' => 'Délai de livraison ?', 'a' => '2 à 4 jours.'], ['q' => 'Paiement ?', 'a' => 'À la livraison.']],
            'comparison_json' => [['feature' => 'Livraison gratuite', 'nous' => true, 'concurrent' => false], ['feature' => 'Paiement à la livraison', 'nous' => true, 'concurrent' => false]],
            'preuve_sociale_json' => [],
        ]);
        $p->save();

        // 2 images (pour que la galerie s'affiche).
        ProductImage::where('product_id', self::PID)->delete();
        foreach ([0, 1] as $pos) {
            ProductImage::create(['product_id' => self::PID, 'url_originale' => $imgUrl, 'url_webp' => $imgUrl, 'alt_text' => 'Test', 'position' => $pos, 'statut' => 1]);
        }

        // 3. Page statique avec TOUS les types de blocs.
        StaticPage::where('slug', 'test-page-blocks')->delete();
        StaticPage::create([
            'slug' => 'test-page-blocks', 'titre' => 'Page Test Blocs', 'type' => 'about', 'langue' => 'fr', 'direction' => 'ltr', 'status' => 'active',
            'blocks_json' => [
                ['type' => 'hero_banner', 'titre' => 'Bannière', 'sous_titre' => 'Sous-titre'],
                ['type' => 'text_block', 'titre' => 'Texte', 'contenu' => "Para 1.\n\nPara 2."],
                ['type' => 'values_block', 'titre' => 'Valeurs', 'valeurs' => [['emoji' => '🎯', 'titre' => 'V1', 'texte' => 'D1'], ['emoji' => '❤️', 'titre' => 'V2', 'texte' => 'D2']]],
                ['type' => 'stats_block', 'stats' => [['valeur' => '1000+', 'label' => 'Clients'], ['valeur' => '48h', 'label' => 'Livraison']]],
                ['type' => 'faq_block', 'titre' => 'FAQ', 'items' => [['q' => 'Q1', 'a' => 'R1']]],
                ['type' => 'contact_block', 'titre' => 'Contact', 'message' => 'Écrivez-nous', 'email' => 'test@x.com', 'tel' => '0600', 'whatsapp' => '212600', 'horaires' => '9h-18h'],
            ],
            'seo_json' => ['title' => 'Test Blocs', 'description' => 'Tous les blocs'],
        ]);

        $this->line('WebP généré : ' . ($webpOk ? 'OK' : 'ÉCHEC'));
        $this->line('Produit  → /pages/test-generation/');
        $this->line('Page     → /pages/test-page-blocks/');
        $this->info('Prêt. Rendez les 2 URLs puis lancez --clean.');

        return self::SUCCESS;
    }
}
