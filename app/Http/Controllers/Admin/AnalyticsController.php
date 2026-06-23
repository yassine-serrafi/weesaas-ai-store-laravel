<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\PageView;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Tableau de bord analytics (port de weeadmin/analytics.php).
 * Audience, conversion, funnel, devices et live, filtrables par produit / période.
 */
class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $productId = (int) $request->query('product_id', 0);
        $period    = in_array($request->query('period'), ['7', '30', '90'], true)
            ? (int) $request->query('period') : 30;
        $from = Carbon::now()->subDays($period - 1)->startOfDay();

        $product      = $productId ? Product::find($productId) : null;
        $productsList = Product::active()->orderBy('nom_produit')->get(['id', 'nom_produit']);

        // ── Filtres réutilisables ───────────────────────────────────────────
        $views = fn () => PageView::where('created_at', '>=', $from)
            ->when($productId, fn ($q) => $q->where('product_id', $productId));
        $orders = fn () => Order::where('created_at', '>=', $from)
            ->when($productId, fn ($q) => $q->where('product_id', $productId));

        // ── KPIs ────────────────────────────────────────────────────────────
        $vues        = $views()->count();
        $vuesUniques = $views()->distinct('session_id')->count('session_id');
        $commandes   = $orders()->count();
        $ca          = (float) $orders()->whereIn('statut', ['confirmee', 'expediee', 'livree'])->sum('total_ttc');
        $conversion  = $vuesUniques > 0 ? round($commandes / $vuesUniques * 100, 1) : 0;
        $panierMoyen = $commandes > 0 ? round($ca / max(1, $orders()->whereIn('statut', ['confirmee', 'expediee', 'livree'])->count())) : 0;

        // ── Visiteurs en direct (5 dernières minutes) ───────────────────────
        $live = $views()->where('created_at', '>=', Carbon::now()->subMinutes(5))
            ->distinct('session_id')->count('session_id');

        // ── Série quotidienne (visiteurs uniques + commandes) ───────────────
        $serie = [];
        for ($i = $period - 1; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->format('Y-m-d');
            $serie[] = [
                'jour'    => Carbon::parse($d)->format('d/m'),
                'vues'    => (clone $views())->whereDate('created_at', $d)->distinct('session_id')->count('session_id'),
                'cmd'     => (clone $orders())->whereDate('created_at', $d)->count(),
            ];
        }
        $chartLabels = array_column($serie, 'jour');
        $chartVues   = array_column($serie, 'vues');
        $chartCmd    = array_column($serie, 'cmd');

        // ── Top produits (vues + conversion) ────────────────────────────────
        $topProduits = PageView::selectRaw('product_id, COUNT(*) vues, COUNT(DISTINCT session_id) uniques')
            ->where('created_at', '>=', $from)
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->whereNotNull('product_id')
            ->groupBy('product_id')->orderByDesc('vues')->limit(8)->get()
            ->map(function ($row) use ($from) {
                $p = Product::find($row->product_id);
                $cmd = Order::where('product_id', $row->product_id)->where('created_at', '>=', $from)->count();
                return (object) [
                    'id'     => $row->product_id,
                    'nom'    => $p->nom_produit ?? '—',
                    'vues'   => $row->vues,
                    'uniques' => $row->uniques,
                    'cmd'    => $cmd,
                    'conv'   => $row->uniques > 0 ? round($cmd / $row->uniques * 100, 1) : 0,
                ];
            });

        // ── Pages les plus visitées (tout le site, par URL) ─────────────────
        $rawPages = $views()->selectRaw('referrer, COUNT(*) vues, COUNT(DISTINCT session_id) uniques')
            ->where('referrer', '!=', '')->whereNotNull('referrer')
            ->groupBy('referrer')->get();
        $pagesAgg = [];
        foreach ($rawPages as $r) {
            $path = parse_url($r->referrer, PHP_URL_PATH) ?: '/';
            $path = $path === '' ? '/' : (rtrim($path, '/') ?: '/');
            if (! isset($pagesAgg[$path])) {
                $pagesAgg[$path] = ['path' => $path, 'label' => $this->pageLabel($path), 'vues' => 0, 'uniques' => 0];
            }
            $pagesAgg[$path]['vues']    += $r->vues;
            $pagesAgg[$path]['uniques'] += $r->uniques;
        }
        $pages = collect($pagesAgg)->sortByDesc('vues')->take(12)->values()
            ->map(fn ($p) => (object) $p);
        $pagesMax = max(1, $pages->max('vues') ?? 1);

        // ── Sources & villes ────────────────────────────────────────────────
        $sources = $views()->selectRaw('source, COUNT(*) n')
            ->groupBy('source')->orderByDesc('n')->limit(6)->get();
        $villes = $orders()->selectRaw('ville, COUNT(*) n')->where('ville', '!=', '')
            ->groupBy('ville')->orderByDesc('n')->limit(6)->get();

        // ── Répartition device ──────────────────────────────────────────────
        $devRow = $views()->selectRaw(
            "SUM(CASE WHEN LOWER(device_type) IN ('mobile','tablet') THEN 1 ELSE 0 END) mobile,"
            . " SUM(CASE WHEN LOWER(device_type)='desktop' THEN 1 ELSE 0 END) desktop"
        )->first();
        $devMobile  = (int) ($devRow->mobile ?? 0);
        $devDesktop = (int) ($devRow->desktop ?? 0);
        $devTotal   = max(1, $devMobile + $devDesktop);
        $mobilePct  = (int) round($devMobile / $devTotal * 100);
        $desktopPct = 100 - $mobilePct;

        // ── Funnel réel (depuis events) ─────────────────────────────────────
        $evt = fn (string $name) => Schema::hasTable('events')
            ? DB::table('events')->where('event_name', $name)->where('created_at', '>=', $from)
                ->when($productId, fn ($q) => $q->where('product_id', $productId))
                ->distinct('session_id')->count('session_id')
            : 0;
        $funnel = [
            ['label' => 'Visiteurs',         'val' => $vuesUniques,       'color' => '#2563EB'],
            ['label' => 'Scroll formulaire', 'val' => $evt('scroll_to_form'),   'color' => '#7C3AED'],
            ['label' => 'Initiate Checkout', 'val' => $evt('initiate_checkout'), 'color' => '#FF6B00'],
            ['label' => 'Commandes',         'val' => $commandes,         'color' => '#16A34A'],
        ];
        $funnelMax = max(1, max(array_column($funnel, 'val')));

        return view('admin.analytics.index', compact(
            'product', 'productId', 'productsList', 'period',
            'vues', 'vuesUniques', 'commandes', 'ca', 'conversion', 'panierMoyen', 'live',
            'chartLabels', 'chartVues', 'chartCmd',
            'topProduits', 'pages', 'pagesMax', 'sources', 'villes',
            'mobilePct', 'desktopPct', 'funnel', 'funnelMax'
        ));
    }

    /**
     * Libellé lisible pour un chemin d'URL (accueil, suivi, page produit…).
     */
    private function pageLabel(string $path): string
    {
        if ($path === '/' || $path === '') {
            return 'Accueil (catalogue)';
        }
        if (str_starts_with($path, '/pages/')) {
            $slug = trim(substr($path, 7), '/');
            $p = \App\Models\Product::where('slug', $slug)->first(['nom_produit']);
            return $p ? 'Produit : ' . $p->nom_produit : 'Page : ' . $slug;
        }
        return match ($path) {
            '/suivi'  => 'Suivi de commande',
            '/merci'  => 'Remerciement',
            '/villes' => 'Villes (AJAX)',
            default   => $path,
        };
    }

    /**
     * Endpoint live (polling JSON) — visiteurs actifs + CA du jour.
     */
    public function live(Request $request): JsonResponse
    {
        $productId = (int) $request->query('product_id', 0);

        // Visiteurs actifs « live » : sessions ayant envoyé un heartbeat (front.js)
        // dans les 90 dernières secondes (heartbeat émis toutes les 45 s).
        $actifs = Event::where('event_name', 'heartbeat')
            ->where('created_at', '>=', Carbon::now()->subSeconds(90))
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->distinct('session_id')->count('session_id');

        $caToday = (float) Order::whereDate('created_at', Carbon::today())
            ->when($productId, fn ($q) => $q->where('product_id', $productId))
            ->whereIn('statut', ['confirmee', 'expediee', 'livree'])->sum('total_ttc');

        return response()->json([
            'actifs'     => $actifs,
            'ca_today'   => $caToday,
            'updated_at' => Carbon::now()->format('H:i:s'),
        ]);
    }
}
