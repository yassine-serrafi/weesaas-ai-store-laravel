<?php

namespace App\Jobs;

use App\Models\AiJob;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AI\GeminiService;
use App\Services\AI\OpenAiService;
use App\Services\GenerationLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Génération d'une page produit par IA (port de jobs/worker.php).
 *
 * Remplace le worker HTTP+token : ici c'est un Job Laravel en file d'attente.
 * Étapes : analyse image (Gemini) → visuels (Gemini) → copywriting (OpenAI)
 * → attributs → écriture des colonnes produit (status active).
 * Plus de fichier généré sur disque : la page est servie dynamiquement.
 */
class GenerateProductPage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1800; // 30 min : la génération d'images peut être longue.

    private ?GenerationLogger $log = null;

    public function __construct(public int $aiJobId, public int $productId) {}

    public function handle(GeminiService $gemini, OpenAiService $openai): void
    {
        $job = AiJob::find($this->aiJobId);
        $product = Product::find($this->productId);
        if (! $job || ! $product) {
            return;
        }

        $params = $job->params_json ?: [];
        $job->update(['status' => 'running', 'started_at' => now()]);

        $this->log = GenerationLogger::start('product', $this->productId, $product->nom_produit ?: 'Produit en cours');
        $this->log->info('Génération démarrée', [
            'ai_job_id' => $this->aiJobId,
            'langue'    => $params['langue'] ?? null,
            'pays'      => $params['pays_vente'] ?? null,
            'style'     => $params['style_page'] ?? null,
            'nb_images' => (int) ($params['nb_images'] ?? 3),
            'mode_rapide' => ! empty($params['mode_rapide']),
        ]);

        try {
            $imagePath    = $params['image_path'] ?? '';
            $nbImages     = (int) ($params['nb_images'] ?? 3);
            $langue       = $params['langue'] ?? 'ar_marocain';
            $pays         = $params['pays_vente'] ?? 'maroc';
            $style        = $params['style_page'] ?? 'moderne';
            $instructions = $params['instructions_libres'] ?? '';
            $disableColors = ! empty($params['disable_colors']);
            $modeRapide   = ! empty($params['mode_rapide']);

            // STEP 1 — Analyse image
            $this->step($job, 1, 10, "Analyse de l'image en cours...");
            if (! is_file($imagePath)) {
                $this->fail($job, $product, 'Image introuvable: ' . $imagePath);
                return;
            }
            $analysis = $gemini->analyzeImage($imagePath);
            if (isset($analysis['error'])) {
                $this->fail($job, $product, 'Erreur analyse image: ' . $analysis['error']);
                return;
            }
            $analysis['benefices_str'] = implode(', ', (array) ($analysis['benefices'] ?? []));
            $this->log->success('Image analysée par Gemini', [
                'modele'      => 'gemini',
                'type_produit' => $analysis['type_produit'] ?? null,
                'nom_detecte' => $analysis['nom_produit'] ?? null,
                'benefices'   => $analysis['benefices'] ?? [],
            ]);

            // Mémorise le type de produit (réutilisé pour régénérer une image depuis l'éditeur).
            $job->update(['result_data' => array_merge((array) $job->result_data, [
                'type_produit' => $analysis['type_produit'] ?? 'autre',
            ])]);

            // STEP 2 — Visuels lifestyle
            $images = [];
            if (! $modeRapide) {
                $this->step($job, 2, 25, 'Génération des visuels lifestyle...');
                $promptImages = $analysis['prompt_images'] ?? 'professional product photography, high quality';
                for ($i = 0; $i < $nbImages; $i++) {
                    $res = $gemini->generateLifestyleImage($imagePath, $promptImages, $style, $i, $analysis['type_produit'] ?? 'autre');
                    if (! isset($res['error'])) {
                        $path = $gemini->saveGeneratedImage($res['data'], $res['mimeType'], $this->productId, $i + 1, true);
                        if ($path) {
                            $images[] = $path;
                            $this->log->success('Visuel ' . ($i + 1) . "/{$nbImages} généré", [
                                'path'  => $path,
                                'model' => $res['model'] ?? null,
                            ]);
                        } else {
                            $this->log->warning('Visuel ' . ($i + 1) . " : échec de sauvegarde");
                        }
                    } else {
                        $this->log->warning('Visuel ' . ($i + 1) . " non généré", [
                            'error' => $res['error'],
                            'model' => $res['model'] ?? null,
                        ]);
                    }
                    $job->update(['progress_pct' => 25 + (int) (($i + 1) / max(1, $nbImages) * 25)]);
                }
            } else {
                $this->log->info('Mode rapide : génération des visuels ignorée');
            }

            // Peupler product_images (à partir des visuels générés, sinon image originale).
            $this->populateImages($product, $images, $imagePath, $analysis['nom_produit'] ?? '');
            $this->log->info(count($images) . ' visuel(s) retenu(s)', [
                'source' => $images ? 'générés' : 'image originale (fallback)',
            ]);

            // STEP 3 — Copywriting
            $this->step($job, 3, 55, 'Génération du contenu marketing...');
            $content = $openai->generateContent($analysis, [
                'langue'             => $langue,
                'variante_arabe'     => $params['variante_arabe'] ?? '',
                'pays_vente'         => $pays,
                'pays_cible'         => $params['pays_cible'] ?? $pays,
                'devise'             => $params['devise'] ?? 'MAD',
                'symbole_devise'     => $params['symbole_devise'] ?? 'درهم',
                'position_symbole'   => $params['position_symbole'] ?? 'apres',
                'prix'               => (float) ($params['prix'] ?? 0),
                'prix_barre'         => (float) ($params['prix_barre'] ?? 0),
                'frais_livraison'    => (float) ($params['frais_livraison'] ?? 0),
                'livraison_delai'    => $params['livraison_delai'] ?? getDelaiLivraison($pays),
                'garantie_jours'     => (int) ($params['garantie_jours'] ?? 30),
                'nom_manuel'         => $params['nom_produit'] ?? '',
                'cat_manuelle'       => $params['categorie'] ?? '',
                'instructions_libres' => $instructions,
                'description_ia_input' => $params['description_ia_input'] ?? '',
                'disable_colors'     => $disableColors,
            ]);
            if (isset($content['error'])) {
                $this->fail($job, $product, 'Erreur copywriting: ' . $content['error']);
                return;
            }
            $this->log->success('Contenu marketing généré par OpenAI', [
                'modele'   => 'openai',
                'sections' => array_keys($content['sections'] ?? []),
                'nb_temoignages' => count($content['temoignages'] ?? []),
                'nb_faq'   => count($content['faq'] ?? []),
                'nom_produit' => $content['nom_produit'] ?? null,
            ]);

            // STEP 4 — Attributs + normalisation
            $this->step($job, 4, 75, 'Définition des attributs produit...');
            $attrs = $content['attributes'] ?? $openai->generateAttributes($analysis, $langue, $pays);
            if ($disableColors && isset($attrs['groupes'])) {
                $attrs['groupes'] = array_values(array_filter($attrs['groupes'], fn ($g) => ($g['id'] ?? '') !== 'couleur'));
            }
            // Filet de sécurité : forcer les variantes selon les contraintes des instructions
            // (ex. « il reste que le 43 », « uniquement noir ») même si GPT les ignore.
            if (! empty($instructions)) {
                $attrs = $this->applyInstructionOverrides($attrs, $instructions);
                $this->log->info('Contraintes d\'instructions appliquées', ['instructions' => $instructions]);
            }
            $this->log->success('Attributs définis', [
                'groupes' => array_map(fn ($g) => $g['id'] ?? '?', $attrs['groupes'] ?? []),
            ]);

            $this->writeProduct($product, $analysis, $content, $attrs, $langue, $params);
            $finalName = $product->fresh()->nom_produit ?? '';
            $this->log->setLabel($finalName)->success('Produit écrit en base', [
                'nom_produit' => $finalName,
                'slug'        => $product->fresh()->slug ?? null,
            ]);

            // STEP 5 — Finalisation (page dynamique, pas de fichier disque)
            $this->step($job, 5, 100, 'Page générée avec succès ✓');
            $job->update(['status' => 'completed', 'finished_at' => now()]);
            $this->log->success('✓ Génération terminée avec succès');

            Notification::create([
                'type'    => 'produit',
                'titre'   => 'Nouveau produit généré',
                'message' => "Le produit « {$product->fresh()->nom_produit} » est maintenant actif.",
                'lien'    => route('admin.products.edit', $product->id),
            ]);
        } catch (Throwable $e) {
            $this->fail($job, $product, $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    private function step(AiJob $job, int $step, int $pct, string $label): void
    {
        $job->update(['status' => 'running', 'step_current' => $step, 'step_total' => 5, 'progress_pct' => $pct, 'step_label' => $label]);
    }

    private function fail(AiJob $job, Product $product, string $message): void
    {
        $job->update(['status' => 'failed', 'error_message' => $message, 'finished_at' => now()]);
        $product->update(['status' => 'draft']);
        $this->log?->error('✗ Échec de la génération', ['detail' => $message]);
        report(new \RuntimeException("[GenerateProductPage] $message"));
    }

    /**
     * Moteur universel d'enforcement des contraintes (port de worker.php).
     * Détecte tailles numériques, tailles vêtement, valeurs techniques ou couleurs
     * dans les instructions libres et force le groupe d'attribut correspondant.
     */
    private function applyInstructionOverrides(array $attrs, string $instructions): array
    {
        if (! preg_match('/\b(reste|uniquement|seulement|only|disponible|available|il y a|il reste|يتوفر|فقط|حصراً|المقاس|النوع)\b/iu', $instructions)) {
            return $attrs;
        }

        // 1. Tailles numériques (chaussures, jeans) : 30-52
        preg_match_all('/\b(3[0-9]|4[0-9]|5[0-2])\b/', $instructions, $m1);
        $numSizes = array_values(array_unique(array_filter($m1[1] ?? [])));

        // 2. Tailles vestimentaires : XS … XXXL
        preg_match_all('/\b(XXX?L|XX?L|XS|[SMLX]{1,2})\b/', $instructions, $m2);
        $clothSizes = array_values(array_unique(array_filter($m2[1] ?? [])));

        // 3. Couleurs (FR / EN / AR)
        $colorKws = ['noir', 'blanc', 'rouge', 'bleu', 'vert', 'jaune', 'rose', 'violet', 'orange', 'marron', 'gris', 'beige', 'olive', 'black', 'white', 'red', 'blue', 'green', 'yellow', 'pink', 'purple', 'brown', 'grey', 'gray', 'gold', 'silver', 'أسود', 'أبيض', 'أحمر', 'أزرق', 'أخضر', 'وردي', 'بني', 'رمادي', 'ذهبي', 'زيتي'];
        $foundColors = [];
        foreach ($colorKws as $c) {
            if (mb_stripos($instructions, $c) !== false) {
                $foundColors[] = mb_strtoupper(mb_substr($c, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($c, 1, null, 'UTF-8');
            }
        }

        // 4. Valeurs techniques : 20W, 100ml, 128Go, 1kg…
        preg_match_all('/\b(\d+(?:[,.]\d+)?)\s*(W|ml|cl|Go|GB|To|TB|g|kg|cm|mm)\b/ui', $instructions, $m4);
        $techValues = [];
        foreach (($m4[1] ?? []) as $k => $v) {
            $techValues[] = $v . ($m4[2][$k] ?? '');
        }
        $techValues = array_values(array_unique(array_filter($techValues)));

        // Priorité : taille numérique > taille vêtement > valeur technique > couleur
        if ($numSizes) {
            sort($numSizes, SORT_NUMERIC);
            $this->overrideGroup($attrs, 'taille', $numSizes);
        } elseif ($clothSizes) {
            $order = ['XS' => 0, 'S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4, 'XXL' => 5, 'XXXL' => 6];
            usort($clothSizes, fn ($a, $b) => ($order[$a] ?? 9) - ($order[$b] ?? 9));
            $this->overrideGroup($attrs, 'taille', $clothSizes);
        } elseif ($techValues) {
            $u = implode('', $techValues);
            $gid = preg_match('/Go|GB|To|TB/i', $u) ? 'stockage'
                 : (preg_match('/ml|cl/i', $u) ? 'volume'
                 : (preg_match('/kg|g\b/i', $u) ? 'poids' : 'variant'));
            $this->overrideGroup($attrs, $gid, $techValues);
        } elseif ($foundColors) {
            $this->overrideGroup($attrs, 'couleur', array_values(array_unique($foundColors)));
        }

        return $attrs;
    }

    private function overrideGroup(array &$attrs, string $groupId, array $vals): void
    {
        if (! $vals) {
            return;
        }
        if (isset($attrs['groupes'])) {
            foreach ($attrs['groupes'] as &$grp) {
                if (($grp['id'] ?? '') === $groupId) {
                    $grp['valeurs'] = array_values($vals);
                    break;
                }
            }
            unset($grp);
        }
        if (($attrs['type'] ?? '') === $groupId) {
            $attrs['valeurs'] = array_values($vals);
        }
    }

    private function populateImages(Product $product, array $generated, string $originalPath, string $alt): void
    {
        ProductImage::where('product_id', $product->id)->delete();

        if (! empty($generated)) {
            $product->update(['image_principale' => $generated[0]]);
            foreach ($generated as $idx => $path) {
                $url = site_url($path);
                ProductImage::create([
                    'product_id'    => $product->id,
                    'url_originale' => $url,
                    'url_webp'      => $url,
                    'alt_text'      => $alt,
                    'position'      => $idx,
                    'statut'        => 1,
                ]);
            }
            $product->update(['images_json' => array_map(fn ($p) => ['path' => $p, 'type' => 'generated'], $generated)]);
            return;
        }

        // Fallback : image originale uploadée.
        $rel = 'uploads/originals/' . basename($originalPath);
        $url = site_url($rel);
        ProductImage::create([
            'product_id'    => $product->id,
            'url_originale' => $url,
            'url_webp'      => $url,
            'alt_text'      => $alt,
            'position'      => 0,
            'statut'        => 1,
        ]);
        $product->update(['image_principale' => $rel]);
    }

    /**
     * Garde défensive : rétablit `sections` sous forme d'objet {type: {data}}.
     * Gère le cas où l'IA renvoie une liste [{type,…}] ou des valeurs en chaîne JSON
     * (même classe de dérive que pour les pages statiques).
     */
    private function normalizeAiSections(mixed $sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        // Décode toute valeur fournie sous forme de chaîne JSON.
        foreach ($sections as $k => $v) {
            if (is_string($v)) {
                $decoded = json_decode($v, true);
                $sections[$k] = is_array($decoded) ? $decoded : $v;
            }
        }

        // Liste séquentielle [{type:'hero',…}] → objet {hero:{…}}.
        if (array_is_list($sections)) {
            $keyed = [];
            foreach ($sections as $sec) {
                if (is_array($sec) && isset($sec['type'])) {
                    $type = $sec['type'];
                    $keyed[$type] = $sec['data'] ?? array_diff_key($sec, ['type' => 1, 'actif' => 1, 'ordre' => 1, 'data' => 1]);
                }
            }
            return $keyed ?: $sections;
        }

        return $sections;
    }

    /** Normalise le contenu IA vers les colonnes produit (port de la STEP 4 du worker). */
    private function writeProduct(Product $product, array $analysis, array $content, array $attrs, string $langue, array $params): void
    {
        $content['sections'] = $this->normalizeAiSections($content['sections'] ?? []);
        $sections = $content['sections'] ?? [];
        $hero = $sections['hero'] ?? [];
        $urgency = $sections['urgency_bar'] ?? [];

        $iconToEmoji = ['star'=>'⭐','shield'=>'🛡️','truck'=>'🚚','heart'=>'❤️','check'=>'✅','lock'=>'🔒','flash'=>'⚡','fire'=>'🔥','gift'=>'🎁','clock'=>'⏰','diamond'=>'💎','rocket'=>'🚀','leaf'=>'🌿','bolt'=>'⚡','medal'=>'🏅','crown'=>'👑','refresh'=>'🔄','phone'=>'📞','tag'=>'🏷️','box'=>'📦','smile'=>'😊','thumbsup'=>'👍','award'=>'🏆','zap'=>'⚡','sun'=>'☀️','drop'=>'💧','sparkle'=>'✨','magic'=>'✨'];

        $features = array_map(function ($f) use ($iconToEmoji) {
            $raw = $f['icone'] ?? $f['icon'] ?? $f['emoji'] ?? '⭐';
            $emoji = $iconToEmoji[strtolower(trim((string) $raw))] ?? (mb_strlen((string) $raw) <= 4 ? $raw : '⭐');
            return ['emoji' => $emoji, 'titre' => $f['titre'] ?? $f['title'] ?? '', 'texte' => $f['texte'] ?? $f['text'] ?? $f['desc'] ?? ''];
        }, $sections['features_grid']['features'] ?? []);

        $stats = array_map(fn ($s) => ['val' => $s['valeur'] ?? $s['val'] ?? $s['value'] ?? '', 'label' => $s['label'] ?? ''], $sections['stats_bar']['stats'] ?? []);

        $temoignages = array_map(fn ($t) => [
            'prenom' => $t['prenom'] ?? '', 'nom' => $t['nom'] ?? '', 'ville' => $t['ville'] ?? '',
            'note' => (int) ($t['note'] ?? 5), 'texte' => $t['texte'] ?? $t['commentaire'] ?? '',
            'attr' => $t['attribut'] ?? $t['attr'] ?? '', 'verifie' => true,
        ], $content['temoignages'] ?? []);

        $faq = array_map(fn ($f) => ['q' => $f['question'] ?? $f['q'] ?? '', 'a' => $f['reponse'] ?? $f['a'] ?? $f['r'] ?? ''], $content['faq'] ?? []);

        $comparison = array_map(fn ($p) => [
            'feature' => $p['point'] ?? $p['feature'] ?? $p['critere'] ?? '',
            'nous' => isset($p['nous']) ? (bool) $p['nous'] : true,
            'concurrent' => isset($p['eux']) ? (bool) $p['eux'] : false,
        ], $sections['comparison_table']['points'] ?? []);

        $seo = $content['seo'] ?? [];
        $nomProduit = $content['nom_produit'] ?? $analysis['nom_produit'] ?? 'Produit';

        // Slug unique.
        $slug = slugify($nomProduit, $langue);
        $base = $slug;
        $c = 1;
        while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
            $slug = $base . '-' . $c++;
        }

        $direction = in_array($langue, ['ar_marocain', 'ar_standard', 'ar_golfe', 'ar_mixte', 'ar_fr'], true) ? 'rtl' : 'ltr';

        $sectionsJson = ['sections' => []];
        $ordre = 0;
        foreach (['urgency_bar', 'guarantee_bar', 'gallery', 'features_grid', 'size_selector', 'testimonials', 'stats_bar', 'faq', 'order_form'] as $s) {
            if (isset($content['sections'][$s])) {
                $sectionsJson['sections'][] = ['type' => $s, 'actif' => true, 'ordre' => $ordre++, 'data' => $content['sections'][$s]];
            }
        }

        $product->update([
            'slug'                => $slug,
            'nom_produit'         => $nomProduit,
            'status'              => 'active',
            'direction'           => $direction,
            'texte_hero'          => $hero['sous_titre'] ?? $hero['description'] ?? '',
            'badge_hero'          => $hero['badge'] ?? '',
            'urgency_actif'       => ! empty($urgency['texte'] ?? $urgency['text'] ?? ''),
            'urgency_text'        => $urgency['texte'] ?? $urgency['text'] ?? '',
            'urgency_sub'         => $urgency['sous_texte'] ?? $urgency['sub_text'] ?? '',
            'features'            => $features,
            'stats'               => $stats,
            'testimonials'        => $temoignages,
            'temoignages_json'    => $temoignages,
            'faqs'                => $faq,
            'faq_json'            => $faq,
            'comparison_json'     => $comparison,
            'meta_title'          => $seo['title'] ?? $seo['meta_title'] ?? '',
            'meta_description'    => $seo['description'] ?? $seo['meta_description'] ?? '',
            'sections_order'      => ['hero', 'description', 'guarantee_bar', 'features_grid', 'size_selector', 'gallery', 'stats_bar', 'testimonials', 'comparison_table', 'order_form', 'faq'],
            'sections_json'       => $sectionsJson,
            'attrs_json'          => $attrs,
            'seo_json'            => $seo,
            'preuve_sociale_json' => $content['preuve_sociale'] ?? [],
            'historique_json'     => [['timestamp' => now()->toDateTimeString(), 'seo' => $seo]],
            'description_ia_input' => $params['description_ia_input'] ?? '',
            'description_html'    => $content['description_html'] ?? '',
            'description_ar'      => $content['description_ar'] ?? '',
            'page_generated_at'   => now(),
        ]);
    }
}
