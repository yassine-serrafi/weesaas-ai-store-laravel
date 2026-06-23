<?php

namespace App\Services\AI;

use App\Services\SettingsRepository;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Service OpenAI (port de includes/ai_openai.php).
 * Copywriting de la landing page (gpt-4o) + génération d'attributs dynamiques.
 */
class OpenAiService
{
    private const BASE = 'https://api.openai.com/v1/';

    public function __construct(private SettingsRepository $settings) {}

    private function key(): string
    {
        return trim($this->settings->get('openai_api_key'));
    }

    /** Appel chat completions (réponse JSON). */
    public function request(array $messages, string $model = 'gpt-4o', float $temperature = 0.7, int $maxTokens = 4096): array
    {
        $key = $this->key();
        if ($key === '') {
            return ['error' => 'Clé API OpenAI non configurée'];
        }

        try {
            $resp = Http::timeout(180)
                ->withToken($key)
                ->post(self::BASE . 'chat/completions', [
                    'model'           => $model,
                    'messages'        => $messages,
                    'temperature'     => $temperature,
                    'max_tokens'      => $maxTokens,
                    'response_format' => ['type' => 'json_object'],
                ]);
        } catch (Throwable $e) {
            return ['error' => 'HTTP error: ' . $e->getMessage()];
        }

        if (! $resp->successful()) {
            return ['error' => $resp->json('error.message') ?? ('Erreur OpenAI ' . $resp->status())];
        }

        $content = $resp->json('choices.0.message.content') ?? '';
        $finish = $resp->json('choices.0.finish_reason') ?? 'unknown';

        if ($finish === 'length') {
            return ['error' => 'Réponse GPT tronquée (finish_reason=length) : augmenter max_tokens', 'raw' => substr($content, 0, 500)];
        }

        $parsed = json_decode($content, true);
        return is_array($parsed) ? $parsed : ['error' => 'JSON invalide reçu de GPT', 'raw' => $content];
    }

