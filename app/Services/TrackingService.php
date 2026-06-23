<?php

namespace App\Services;

use App\Models\IpCache;
use App\Models\PageView;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Suivi d'audience (port de includes/tracker_helper.php + helpers analytics).
 *
 * Enregistre une vue de page (catalogue ou produit) avec dédup 30 min par
 * session, géolocalisation IP (cache 24h), device/navigateur/OS, UTM et source.
 */
class TrackingService
{
    public function __construct(private Request $request) {}

    /** Enregistre une vue de page (idempotent sur 30 min / session). */
    public function recordView(?int $productId, string $pageType = 'product'): void
    {
        try {
            $ip = $this->realIp();
            $ua = (string) $this->request->userAgent();
            $sessionId = $this->sessionId($ip, $ua);

            $exists = PageView::where('session_id', $sessionId)
                ->where(fn ($q) => $q->where('product_id', $productId)->orWhereNull('product_id'))
                ->where('created_at', '>', Carbon::now()->subMinutes(30))
                ->exists();

            if ($exists) {
                return;
            }

            $geo = $this->geoIp($ip);
            $utm = $this->persistUtm();

            PageView::create([
                'session_id'   => $sessionId,
                'product_id'   => $productId,
                'page_type'    => $pageType,
                'country_code' => $geo['countryCode'],
                'country_name' => $geo['country'],
                'city'         => $geo['city'],
                'region'       => $geo['region'],
                'device_type'  => $this->deviceType(),
                'browser'      => $this->browser(),
                'os'           => $this->os(),
                'referrer'     => (string) $this->request->headers->get('referer', ''),
                'source'       => $this->detectSource(),
                'utm_source'   => $utm['utm_source'],
                'utm_medium'   => $utm['utm_medium'],
                'utm_campaign' => $utm['utm_campaign'],
                'ip_address'   => $ip,
            ]);

            if ($productId !== null) {
                Product::whereKey($productId)->increment('vues_total');

                $unique = PageView::where('session_id', $sessionId)
                    ->where('product_id', $productId)->count();
                if ($unique === 1) {
                    Product::whereKey($productId)->increment('vues_uniques');
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /* ───────────────── Détection requête ───────────────── */

    public function realIp(): string
    {
        foreach (['CF-Connecting-IP', 'X-Forwarded-For', 'X-Real-IP'] as $header) {
            $value = $this->request->headers->get($header);
            if ($value) {
                $ip = trim(explode(',', $value)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $this->request->ip() ?: '0.0.0.0';
    }

    public function sessionId(string $ip, string $ua): string
    {
        return hash('sha256', $ip . $ua . date('Y-m-d'));
    }

    public function deviceType(): string
    {
        $ua = (string) $this->request->userAgent();
        if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) {
            return preg_match('/iPad/i', $ua) ? 'tablet' : 'mobile';
        }
        return 'desktop';
    }

    public function browser(): string
    {
        $ua = (string) $this->request->userAgent();
        return match (true) {
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Edge')    => 'Edge',
            str_contains($ua, 'Chrome')  => 'Chrome',
            str_contains($ua, 'Safari')  => 'Safari',
            str_contains($ua, 'Opera')   => 'Opera',
            default                      => 'Autre',
        };
    }

    public function os(): string
    {
        $ua = (string) $this->request->userAgent();
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS')  => 'macOS',
            str_contains($ua, 'iPhone')  => 'iOS',
            str_contains($ua, 'iPad')    => 'iPadOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux')   => 'Linux',
            default                      => 'Autre',
        };
    }

    public function detectSource(): string
    {
        if ($utm = $this->request->query('utm_source')) {
            return (string) $utm;
        }
        $ref = (string) $this->request->headers->get('referer', '');
        if ($ref === '') {
            return 'direct';
        }
        foreach (['facebook', 'tiktok', 'instagram', 'google', 'whatsapp', 'youtube', 'snapchat'] as $src) {
            if (str_contains($ref, $src)) {
                return $src;
            }
        }
        return 'autre';
    }

    /** Persiste les UTM en session (première touche). */
    private function persistUtm(): array
    {
        $keys = ['utm_source', 'utm_medium', 'utm_campaign'];
        $out = [];
        foreach ($keys as $k) {
            if ($this->request->query('utm_source') !== null) {
                session([$k => (string) $this->request->query($k, '')]);
            }
            $out[$k] = (string) ($this->request->query($k) ?? session($k, ''));
        }
        return $out;
    }

    /* ───────────────── Géolocalisation IP (cache 24h) ───────────────── */

    public function geoIp(string $ip): array
    {
        if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'], true)) {
            return ['country' => 'Local', 'city' => 'Local', 'region' => 'Local', 'countryCode' => 'XX'];
        }

        $cached = IpCache::where('ip', $ip)
            ->where('created_at', '>', Carbon::now()->subDay())
            ->first();
        if ($cached) {
            return [
                'country' => $cached->country, 'city' => $cached->city,
                'region' => $cached->region, 'countryCode' => $cached->country_code,
            ];
        }

        try {
            $resp = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,countryCode,regionName,city',
                'lang'   => 'fr',
            ])->json();

            if (($resp['status'] ?? '') === 'success') {
                $geo = [
                    'country'     => $resp['country'] ?? '',
                    'city'        => $resp['city'] ?? '',
                    'region'      => $resp['regionName'] ?? '',
                    'countryCode' => $resp['countryCode'] ?? '',
                ];
                DB::table('ip_cache')->updateOrInsert(
                    ['ip' => $ip],
                    [
                        'country'      => $geo['country'],
                        'city'         => $geo['city'],
                        'region'       => $geo['region'],
                        'country_code' => $geo['countryCode'],
                        'created_at'   => now(),
                    ]
                );
                return $geo;
            }
        } catch (Throwable) {
            // réseau indisponible : on renvoie vide sans bloquer la vue
        }

        return ['country' => '', 'city' => '', 'region' => '', 'countryCode' => ''];
    }
}
