<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\PageView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Collecte d'événements analytics (port de tracker.php).
 * Appelé via navigator.sendBeacon (corps JSON) → exclu de la protection CSRF.
 */
class TrackingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->json()->all() ?: $request->all();

        $eventName = preg_replace('/[^a-zA-Z_]/', '', $data['event'] ?? 'pageview');
        $productId = (int) ($data['product_id'] ?? 0) ?: null;
        $orderId   = (int) ($data['order_id'] ?? 0) ?: null;
        $sessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', substr((string) ($data['session_id'] ?? ''), 0, 64));
        $value     = (float) ($data['value'] ?? 0);
        $currency  = preg_replace('/[^A-Z]/', '', substr((string) ($data['currency'] ?? 'MAD'), 0, 3));
        $pageUrl   = substr((string) ($data['url'] ?? ''), 0, 500);
        $utmSource = substr((string) ($data['utm_source'] ?? ''), 0, 50);
        $ip        = $request->ip() ?? '';
        $ua        = substr((string) $request->userAgent(), 0, 255);

        try {
            Event::create([
                'event_name' => $eventName,
                'product_id' => $productId,
                'order_id'   => $orderId,
                'session_id' => $sessionId,
                'data'       => ['url' => $pageUrl, 'value' => $value, 'currency' => $currency, 'utm_source' => $utmSource],
                'ip'         => $ip,
                'user_agent' => $ua,
            ]);

            if ($eventName === 'pageview') {
                PageView::create([
                    'product_id' => $productId,
                    'session_id' => $sessionId,
                    'referrer'   => $pageUrl,
                    'ip_address' => $ip,
                    'utm_source' => $utmSource,
                ]);
            }

            // Heartbeat « présence live » : purge périodique des anciens pings
            // (1 chance sur 12) pour garder la table légère.
            if ($eventName === 'heartbeat' && random_int(1, 12) === 1) {
                Event::where('event_name', 'heartbeat')
                    ->where('created_at', '<', now()->subMinutes(10))
                    ->delete();
            }
        } catch (Throwable $e) {
            report($e);
            return response()->json(['ok' => false]);
        }

        return response()->json(['ok' => true]);
    }
}