    /** Génère le contenu complet de la landing page. */
    public function generateContent(array $analysis, array $params): array
    {
        $systemPrompt = $this->buildSystemPrompt(array_merge($params, [
            'type_marque' => $analysis['type_marque'] ?? 'generique',
        ]));

        $prix      = $params['prix'] ?? 0;
        $prixBarre = $params['prix_barre'] ?? 0;
        $remise    = $prixBarre > 0 ? round((($prixBarre - $prix) / $prixBarre) * 100) . '%' : '';
        $pays      = $params['pays_vente'] ?? 'maroc';
        $garantie  = $params['garantie_jours'] ?? 30;
        $livraison = $params['livraison_delai'] ?? getDelaiLivraison($pays);
        $instr     = $params['instructions_libres'] ?? '';
        $nomManuel = $params['nom_manuel'] ?? '';
        $catManuelle = $params['cat_manuelle'] ?? ($analysis['type_produit'] ?? '');
        $symbole   = $params['symbole_devise'] ?? 'MAD';
        $descIa    = $params['description_ia_input'] ?? '';

        $colorRule = ! empty($params['disable_colors'])
            ? "\n\nNO COLORS RULE (CRITICAL):\nLe marchand a DÉSACTIVÉ le choix de couleur. Ne génère AUCUN attribut 'couleur'."
            : '';

        $nomVisuel = $analysis['nom_produit'] ?? '';
        $descVisuelle = $analysis['description'] ?? '';
        $benefices = $analysis['benefices_str'] ?? implode(', ', (array) ($analysis['benefices'] ?? []));
        $publicCible = $analysis['public_cible'] ?? '';
        $styleVisuel = $analysis['style_visuel'] ?? '';

        $userPrompt = <<<PROMPT
=== CRITICAL RULES — READ BEFORE GENERATING ===

[1] TARGET LANGUAGE RULE (ABSOLUTE): Every word of the output MUST be in the target language defined in the system prompt. NO French if target is Arabic/English.
[2] BRAND NAME RULE: International brand names MUST stay in original English form. Never transliterate brand/model.
[3] VISUAL FIDELITY RULE: Base ALL content on the vision description. DO NOT invent invisible features.
[4] NAMING RULE: If manual name empty, use the exact detected name.
[5] DYNAMIC CUSTOM INSTRUCTIONS RULE: Heavily favor the "INSTRUCTIONS SPÉCIALES" below.

Génère la structure JSON complète pour une landing page e-commerce :

PRODUIT:
- Nom (visuel): {$nomVisuel}
- Nom (manuel): {$nomManuel}
- Type: {$catManuelle}
- Description visuelle: {$descVisuelle}
- Bénéfices: {$benefices}
- Public cible: {$publicCible}
- Style visuel: {$styleVisuel}

PRIX & OFFRE:
- Prix: {$prix} {$symbole}
- Prix barré: {$prixBarre} {$symbole}
- Réduction: {$remise}
- Livraison: {$livraison}
- Garantie: {$garantie} jours

INSTRUCTIONS SPÉCIALES:
{$instr}

DESCRIPTION AVEC IA:
{$descIa}
{$colorRule}

Return a JSON with this EXACT structure (ALL text in target language, brand names in English):
{
  "nom_produit": "Final product name",
  "sections": {
    "hero": {"titre":"","sous_titre":"","badge":"","cta":""},
    "urgency_bar": {"texte":"","sous_texte":""},
    "features_grid": {"titre":"","features":[{"icone":"star","titre":"","texte":""},{"icone":"shield","titre":"","texte":""},{"icone":"truck","titre":"","texte":""},{"icone":"heart","titre":"","texte":""}]},
    "stats_bar": {"stats":[{"valeur":"1,200+","label":""},{"valeur":"98%","label":""},{"valeur":"","label":""},{"valeur":"","label":""}]},
    "comparison_table": {"titre":"","notre_produit":"","concurrent":"","points":[{"point":"","nous":true,"eux":false},{"point":"","nous":true,"eux":false},{"point":"","nous":false,"eux":true},{"point":"","nous":true,"eux":false}]},
    "guarantee_bar": {"garanties":[{"icone":"shield","texte":""},{"icone":"truck","texte":""},{"icone":"refresh","texte":""},{"icone":"lock","texte":""}]},
    "order_form": {"titre":"","sous_titre":"","cta":"","note_securite":""}
  },
  "temoignages": [
    {"prenom":"","ville":"","note":5,"texte":"Authentic review praising ONLY THE STORE SERVICES (fast delivery, support, COD trust). DO NOT mention the product.","attribut":""},
    {"prenom":"","ville":"","note":5,"texte":"Another service review. NO product mention.","attribut":""},
    {"prenom":"","ville":"","note":4,"texte":"Service review praising WhatsApp support or packaging. NO product mention.","attribut":""}
  ],
  "faq": [
    {"question":"","reponse":""},{"question":"","reponse":""},{"question":"","reponse":""},{"question":"","reponse":""}
  ],
  "seo": {"title":"","description":"","og_title":"","mots_cles":["","",""]},
  "preuve_sociale": ["","",""],
  "attributes": {"groupes":[{"id":"taille|couleur|stockage|volume|variant","label":"","type":"pills","valeurs":["ONLY available values from instructions, else standard range for product type"],"required":true}]},
  "description_html": "If 'DESCRIPTION AVEC IA' provided, persuasive HTML in French (<p>,<ul>,<strong>), else \"\".",
  "description_ar": "Arabic translation of description_html with same tags, else \"\"."
}
CRITICAL RULE FOR attributes.groupes[].valeurs: If instructions restrict availability (e.g. 'only size 43'), USE ONLY THOSE VALUES. Else standard range.
PROMPT;

        return $this->request(
            [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            $this->settings->get('gpt_model', 'gpt-4o') ?: 'gpt-4o',
            0.8,
            6000
        );
    }

    private function buildSystemPrompt(array $params): string
    {
        $corePrompt = $this->settings->get('prompt_core') ?: $this->defaultCorePrompt();
        $langue = $params['langue'] ?? 'ar_marocain';
        $pays = $params['pays_vente'] ?? 'maroc';
        $devise = $params['devise'] ?? 'MAD';
        $typeMarque = $params['type_marque'] ?? 'generique';

        $langueInstructions = match ($langue) {
            'ar_marocain' => "LANGUE: Arabe marocain (Darija). Ton direct et familier. Mots du quotidien marocain. Tu peux inclure naturellement des touches de français comme en vraie darija. PAS d'arabe littéraire formel.",
            'ar_standard' => 'LANGUE: Arabe Standard Moderne (Fusha). Professionnel, compréhensible dans tout le monde arabe. Aucun dialecte. Style premium.',
            'ar_golfe'    => 'LANGUE: Dialecte du Golfe. Registre saoudien/émirati. Ton formel et prestigieux. Qualité et luxe mis en avant.',
            'ar_mixte'    => "LANGUE: Mixte. Titres et accroches en Arabe Standard (fusha) pour l'impact. Corps du texte et témoignages en Darija marocaine pour la proximité.",
            'fr'          => 'LANGUE: Français standard. Clair, orienté vente, professionnel mais accessible.',
            'en'          => 'LANGUE: English. International, neutral, professional. Clear and conversion-focused.',
            'ar_fr'       => 'LANGUE: Bilingue Arabe + Français. Contenu en deux langues sur la même page. Direction globale RTL, blocs français en LTR intégrés.',
            default       => 'LANGUE: Français.',
        };
        $paysInstructions = match ($pays) {
            'maroc'    => 'PAYS DE VENTE: Maroc. Mentionne des villes marocaines (Casablanca, Rabat, Marrakech, Fès...). Livraison 2-4 jours ouvrés. COD privilégié.',
            'saudi'    => 'PAYS DE VENTE: Arabie Saoudite. Villes: Riyad, Djeddah, Dammam, Médine. Livraison 2-5 jours. Qualité et prestige.',
            'uae'      => 'PAYS DE VENTE: UAE/Dubaï. Villes: Dubaï, Abu Dhabi, Sharjah. Livraison 1-3 jours. Premium et service irréprochable.',
            'france'   => 'PAYS DE VENTE: France. Villes: Paris, Lyon, Marseille, Toulouse. Livraison 2-5 jours. Garanties légales, retours faciles.',
            'belgique' => 'PAYS DE VENTE: Belgique. Villes: Bruxelles, Anvers, Gand. Livraison 2-4 jours.',
            default    => 'PAYS DE VENTE: International.',
        };
        $deviseInstructions = match ($devise) {
            'MAD' => 'DEVISE: MAD (درهم marocain). COD privilégié. Livraison partout au Maroc.',
            'SAR' => 'DEVISE: SAR (ريال سعودي). Ton premium. Livraison express. Confiance et qualité.',
            'AED' => 'DEVISE: AED (درهم إماراتي). Très premium. Service irréprochable. Dubaï lifestyle.',
            'EUR' => 'DEVISE: EUR (€). Ton européen. Prix transparents. Retours garantis.',
            'USD' => 'DEVISE: USD ($). Ton international. Qualité internationale.',
            'GBP' => 'DEVISE: GBP (£). Ton britannique. Premium et fiable.',
            default => 'DEVISE: MAD.',
        };
        $marqueInstruction = $typeMarque === 'internationale'
            ? "RÈGLE MARQUE: Ce produit est une MARQUE INTERNATIONALE. Le nom de marque et le modèle RESTENT en langue d'origine (anglais). Ne jamais translittérer. Combiner: nom anglais + valeur dans la langue cible. Correct: 'Nike Air Force 1 — أصلية بسعر استثنائي'. INTERDIT: 'نايكي اير فورس 1'."
            : 'RÈGLE MARQUE: Ce produit est GÉNÉRIQUE (sans marque internationale). Nom entièrement dans la langue choisie.';

        $res = "$corePrompt\n\n$langueInstructions\n\n$paysInstructions\n\n$deviseInstructions\n\n$marqueInstruction";
        if (! empty($params['instructions_libres'])) {
            $res .= "\n\nINSTRUCTIONS SPÉCIFIQUES DU CLIENT (PRIORITAIRES) :\n" . $params['instructions_libres'];
        }
        return $res;
    }

    /** Génère des attributs par défaut selon le type produit (fallback si GPT n'en fournit pas). */
    public function generateAttributes(array $analysis, string $langue, string $pays): array
    {
        $typeProduct = strtolower($analysis['type_produit'] ?? 'autre');
        $couleurs = array_values(array_filter((array) ($analysis['couleurs_detectees'] ?? [])));
        $attrsDetected = array_values(array_filter((array) ($analysis['attributs_detectes'] ?? [])));
        $typeAttr = strtolower($analysis['type_attribut'] ?? '');

        $isAr = str_starts_with($langue, 'ar');
        $isEn = $langue === 'en';
        $L = [
            'taille'   => $isAr ? 'المقاس' : ($isEn ? 'Size' : 'Taille'),
            'couleur'  => $isAr ? 'اللون' : ($isEn ? 'Color' : 'Couleur'),
            'stockage' => $isAr ? 'التخزين' : ($isEn ? 'Storage' : 'Stockage'),
            'matiere'  => $isAr ? 'المادة' : ($isEn ? 'Material' : 'Matière'),
            'poids'    => $isAr ? 'الوزن' : ($isEn ? 'Weight' : 'Poids'),
            'teinte'   => $isAr ? 'اللون/الظل' : ($isEn ? 'Shade' : 'Teinte'),
            'volume'   => $isAr ? 'الحجم' : ($isEn ? 'Volume' : 'Volume'),
            'metal'    => $isAr ? 'المعدن' : ($isEn ? 'Metal' : 'Métal'),
            'variant'  => $isAr ? 'الخيار' : ($isEn ? 'Option' : 'Option'),
        ];
        $pills = fn ($id, $vals, $req = false) => ['id' => $id, 'label' => $L[$id] ?? $L['variant'], 'type' => 'pills', 'valeurs' => array_values($vals), 'required' => $req];
        $colorPills = fn ($id, $vals, $req = false) => ['id' => $id, 'label' => $L[$id] ?? $L['couleur'], 'type' => 'color_pills', 'valeurs' => array_values($vals), 'required' => $req];

        $groupes = [];
        if (preg_match('/chaussure|shoe|basket|sneaker|sandale|botte|mocassin|espadrille|talon/', $typeProduct)) {
            $groupes[] = $pills('taille', ['36', '37', '38', '39', '40', '41', '42', '43', '44', '45'], true);
            if ($couleurs) $groupes[] = $colorPills('couleur', $couleurs);
        } elseif (preg_match('/vetement|vêtement|robe|pantalon|shirt|t-shirt|chemise|pull|manteau|veste|jean|hoodie|survêt|abaya|djellaba|kaftan/', $typeProduct)) {
            $groupes[] = $pills('taille', ['XS', 'S', 'M', 'L', 'XL', 'XXL'], true);
            if ($couleurs) $groupes[] = $colorPills('couleur', $couleurs);
        } elseif (preg_match('/telephone|smartphone|phone|iphone|samsung|xiaomi|huawei|oppo/', $typeProduct)) {
            $groupes[] = $pills('stockage', $attrsDetected ?: ['128Go', '256Go', '512Go'], true);
            if ($couleurs) $groupes[] = $colorPills('couleur', $couleurs);
        } elseif (preg_match('/electronique|laptop|ordinateur|tablette|tablet|pc|macbook|ipad|écouteur|airpod|casque|montre connect/', $typeProduct)) {
            $groupes[] = $pills('stockage', $attrsDetected ?: ['64Go', '128Go', '256Go', '512Go'], true);
            if ($couleurs) $groupes[] = $colorPills('couleur', $couleurs);
        } elseif (preg_match('/parfum|fragrance|eau de/', $typeProduct)) {
            $groupes[] = $pills('volume', $attrsDetected ?: ['30ml', '50ml', '75ml', '100ml'], true);
        } elseif (preg_match('/cosmetique|cosmétique|beaute|beauté|maquillage|soin|crème|serum|lotion/', $typeProduct)) {
            if ($couleurs && count($couleurs) > 1) $groupes[] = $colorPills('teinte', $couleurs, true);
            elseif ($attrsDetected) $groupes[] = $pills('variant', $attrsDetected, true);
            else $groupes[] = $pills('volume', ['30ml', '50ml', '100ml', '200ml']);
        } elseif (preg_match('/bijou|montre|bracelet|collier|bague|pendentif|chaîne|jonc/', $typeProduct)) {
            if ($attrsDetected) $groupes[] = $pills('taille', $attrsDetected);
            $groupes[] = $colorPills('metal', $couleurs ?: ['Or', 'Argent', 'Or Rose']);
        } elseif (preg_match('/sac|handbag|cartable|pochette|ceinture|portefeuille/', $typeProduct)) {
            if ($couleurs) $groupes[] = $colorPills('couleur', $couleurs, true);
            if ($attrsDetected) $groupes[] = $pills('matiere', $attrsDetected);
        } elseif (preg_match('/meuble|canapé|chaise|table|lit|bureau|armoire|étagère|deco|décoration|luminaire|lampe|coussin/', $typeProduct)) {
            $groupes[] = $colorPills('couleur', $attrsDetected ?: ($couleurs ?: ['Naturel', 'Blanc', 'Noir', 'Noyer']));
        } elseif (preg_match('/alimentation|food|supplement|protein|whey|nutrition|snack|chocolat|café|thé|épice/', $typeProduct)) {
            $groupes[] = $pills('poids', $attrsDetected ?: ['250g', '500g', '1kg']);
        } elseif (preg_match('/sport|fitness|yoga|gym|vélo|running|natation/', $typeProduct)) {
            $groupes[] = $pills('taille', ['S', 'M', 'L', 'XL', 'XXL'], true);
            if ($couleurs) $groupes[] = $colorPills('couleur', $couleurs);
        } else {
            if ($attrsDetected) $groupes[] = $pills($typeAttr ?: 'variant', $attrsDetected);
            if ($couleurs) $groupes[] = $colorPills('couleur', $couleurs);
        }

        $first = $groupes[0] ?? null;
        return [
            'type'         => $first['id'] ?? 'couleur',
            'valeurs'      => $first['valeurs'] ?? [],
            'type_produit' => $typeProduct,
            'groupes'      => $groupes,
        ];
    }

    /** Génère le contenu d'une page statique (port de generateStaticPage). */
    public function generateStaticPage(array $params): array
    {
        $type = $params['type'] ?? 'about';
        $langue = $params['langue'] ?? 'fr';
        $siteName = $this->settings->get('nom_boutique', 'Notre Boutique') ?: 'Notre Boutique';
        $siteDesc = $this->settings->get('description_boutique');
        $pays = $params['pays'] ?? 'maroc';
        $instructions = $params['instructions_libres'] ?? '';

        $langLabel = match ($langue) {
            'ar_marocain' => 'Arabe marocain (Darija)',
            'ar_standard' => 'Arabe Standard Moderne',
            'ar_golfe'    => 'Arabe du Golfe',
            'en'          => 'English',
            default       => 'Français',
        };

        $typeConfig = match ($type) {
            'about'     => ['label' => 'Qui sommes-nous', 'prompt' => "Génère une page 'Qui sommes-nous' professionnelle et inspirante. Histoire de la marque, mission, valeurs, promesse client.", 'blocks' => ['hero_banner', 'text_block', 'values_block', 'stats_block', 'text_block']],
            'contact'   => ['label' => 'Nous contacter', 'prompt' => "Génère une page Contact professionnelle. Message d'accueil chaleureux, infos de contact, horaires, délais de réponse.", 'blocks' => ['hero_banner', 'contact_block', 'faq_block']],
            'cgv'       => ['label' => 'Conditions Générales de Vente', 'prompt' => 'Génère des CGV complètes adaptées au e-commerce COD : commandes, livraison, retours, remboursements, garanties, données personnelles.', 'blocks' => ['hero_banner', 'text_block', 'text_block', 'text_block', 'text_block']],
            'faq'       => ['label' => 'Questions fréquentes', 'prompt' => 'Génère une FAQ complète (10-12 Q/R) : livraison, paiement COD, retours, tailles, qualité, tracking, garantie.', 'blocks' => ['hero_banner', 'faq_block']],
            'livraison' => ['label' => 'Politique de livraison', 'prompt' => 'Génère une page Politique de Livraison claire : délais, zones, frais, tracking, colis non reçu. COD privilégié.', 'blocks' => ['hero_banner', 'text_block', 'faq_block']],
            'retour'    => ['label' => 'Retours & Remboursements', 'prompt' => 'Génère une page Retours & Remboursements rassurante : conditions, délais, procédure étape par étape.', 'blocks' => ['hero_banner', 'text_block', 'faq_block']],
            'mentions'  => ['label' => 'Mentions légales', 'prompt' => 'Génère des mentions légales standards e-commerce : éditeur, hébergeur, propriété intellectuelle, données, cookies.', 'blocks' => ['hero_banner', 'text_block']],
            default     => ['label' => 'Page informative', 'prompt' => 'Génère une page informative professionnelle et engageante pour ce site e-commerce.', 'blocks' => ['hero_banner', 'text_block']],
        };

        $systemPrompt = "Tu es un expert en rédaction e-commerce pour la boutique '$siteName'. "
            . ($siteDesc ? "Description boutique: $siteDesc. " : '')
            . "Langue de rédaction: $langLabel. Pays: $pays. "
            . 'Retourne UNIQUEMENT un JSON valide, sans markdown, sans explication.';

        $blockExamples = [
            'hero_banner'   => '{"type":"hero_banner","titre":"Titre accrocheur","sous_titre":"Sous-titre engageant"}',
            'text_block'    => '{"type":"text_block","titre":"Titre de section","contenu":"Texte riche, plusieurs paragraphes séparés par \\n\\n. Minimum 3 paragraphes."}',
            'values_block'  => '{"type":"values_block","titre":"Nos valeurs","valeurs":[{"emoji":"🎯","titre":"Valeur 1","texte":"Description"},{"emoji":"❤️","titre":"Valeur 2","texte":"Description"},{"emoji":"⭐","titre":"Valeur 3","texte":"Description"}]}',
            'stats_block'   => '{"type":"stats_block","stats":[{"valeur":"1000+","label":"Clients satisfaits"},{"valeur":"48h","label":"Livraison rapide"},{"valeur":"30j","label":"Garantie"}]}',
            'faq_block'     => '{"type":"faq_block","titre":"Questions fréquentes","items":[{"q":"Question 1","a":"Réponse détaillée 1"},{"q":"Question 2","a":"Réponse détaillée 2"}]}',
            'contact_block' => '{"type":"contact_block","titre":"Contactez-nous","message":"Message d\'accueil","email":"contact@boutique.com","tel":"","whatsapp":"","adresse":"","horaires":"Lun-Ven 9h-18h"}',
        ];

        // Séquence de blocs suggérée pour ce type de page (exemples concrets, objets).
        $exampleSeq = array_values(array_filter(array_map(
            fn ($t) => $blockExamples[$t] ?? null,
            array_values(array_unique($typeConfig['blocks']))
        )));

        $userPrompt = $typeConfig['prompt'] . "\n\n"
            . ($instructions ? "INSTRUCTIONS SPÉCIALES DU CLIENT À RESPECTER ABSOLUMENT :\n$instructions\n\n" : '')
            . "Retourne STRICTEMENT un JSON de cette forme :\n"
            . '{"titre":"' . $typeConfig['label'] . '","seo_title":"...","seo_description":"...","blocks":['
            . implode(',', $exampleSeq) . "]}\n\n"
            . "RÈGLES IMPÉRATIVES sur \"blocks\" :\n"
            . "- C'est un TABLEAU d'OBJETS.\n"
            . "- Chaque objet contient DIRECTEMENT un champ \"type\" (valeurs : hero_banner, text_block, values_block, stats_block, faq_block, contact_block) plus ses propres champs.\n"
            . "- N'imbrique JAMAIS un bloc sous une clé portant son nom de type.\n"
            . "- Ne mets JAMAIS une chaîne JSON encodée comme valeur ; uniquement de vrais objets.\n"
            . "Génère entre 3 et 6 blocs selon le type de page. Contenu complet, professionnel, dans la langue demandée.";

        return $this->request([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ], $this->settings->get('gpt_model', 'gpt-4o') ?: 'gpt-4o', 0.65, 3000);
    }

    private function defaultCorePrompt(): string
    {
        return "Tu es un expert en copywriting e-commerce et en psychologie de vente, spécialisé dans les marchés arabes et francophones. "
            . "Ton objectif est de créer des pages de vente qui convertissent au maximum. "
            . "Applique : urgence réelle, bénéfices avant caractéristiques, preuves sociales crédibles, objections traitées, CTA clair et unique, langue naturelle.";
    }
}
