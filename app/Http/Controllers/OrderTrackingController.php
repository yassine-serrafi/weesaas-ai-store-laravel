<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\SettingsRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Suivi de commande par référence (port de suivi.php).
 */
class OrderTrackingController extends Controller
{
    public function show(Request $request, SettingsRepository $settings): View
    {
        $ref = htmlspecialchars(trim((string) $request->query('ref')), ENT_QUOTES, 'UTF-8');
        $order = null;
        $error = '';

        if ($ref) {
            $order = Order::with('product')->where('reference', $ref)->first();
            if (! $order) {
                $error = 'not_found';
            }
        }

        $shop = $settings->all();
        $langueSrc = $order ? ($order->product->langue ?? 'fr') : ($shop['langue_defaut'] ?? 'fr');
        $lang = resolveShopLang($langueSrc);

        $titres = ['ar' => 'تتبع طلبي', 'en' => 'Order Tracking', 'fr' => 'Suivi commande'];

        return view('suivi', [
            'shop'       => $shop,
            'order'      => $order,
            'ref'        => $ref,
            'error'      => $error,
            'lang_code'  => $lang['code'],
            'lang_dir'   => $lang['dir'],
            'page_title' => ($titres[$lang['code']] ?? 'Suivi commande') . ' — ' . ($shop['nom_boutique'] ?? ''),
            'page_url'   => site_url('suivi'),
            'robots'     => $order ? 'noindex,nofollow' : 'index,follow',
        ]);
    }
}
