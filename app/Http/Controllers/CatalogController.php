<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\SettingsRepository;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Catalogue boutique (port de index.php).
 * La langue/direction du catalogue suit le paramètre boutique `langue_defaut`.
 */
class CatalogController extends Controller
{
    public function index(Request $request, SettingsRepository $settings, TrackingService $tracker): View
    {
        $tracker->recordView(null, 'catalog');

        $shop = $settings->all();
        $lang = resolveShopLang($shop['langue_defaut'] ?? config('weesaas.default_lang'));

        $pays   = trim((string) $request->query('pays', $shop['pays_defaut'] ?? ''));
        $search = trim((string) $request->query('q', ''));

        $query = Product::query()
            ->active()
            ->with('mainImage')
            ->when($pays !== '', fn ($q) => $q->where('pays_vente', $pays))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(fn ($w) => $w
                    ->where('nom_produit', 'like', "%{$search}%")
                    ->orWhere('meta_description', 'like', "%{$search}%"));
            })
            ->orderByDesc('created_at');

        $products = $query->paginate(12)->withQueryString();

        $labels = $this->labels($lang['code']);

        // Métadonnées de l'accueil : le titre = nom de la boutique (PAS « Catalogue »).
        $nom = $shop['nom_boutique'] ?? 'Boutique';
        $taglines = [
            'fr' => 'Boutique en ligne · Livraison rapide · Paiement à la livraison',
            'ar' => 'متجر إلكتروني · توصيل سريع · الدفع عند الاستلام',
            'en' => 'Online store · Fast delivery · Cash on delivery',
        ];
        $metaDesc = ($shop['description_boutique'] ?? '') ?: ($nom . ' — ' . ($taglines[$lang['code']] ?? $taglines['fr']));

        return view('catalog', [
            'shop'       => $shop,
            'lang_code'  => $lang['code'],
            'lang_dir'   => $lang['dir'],
            'products'   => $products,
            'pays'       => $pays,
            'search'     => $search,
            'L'          => $labels,
            'page_title' => $nom,
            'page_desc'  => $metaDesc,
            'og_image'   => $shop['logo_url'] ?? site_url('assets/img/og-default.jpg'),
            'promo_bar'  => $shop['promo_bar_text'] ?? '',
        ]);
    }

    private function labels(string $code): array
    {
        $all = [
            'fr' => ['titre' => 'Catalogue', 'rechercher' => 'Rechercher…', 'btn_search' => 'Rechercher', 'tout' => 'Tout afficher', 'vide' => 'Aucun produit disponible pour le moment.', 'epuise' => 'Épuisé'],
            'ar' => ['titre' => 'المتجر', 'rechercher' => 'ابحث عن منتج…', 'btn_search' => 'بحث', 'tout' => 'عرض الكل', 'vide' => 'لا توجد منتجات متاحة حالياً.', 'epuise' => 'نفذ المخزون'],
            'en' => ['titre' => 'Catalogue', 'rechercher' => 'Search a product…', 'btn_search' => 'Search', 'tout' => 'Show all', 'vide' => 'No products available at the moment.', 'epuise' => 'Out of stock'],
        ];
        return $all[$code] ?? $all['fr'];
    }
}
