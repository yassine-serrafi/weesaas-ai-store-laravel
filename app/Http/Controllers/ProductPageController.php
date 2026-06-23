<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StaticPage;
use App\Services\SettingsRepository;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page de vente produit (port de templates/product_page.php).
 *
 * Remplace l'ancien système de fichiers générés sur disque (pages/{slug}/index.php)
 * par une route dynamique. Les sections sont assemblées depuis sections_order,
 * en sautant sections_disabled, exactement comme l'ancien template.
 */
class ProductPageController extends Controller
{
    private const SECTIONS_DEFAUT = [
        'hero', 'guarantee_bar', 'features_grid', 'size_selector', 'gallery',
        'stats_bar', 'testimonials', 'comparison_table', 'order_form', 'faq',
    ];

    public function show(string $slug, Request $request, SettingsRepository $settings, TrackingService $tracker): View|Response
    {
        $product = Product::active()->where('slug', $slug)->first();

        if (! $product) {
            // Les pages statiques partagent le namespace /pages/{slug}/ avec les produits.
            $page = StaticPage::active()->where('slug', $slug)->first();
            if ($page) {
                return $this->showStaticPage($page, $settings, $tracker);
            }
            return response()->view('errors.product-not-found', [], 404);
        }

        $tracker->recordView((int) $product->id, 'product');

        $shop = $settings->all();
        $lang = resolveShopLang($product->langue ?? 'fr');

        $images = $product->images()->get();

        // Sections actives (ordre configurable) moins les sections désactivées.
        $order = $product->sections_order ?: self::SECTIONS_DEFAUT;
        $disabled = $product->sections_disabled ?: [];
        $sections = array_values(array_diff($order, $disabled));

        $ogImage = $images->first()
            ? ($images->first()->url ?: site_url('assets/img/og-default.jpg'))
            : site_url('assets/img/og-default.jpg');

        return view('product.show', [
            'product'       => $product,
            'shop'          => $shop,
            'images'        => $images,
            'sections'      => $sections,
            'lang_code'     => $lang['code'],
            'lang_dir'      => $lang['dir'],
            'SOCIAL_PROOFS' => $this->socialProofs($product, $lang['code']),
            'page_title'    => ($product->meta_title ?: $product->nom_produit) . ' — ' . ($shop['nom_boutique'] ?? ''),
            'page_desc'     => $product->meta_description ?: strip_tags((string) $product->texte_hero),
            'page_url'      => site_url('pages/' . $product->slug . '/'),
            'og_image'      => $ogImage,
            'promo_bar'     => $product->promo_bar_text ?? '',
        ]);
    }

    /** Rend une page statique (port de templates/static_page.php). */
    private function showStaticPage(StaticPage $page, SettingsRepository $settings, TrackingService $tracker): View
    {
        $tracker->recordView(null, 'static');

        $shop = $settings->all();
        $lang = resolveShopLang($page->langue ?? 'fr');
        $seo = $page->seo_json ?: [];

        return view('page.static', [
            'page'       => $page,
            'shop'       => $shop,
            'blocks'     => $page->blocks_json ?: [],
            'lang_code'  => $lang['code'],
            'lang_dir'   => $lang['dir'],
            'page_title' => $seo['title'] ?? $page->titre,
            'page_desc'  => $seo['description'] ?? '',
            'page_url'   => site_url('pages/' . $page->slug . '/'),
            'og_image'   => $shop['logo_url'] ?? '',
            'promo_bar'  => $shop['promo_bar_text'] ?? '',
        ]);
    }

    /** Génère les preuves sociales (depuis preuve_sociale_json ou pool localisé). */
    private function socialProofs(Product $product, string $code): array
    {
        $out = [];
        foreach ((array) $product->preuve_sociale_json as $ps) {
            $nom = is_array($ps) ? ($ps['prenom'] ?? '') : '';
            $msg = is_array($ps) ? ($ps['message'] ?? $ps['texte'] ?? '') : (string) $ps;
            if ($nom && $msg) {
                $out[] = '<strong>' . e($nom) . '</strong> ' . e($msg);
            } elseif ($msg) {
                $out[] = e($msg);
            }
        }

        if (! empty($out)) {
            return $out;
        }

        [$pool, $verbe] = match ($code) {
            'ar'    => [['كريمة', 'يوسف', 'فاطمة', 'أحمد', 'سارة', 'مهدي', 'نورة', 'حسن'], 'طلب للتو هذا المنتج'],
            'en'    => [['Karima', 'Youssef', 'Fatima', 'Ahmed', 'Sara', 'Mehdi', 'Noura', 'Hassan'], 'just ordered this product'],
            default => [['Karima', 'Youssef', 'Fatima', 'Ahmed', 'Sara', 'Mehdi', 'Noura', 'Hassan'], 'vient de commander ce produit'],
        };
        for ($i = 0; $i < 5; $i++) {
            $out[] = '<strong>' . $pool[array_rand($pool)] . '</strong> ' . $verbe;
        }
        return $out;
    }
}
