<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StaticPage;
use Illuminate\Http\Response;

/**
 * Sitemap XML dynamique (port de sitemap.php) — accueil + suivi + produits + pages statiques actives.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $products = Product::where('status', 'active')->orderByDesc('updated_at')->get(['slug', 'updated_at']);
        $pages = StaticPage::where('status', 'active')->get(['slug', 'updated_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $xml .= $this->url(site_url(), null, 'daily', '1.0');
        $xml .= $this->url(site_url('suivi'), null, 'monthly', '0.3');

        foreach ($products as $p) {
            $xml .= $this->url(site_url('pages/' . $p->slug . '/'), $p->updated_at?->format('Y-m-d'), 'weekly', '0.9');
        }
        foreach ($pages as $p) {
            $xml .= $this->url(site_url('pages/' . $p->slug . '/'), $p->updated_at?->format('Y-m-d'), 'monthly', '0.5');
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function url(string $loc, ?string $lastmod, string $freq, string $priority): string
    {
        $out = "  <url>\n    <loc>" . htmlspecialchars($loc) . "</loc>\n";
        if ($lastmod) {
            $out .= "    <lastmod>{$lastmod}</lastmod>\n";
        }
        $out .= "    <changefreq>{$freq}</changefreq>\n    <priority>{$priority}</priority>\n  </url>\n";
        return $out;
    }
}
