<?php

namespace App\Services\AI;

use App\Services\ImageService;
use App\Services\SettingsRepository;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Service Google Gemini (port de includes/ai_gemini.php).
 * Analyse d'image (vision) + génération de visuels lifestyle.
 */
class GeminiService
{
    private const BASE = 'https://generativelanguage.googleapis.com/';

    public function __construct(
        private SettingsRepository $settings,
        private ImageService $images,
    ) {}

    private function key(): string
    {
        return trim($this->settings->get('gemini_api_key'));
    }

    /** Requête texte/multimodale (v1 ou v1beta). */
    private function request(string $model, array $payload, bool $beta = false): array
    {
        $key = $this->key();
        if ($key === '') {
            return ['error' => 'Clé API Gemini non configurée'];
        }

        $version = $beta ? 'v1beta' : 'v1';
        $url = self::BASE . "{$version}/models/{$model}:generateContent?key={$key}";

        return $this->postWithRetry($url, $payload, 120);
    }

    /** Requête de génération d'image (timeout long). */
    private function requestImage(string $model, array $payload): array
    {
        $key = $this->key();
        if ($key === '') {
            return ['error' => 'Clé API Gemini non configurée'];
        }

        // Les modèles image natifs sont documentés sur l'API v1beta.
        $url = self::BASE . "v1beta/models/{$model}:generateContent?key={$key}";

        return $this->postWithRetry($url, $payload, 300);
    }

    /**
     * POST avec réessai automatique et backoff exponentiel sur erreurs transitoires
     * (modèle surchargé « high demand » 503, 500/502/504, indisponibilité temporaire).
     */
    private function postWithRetry(string $url, array $payload, int $timeout, int $maxAttempts = 4): array
    {
        $delay = 2; // secondes : 2 → 4 → 8 …
        $lastError = 'Erreur Gemini inconnue';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $resp = Http::timeout($timeout)->withHeaders(['Content-Type' => 'application/json'])->post($url, $payload);
            } catch (Throwable $e) {
                $lastError = 'HTTP error: ' . $e->getMessage();
                if ($attempt < $maxAttempts) {
                    sleep($delay);
                    $delay *= 2;
                    continue;
                }
                return ['error' => $lastError];
            }

            if ($resp->successful()) {
                return $resp->json() ?: ['error' => 'Réponse Gemini vide'];
            }

            $status = $resp->status();
            $lastError = $resp->json('error.message') ?? ('Erreur Gemini ' . $status);

            if ($this->isTransient($status, $lastError) && $attempt < $maxAttempts) {
                sleep($delay);
                $delay *= 2;
                continue;
            }

