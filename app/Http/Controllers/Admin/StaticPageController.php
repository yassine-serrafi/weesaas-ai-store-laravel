<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\StaticPage;
use App\Services\AI\OpenAiService;
use App\Services\GenerationLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pages institutionnelles (port de weeadmin/pages.php + creer-page.php).
 * Génération du contenu par IA (OpenAI), avec repli si l'IA est indisponible.
 */
class StaticPageController extends Controller
{
    public const TYPES = [
        'about'     => ['emoji' => '👥', 'label' => 'Qui sommes-nous'],
        'contact'   => ['emoji' => '📩', 'label' => 'Contact'],
        'cgv'       => ['emoji' => '📋', 'label' => 'CGV'],
        'faq'       => ['emoji' => '❓', 'label' => 'FAQ'],
        'livraison' => ['emoji' => '🚚', 'label' => 'Livraison'],
        'retour'    => ['emoji' => '🔄', 'label' => 'Retours'],
        'mentions'  => ['emoji' => '⚖️', 'label' => 'Mentions légales'],
        'custom'    => ['emoji' => '📄', 'label' => 'Page libre'],
    ];

    public function index(): View
    {
        $pages = StaticPage::orderBy('ordre_affichage')->orderByDesc('created_at')->get();
        return view('admin.pages.index', ['pages' => $pages, 'types' => self::TYPES]);
    }

    public function create(): View
    {
        return view('admin.pages.create', ['types' => self::TYPES]);
    }

    public function store(Request $request, OpenAiService $openai): RedirectResponse
    {
        $data = $request->validate([
            'type'                => ['required', 'string', 'max:50'],
            'titre'               => ['nullable', 'string', 'max:500'],
            'langue'              => ['required', 'string', 'max:20'],
            'instructions_libres' => ['nullable', 'string', 'max:2000'],
            'show_in_header_menu' => ['nullable', 'boolean'],
            'show_in_footer_menu' => ['nullable', 'boolean'],
            'status'              => ['required', 'in:draft,active'],
        ]);

        $langToPays = ['ar_marocain' => 'maroc', 'ar_standard' => 'maroc', 'ar_golfe' => 'saudi', 'fr' => 'france', 'en' => 'france'];
        $direction = in_array($data['langue'], ['ar_marocain', 'ar_standard', 'ar_golfe'], true) ? 'rtl' : 'ltr';

        $typeLabel = self::TYPES[$data['type']]['label'] ?? 'Page';
        $log = GenerationLogger::start('page', null, ($data['titre'] ?? '') ?: $typeLabel);
        $log->step(1, 'Génération de la page')->info('Génération démarrée', [
            'type'   => $data['type'],
            'langue' => $data['langue'],
            'instructions' => $data['instructions_libres'] ?? '',
        ]);

        // Génération du contenu par IA (repli si indisponible).
        $result = $openai->generateStaticPage([
            'type'                => $data['type'],
            'langue'              => $data['langue'],
            'pays'                => $langToPays[$data['langue']] ?? 'maroc',
            'instructions_libres' => $data['instructions_libres'] ?? '',
        ]);

        $label = $typeLabel;
        $titre = ($data['titre'] ?? '') ?: ($result['titre'] ?? $label);

        if (isset($result['error']) || empty($result['blocks'])) {
            // Repli : page minimale éditable même sans IA.
            $blocks = [
                ['type' => 'hero_banner', 'titre' => $titre, 'sous_titre' => ''],
                ['type' => 'text_block', 'titre' => $titre, 'contenu' => "Contenu à compléter.\n\n" . ($data['instructions_libres'] ?? '')],
            ];
            $seo = ['title' => $titre, 'description' => ''];
            $flash = isset($result['error'])
                ? "Page créée en repli (IA indisponible : {$result['error']})."
                : 'Page créée.';
            $log->warning('Repli : page minimale créée sans IA', [
                'raison' => $result['error'] ?? 'aucun bloc retourné',
            ]);
        } else {
            $blocks = $result['blocks'];
            $seo = ['title' => $result['seo_title'] ?? $titre, 'description' => $result['seo_description'] ?? ''];
            $flash = 'Page générée par IA et enregistrée.';
            $log->success('Contenu généré par OpenAI', [
                'modele'   => 'openai',
                'nb_blocs' => count($blocks),
                'blocs'    => array_map(fn ($b) => $b['type'] ?? '?', $blocks),
                'seo_title' => $seo['title'] ?? null,
            ]);
        }

        // Normalisation des blocs IA (rétablit la forme {"type":…} attendue par le front).
        $blocks = StaticPage::normalizeBlocks($blocks);

        // Slug unique.
        $slug = slugify($titre ?: $data['type'], $data['langue']) ?: 'page';
        $base = $slug;
        $c = 1;
        while (StaticPage::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $c++;
        }

        $page = StaticPage::create([
            'slug'                => $slug,
            'titre'               => $titre,
            'type'                => $data['type'],
            'langue'              => $data['langue'],
            'direction'           => $direction,
            'blocks_json'         => $blocks,
            'seo_json'            => $seo,
            'status'              => $data['status'],
            'show_in_header_menu' => (bool) ($data['show_in_header_menu'] ?? false),
            'show_in_footer_menu' => (bool) ($data['show_in_footer_menu'] ?? false),
        ]);

        $this->syncMenu($page);

        $log->refId = $page->id;
        $log->setLabel($titre)->step(2, 'Enregistrement')->success('✓ Page enregistrée', [
            'page_id' => $page->id,
            'slug'    => $page->slug,
            'status'  => $page->status,
        ]);

        return redirect()->route('admin.pages.index')->with('success', $flash);
    }

    public function toggleStatus(StaticPage $page): RedirectResponse
    {
        $page->status = $page->status === 'active' ? 'draft' : 'active';
        $page->save();
        return back()->with('success', "Page « {$page->titre} » → {$page->status}");
    }

    public function destroy(StaticPage $page): RedirectResponse
    {
        Menu::where('type', 'page')->where('target_id', $page->id)->delete();
        $page->delete();
        return back()->with('success', 'Page supprimée.');
    }

    /** Synchronise les entrées de menu header/footer pour cette page. */
    private function syncMenu(StaticPage $page): void
    {
        Menu::where('type', 'page')->where('target_id', $page->id)->delete();

        $url = 'pages/' . $page->slug . '/';
        foreach (['header' => $page->show_in_header_menu, 'footer' => $page->show_in_footer_menu] as $position => $show) {
            if ($show) {
                Menu::create([
                    'label_fr'  => $page->titre,
                    'label_ar'  => $page->titre,
                    'label_en'  => $page->titre,
                    'url'       => $url,
                    'type'      => 'page',
                    'target_id' => $page->id,
                    'position'  => $position,
                    'statut'    => 1,
                ]);
            }
        }
    }
}
