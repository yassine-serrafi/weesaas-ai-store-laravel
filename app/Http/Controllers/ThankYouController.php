<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page de remerciement après commande (port de merci.php).
 */
class ThankYouController extends Controller
{
    public function show(Request $request, SettingsRepository $settings): View|RedirectResponse
    {
        $ref = htmlspecialchars(trim((string) $request->query('ref')), ENT_QUOTES, 'UTF-8');
        if (! $ref) {
            return redirect('/');
        }

        $order = Order::with(['product.mainImage'])->where('reference', $ref)->first();
        if (! $order) {
            return redirect('/');
        }

        $lang = resolveShopLang($order->product->langue ?? 'fr');

        $upsells = Product::active()
            ->with('mainImage')
            ->where('pays_vente', $order->product->pays_effectif)
            ->where('id', '!=', $order->product_id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $shop = $settings->all();

        return view('merci', [
            'shop'       => $shop,
            'order'      => $order,
            'upsells'    => $upsells,
            'lang_code'  => $lang['code'],
            'lang_dir'   => $lang['dir'],
            'page_title' => ($lang['code'] === 'ar' ? 'شكراً لطلبك' : 'Merci pour votre commande') . ' — ' . ($shop['nom_boutique'] ?? ''),
            'robots'     => 'noindex,nofollow',
            'og_image'   => $order->product->mainImage?->url ?? '',
        ]);
    }
}