            return ['error' => $lastError];
        }

        return ['error' => $lastError];
    }

    /** Détermine si une erreur Gemini vaut la peine d'être réessayée. */
    private function isTransient(int $status, string $message): bool
    {
        $m = strtolower($message);

        // Quota dur (palier gratuit, limit: 0) : réessayer ne sert à rien.
        if (str_contains($m, 'quota') && str_contains($m, 'limit: 0')) {
            return false;
        }

        if (in_array($status, [500, 502, 503, 504], true)) {
            return true;
        }

        foreach (['high demand', 'overloaded', 'try again', 'temporarily', 'unavailable', 'is busy', 'please retry'] as $kw) {
            if (str_contains($m, $kw)) {
                return true;
            }
        }

        return false;
    }

    /** Analyse le produit (Gemini Vision) → tableau structuré. */
    public function analyzeImage(string $imagePath): array
    {
        if (! is_file($imagePath)) {
            return ['error' => 'Fichier image introuvable : ' . $imagePath];
        }

        $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
        $imageData = base64_encode((string) file_get_contents($imagePath));

        $prompt = <<<PROMPT
Analyze this product image and return a JSON object with these exact fields:
{
  "nom_produit": "GIVE THE EXACT BRAND AND MODEL (e.g. 'Apple iPhone 15 Pro 256GB', 'Nike Air Jordan 1 Low'). Be as precise as possible. Keep brand and model names in English.",
  "type_produit": "Category: chaussure/vetement/electronique/meuble/accessoire/cosmetique/sport/alimentation/autre",
  "type_marque": "internationale or generique",
  "marque": "EXACT BRAND NAME (e.g. 'Nike', 'Samsung'). Empty if generic.",
  "attributs_detectes": ["list", "of", "detected", "variants"],
  "type_attribut": "taille/couleur/stockage/dimensions/matiere/autre",
  "description": "EXTREMELY LITERAL product description. Only mention what is CLEARLY VISIBLE (color, texture, material, branding). Do not hallucinate features.",
  "benefices": ["benefit1", "benefit2", "benefit3"],
  "public_cible": "Target audience description",
  "style_visuel": "Visual style: moderne/luxe/sport/casual/minimaliste",
  "couleurs_detectees": ["color1", "color2"],
  "prompt_images": "Detailed prompt in English for generating lifestyle photos of this specific product model"
}
Return ONLY the JSON, no markdown, no explanation.
PROMPT;

        $result = $this->request('gemini-2.5-flash', [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inlineData' => ['mimeType' => $mimeType, 'data' => $imageData]],
                ],
            ]],
            'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 2048],
        ], true);

        if (isset($result['error'])) {
            return $result;
        }

        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ($text === '') {
            return ['error' => 'Réponse Gemini vide (pas de texte)', 'raw' => json_encode($result)];
        }

        return $this->parseJson($text) ?? ['error' => 'Impossible de parser la réponse Gemini', 'raw' => $text];
    }

    /** Génère une image lifestyle (fallback multi-modèles). */
    public function generateLifestyleImage(string $imagePath, string $basePrompt, string $style, int $index, string $typeProduit = 'autre'): array
    {
        $imgData = @file_get_contents($imagePath);
        if (! $imgData) {
            return ['error' => "Impossible de lire l'image originale"];
        }
        $base64 = base64_encode($imgData);
        $mimeType = match (true) {
            str_ends_with($imagePath, '.png')  => 'image/png',
            str_ends_with($imagePath, '.jpg'), str_ends_with($imagePath, '.jpeg') => 'image/jpeg',
            default => 'image/webp',
        };

        // ── Bibliothèque de DÉCORS DISTINCTS par type de produit ──────────────
        // Chaque décor est une scène riche, spécifique et radicalement différente
        // (pas un simple « studio backdrop »). On en assigne un différent par image
        // pour obtenir des photos variées, sans rapport avec le fond d'origine.
        $sceneLibrary = [
            'chaussure' => [
                'decors' => [
                    'centered on a clean seamless studio background with a soft white-to-light-grey gradient, a gentle realistic drop shadow beneath it, bright even softbox lighting, crisp and sharp — premium e-commerce product page look, no props, no dramatic spotlight',
                    'standing on a raw concrete plinth inside a bright minimalist art gallery, soft daylight from a large window, long soft shadow on a pale floor, editorial fashion vibe',
                    'placed on wet glossy asphalt of a neon-lit city street at night, colorful bokeh light reflections, cinematic moody atmosphere',
                    'frozen in mid-air surrounded by a dynamic swirl of fine colored powder and floating particles bursting outward, dramatic dark studio background, high-speed energy',
                    'resting on smooth golden desert sand dunes at sunset, warm directional light and a vast minimal sky background, premium outdoor campaign',
                ],
                'lifestyle' => 'worn by a stylish model walking on a modern urban city street, shot tightly framed on the lower legs and feet in motion so the shoes are large and prominent in the foreground, shallow depth of field, authentic streetwear editorial',
            ],
            'electronique' => [
                'decors' => [
                    'centered on a clean seamless white-to-light-grey gradient studio background with a soft subtle reflection and gentle shadow, bright even professional lighting, crisp and sharp — premium e-commerce product page look, no props',
                    'on a clean white marble desk beside a warm coffee cup and a small plant, soft morning window light, cozy modern lifestyle vibe',
                    'in a sleek high-tech environment with subtle holographic blue light streaks across a dark gradient background, cinematic tech reveal',
                    'surrounded by softly floating geometric shapes on a smooth pastel gradient backdrop, clean modern editorial composition',
                    'on a dark slate surface with dramatic side lighting and a faint smoke wisp, premium gadget hero shot',
                ],
                'lifestyle' => 'used naturally in a person\'s hands in a bright modern home office, shallow depth of field, authentic everyday scene',
            ],
            'meuble' => [
                'decors' => [
                    'on a clean seamless light neutral studio background (soft white to light grey) with a soft natural shadow, bright even lighting, crisp — premium furniture e-commerce catalog look, no props',
                    'on a polished concrete floor inside a minimalist loft with floor-to-ceiling windows, dramatic daylight and long architectural shadows',
                    'against a bold colored studio wall under a single dramatic spotlight, design-magazine cover aesthetic',
                    'in a warm boho interior with terracotta tones, woven rugs and ambient golden light, lifestyle decor scene',
                ],
                'lifestyle' => 'placed in a beautifully decorated modern home with a person relaxing nearby, warm inviting atmosphere, natural light',
            ],
            'cosmetique' => [
                'decors' => [
                    'centered on a clean seamless soft white studio background with a gentle shadow and bright even beauty lighting, crisp and sharp — premium cosmetics e-commerce product page look, no props',
                    'surrounded by fresh botanical elements, green leaves and delicate flowers on a marble surface, natural beauty editorial',
                    'floating on a soft pastel gradient backdrop with elegant swirling cream and liquid textures, premium beauty campaign',
                    'on a luxury vanity with golden accents, soft bokeh and glamorous warm lighting',
                ],
                'lifestyle' => 'held and showcased by a model with flawless glowing skin in soft beauty lighting, aspirational cosmetics ad',
            ],
            'autre' => [
                'decors' => [
                    'centered on a clean seamless white-to-light-grey gradient studio background with a soft realistic shadow, bright even softbox lighting, crisp and sharp — premium e-commerce product page look, no props, no dramatic spotlight',
                    'on a natural stone surface in soft daylight with a clean minimal background, organic premium aesthetic',
                    'against a bold vibrant colored studio backdrop with strong directional light, modern advertising look',
                    'surrounded by softly floating particles on a smooth gradient backdrop, creative editorial composition',
                    'on a marble surface with elegant minimalist props and soft bokeh, luxury catalog aesthetic',
                ],
                'lifestyle' => 'used naturally by a person in an authentic, aspirational real-life setting, shallow depth of field',
            ],
        ];

        $lib       = $sceneLibrary[$typeProduit] ?? $sceneLibrary['autre'];
        $decors    = $lib['decors'];
        $lifestyle = $lib['lifestyle'];

        // ════════════════════════════════════════════════════════════════════
        //  PROMPT FINAL DE CHAQUE IMAGE — UN SEUL ENDROIT PAR RÔLE.
        //  • index 0 = IMAGE PRINCIPALE  → modifier UNIQUEMENT le bloc « index 0 »
        //  • index 2 = LIFESTYLE (mannequin)
        //  • autres  = décors variés (bibliothèque ci-dessus, decors[1], decors[3]…)
        //  Les briques communes ($identity / $quality) évitent de dupliquer.
        // ════════════════════════════════════════════════════════════════════
        $identity = "Use the reference image ONLY as a reference for the product itself — keep the product 100% identical and instantly recognizable "
            . "(same shape, design, proportions, colors, materials, branding, logos, laces and every fine detail, perfectly sharp). "
            . "CRITICAL: COMPLETELY REMOVE AND REPLACE everything else around the product. The original background, wall, floor, shelf, surface, props and lighting MUST NOT appear at all. "
            . "Do NOT simply keep or lightly retouch the original photo — rebuild a brand-new scene entirely from scratch around the product. ";
        $quality = " Compose it as a HORIZONTAL landscape image (wider than tall, about 4:3). "
            . "Ultra-realistic professional photograph, shot on a full-frame DSLR 85mm f/1.8 lens, true-to-life accurate colors, "
            . "ultra-sharp micro-detail, 8K. STRICTLY photorealistic — NOT an illustration, NOT a 3D render, NOT a cartoon, no watermark, no border.";

        if ($index === 0) {
            // ───────────────── PROMPT IMAGE PRINCIPALE (shooting ultra pro) ─────────
            // 👉 Pour changer le rendu de la photo principale, MODIFIER ICI uniquement.
            $fullPrompt = "ULTRA-PREMIUM PROFESSIONAL PRODUCT PHOTOSHOOT — the kind of striking, magazine-grade hero image a top commercial photographer would shoot for a flagship brand campaign. " . $identity
                . "Completely reimagine the product as the hero of a world-class studio production (this is a full reshoot, never a retouch of the original snapshot). "
                . "STAGE: a beautiful high-end photography studio — the product resting on a smooth elegant surface (matte stone or a fine seamless paper sweep) with a soft, atmospheric neutral backdrop that has real depth and a subtle light gradient (never a flat plain white cutout). "
                . "LIGHTING: cinematic soft directional studio lighting — a sculpting key light with smooth highlights and gentle gradient falloff, a soft contact shadow grounding the product, and a faint realistic reflection on the surface. "
                . "CAMERA: full-frame body, 85mm lens, shallow depth of field with tasteful soft background bokeh, the product tack-sharp. "
                . "Stage the COMPLETE product at a dynamic, flattering THREE-QUARTER side hero angle, the product turned and pointing toward the RIGHT side of the frame (its front/toe facing right), like a premium sneaker campaign — showing mostly the outer/side profile together with a hint of the front, NOT a straight head-on front view. If it comes as a pair (e.g. shoes), arrange BOTH items together, slightly offset and both angled the SAME way toward the right, never a flat dead-side profile, both fully and equally visible. "
                . "CRITICAL — the pair must be rendered PERFECTLY: exactly TWO matching items (a true left + right), identical in shape, color, material and proportions, both complete and flawless, both fully in frame and not cropped, cleanly separated and not fused or overlapping into one blob. No deformed, distorted, duplicated, missing or extra item, no asymmetry or rendering defect — both pieces flawless and consistent. "
                . "The product is the crisp, perfectly-lit HERO, centered with balanced margins, fully visible and never cropped, filling about 75% of the frame. "
                . "The result must look like a genuine high-end commercial shoot — striking, premium, ultra-detailed and powerful. No people, no clutter, no text or logo overlay, no harsh dramatic spotlight."
                . $quality;
        } elseif ($index === 2) {
            // ───────────────── PROMPT IMAGE LIFESTYLE (mannequin) ───────────────────
            $fullPrompt = "You are a world-class commercial photographer. " . $identity
                . "NEW SCENE (lifestyle): " . $lifestyle . ". "
                . "Include a flawless, natural human model with perfect anatomy (no deformed hands, fingers or faces). "
                . "Frame the shot CLOSELY on the body area where the product is worn or used — e.g., the LOWER LEGS AND FEET for footwear, the wrist for a watch, the hands for a device, the head for headwear — so the product is LARGE, prominent and tack-sharp in the foreground. Do NOT make it a tiny distant full-body shot. The product is the clear focal point."
                . $quality;
        } else {
            // ───────────────── PROMPT IMAGES SECONDAIRES (décors variés) ────────────
            $scene = $decors[$index % count($decors)];
            $fullPrompt = "You are a world-class commercial product photographer creating a high-end editorial shot. " . $identity
                . "NEW SCENE: " . $scene . ". No people, no clutter, no text or logo overlays. The product is the hero, perfectly lit and detailed."
                . $quality;
        }

        // Nano Banana 2 (3.1) reconstruit bien mieux la scène complète (vrai studio) ;
        // repli automatique sur Nano Banana 1 (2.5) s'il est indisponible.
        $imageModels = ['gemini-3.1-flash-image-preview', 'gemini-2.5-flash-image'];
        $result = ['error' => 'Aucun modèle image disponible'];

        foreach ($imageModels as $model) {
            $result = $this->requestImage($model, [
                'contents' => [[
                    'parts' => [
                        ['text' => $fullPrompt],
                        ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64]],
                    ],
                ]],
                // Sortie image uniquement. Température haute pour générer des décors
                // vraiment variés et différents de l'original (le modèle garde tout de
                // même l'identité du produit). 0.2-0.45 recopiait trop le fond d'origine.
                'generationConfig' => ['responseModalities' => ['IMAGE'], 'temperature' => 0.95],
            ]);

            if (! isset($result['error'])) {
                $result['model'] = $model;
                break;
            }
            // On poursuit vers le modèle suivant si l'erreur indique :
            // - modèle indisponible / non supporté
            // - surcharge temporaire / quota / timeout transitoire
            // Cela évite de bloquer tout le flux sur une seule variante Gemini.
            if (! $this->shouldTryNextImageModel((string) $result['error'])) {
                break;
            }
        }

        if (isset($result['error'])) {
            return $result;
        }

        foreach (($result['candidates'][0]['content']['parts'] ?? []) as $part) {
            if (isset($part['inlineData'])) {
                return [
                    'data'     => $part['inlineData']['data'] ?? '',
                    'mimeType' => $part['inlineData']['mimeType'] ?? 'image/jpeg',
                    'prompt'   => $fullPrompt,
                ];
            }
        }

        return ['error' => 'Aucune image générée par Gemini'];
    }

    /** Détermine si l'on doit tenter un autre modèle image après un échec. */
    private function shouldTryNextImageModel(string $error): bool
    {
        $error = strtolower($error);

        foreach ([
            'not found',
            'not supported',
            'high demand',
            'overloaded',
            'try again',
            'temporarily',
            'unavailable',
            'is busy',
            'please retry',
            'quota',
            '429',
            '503',
            '502',
            '504',
        ] as $needle) {
            if (str_contains($error, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sauve une image générée (base64) en WebP, retourne le chemin relatif.
     * $landscape force un recadrage paysage 4:3 (utilisé pour la photo principale).
     */
    public function saveGeneratedImage(string $base64, string $mimeType, int $productId, int $index, bool $landscape = false): string|false
    {
        return $this->images->saveBase64AsWebp($base64, $mimeType, $productId, $index, $landscape ? 4 / 3 : null);
    }

    /** Parse robuste du JSON renvoyé par le modèle (markdown / regex fallback). */
    private function parseJson(string $text): ?array
    {
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $clean = trim((string) preg_replace('/```\s*$/', '', $clean));

        $parsed = json_decode($clean, true);
        if (is_array($parsed)) {
            return $parsed;
        }
        if (preg_match('/\{.*\}/s', $clean, $m)) {
            $parsed = json_decode($m[0], true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }
        return null;
    }
}
