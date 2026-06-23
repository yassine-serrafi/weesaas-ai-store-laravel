<?php

/**
 * Helpers globaux WeeSaaS (portés depuis includes/helpers.php).
 *
 * Chargés via composer autoload "files". Adaptés à Laravel : les anciennes
 * constantes (SITE_URL…) deviennent des appels à config()/url().
 * La logique métier (langue, devises, slug arabe) est conservée à l'identique
 * pour ne rien casser côté front (SEO, RTL, prix).
 */

if (! function_exists('site_url')) {
    /** URL absolue du site (équivalent de l'ancienne constante SITE_URL). */
    function site_url(string $path = ''): string
    {
        $base = rtrim((string) config('app.url'), '/') . '/';
        return $path === '' ? $base : $base . ltrim($path, '/');
    }
}

if (! function_exists('asset_v')) {
    /**
     * URL d'un asset public avec cache-busting (?v=mtime).
     * Garantit que les navigateurs rechargent CSS/JS après une modification
     * (évite de servir une ancienne feuille de style en cache).
     */
    function asset_v(string $path): string
    {
        $url = site_url($path);
        $full = public_path($path);
        if (is_file($full)) {
            $url .= '?v=' . filemtime($full);
        }
        return $url;
    }
}

if (! function_exists('h')) {
    /** Échappe pour affichage HTML (équivalent de l'ancien h()). */
    function h(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}

if (! function_exists('js_str')) {
    /** Échappe pour un contexte JS (entre guillemets). */
    function js_str(mixed $value): string
    {
        return addslashes(htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
    }
}

if (! function_exists('svg_icon')) {
    /** Rend une icône SVG inline (voir app/Support/icons.php). */
    function svg_icon(string $name = 'zap', int $size = 24, string $class = '', array $attrs = []): string
    {
        return getSvgIcon($name, $size, $class, $attrs);
    }
}

if (! function_exists('menu_url')) {
    /**
     * Normalise une URL de menu (legacy ou propre) vers une URL Laravel valide.
     * Gère : ancres/vides → accueil ; endpoints .php legacy (suivi.php → suivi) ;
     * URLs absolues (prod) rebasées sur le domaine courant.
     */
    function menu_url(string $url): string
    {
        $url = trim($url);

        if ($url === '' || $url === '#' || $url === '/' || $url === 'index.php' || $url === 'index') {
            return site_url();
        }

        // URL absolue → on ne garde que le chemin (compat dev + cutover).
        if (preg_match('~^(?:f|ht)tps?://~i', $url)) {
            $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
            if ($path === '' || $path === 'index.php') {
                return site_url();
            }
            $url = $path;
        }

        // Endpoints legacy en .php → URLs propres (suivi.php → suivi, merci.php → merci).
        $url = preg_replace('~\.php$~', '', $url);

        return rtrim(site_url(), '/') . '/' . ltrim($url, '/');
    }
}

if (! function_exists('slugify')) {
    function slugify(string $text, string $lang = 'fr'): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        if ($lang === 'ar' || preg_match('/\p{Arabic}/u', $text)) {
            $translit = [
                'ا' => 'a', 'أ' => 'a', 'إ' => 'i', 'آ' => 'a', 'ب' => 'b', 'ت' => 't', 'ث' => 'th',
                'ج' => 'j', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'dh', 'ر' => 'r', 'ز' => 'z',
                'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'dh', 'ع' => 'a',
                'غ' => 'gh', 'ف' => 'f', 'ق' => 'q', 'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
                'ه' => 'h', 'و' => 'w', 'ي' => 'y', 'ى' => 'a', 'ة' => 'a', 'ء' => '', 'ئ' => 'y', 'ؤ' => 'w',
            ];
            $text = strtr($text, $translit);
        }
        $accents = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'é' => 'e',
            'î' => 'i', 'ï' => 'i', 'ì' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o', 'ò' => 'o', 'ó' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u', 'ç' => 'c', 'ñ' => 'n',
        ];
        $text = strtr($text, $accents);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }
}

if (! function_exists('resolveShopLang')) {
    /**
     * Résout une langue canonique (ar_marocain, ar_golfe, fr, en…)
     * → ['code' => 'ar'|'fr'|'en', 'dir' => 'rtl'|'ltr'].
     * Source de vérité unique pour le front (catalogue, produit, merci, suivi).
     */
    function resolveShopLang(string $canonique): array
    {
        static $rtl = ['ar_marocain', 'ar_standard', 'ar_golfe', 'ar_mixte', 'ar_fr'];
        $isRtl = in_array($canonique, $rtl, true);
        return [
            'code' => $isRtl ? 'ar' : ($canonique === 'en' ? 'en' : 'fr'),
            'dir'  => $isRtl ? 'rtl' : 'ltr',
        ];
    }
}

if (! function_exists('langKey')) {
    /** Normalise un code langue (ar_*, fr, en) → 'ar' | 'fr' | 'en'. */
    function langKey(string $code): string
    {
        return str_starts_with($code, 'ar') ? 'ar' : ($code === 'en' ? 'en' : 'fr');
    }
}

if (! function_exists('formatPrice')) {
    function formatPrice(float $amount, string $devise, string $position): string
    {
        $fmt = number_format($amount, 0, '.', ' ');
        return $position === 'avant' ? "$devise $fmt" : "$fmt $devise";
    }
}

if (! function_exists('affPrix')) {
    /** Format prix avec séparateur français (espace) — comme le front legacy. */
    function affPrix(float $amount, string $sym, string $pos): string
    {
        $fmt = number_format($amount, 0, ',', ' ');
        return $pos === 'avant' ? "$sym $fmt" : "$fmt $sym";
    }
}

if (! function_exists('getDeviseParLangue')) {
    function getDeviseParLangue(string $langue): string
    {
        return match ($langue) {
            'ar_marocain', 'ar_mixte' => 'MAD',
            'ar_golfe'    => 'SAR',
            'ar_standard' => 'USD',
            'fr'          => 'EUR',
            'en'          => 'USD',
            'ar_fr'       => 'MAD',
            default       => 'MAD',
        };
    }
}

if (! function_exists('getSymboleDevise')) {
    function getSymboleDevise(string $devise): array
    {
        return match ($devise) {
            'MAD' => ['symbole' => 'درهم', 'position' => 'apres'],
            'SAR' => ['symbole' => 'ر.س', 'position' => 'apres'],
            'AED' => ['symbole' => 'د.إ', 'position' => 'avant'],
            'EUR' => ['symbole' => '€', 'position' => 'apres'],
            'USD' => ['symbole' => '$', 'position' => 'avant'],
            'GBP' => ['symbole' => '£', 'position' => 'avant'],
            'XOF' => ['symbole' => 'FCFA', 'position' => 'apres'],
            'DZD' => ['symbole' => 'د.ج', 'position' => 'apres'],
            default => ['symbole' => 'MAD', 'position' => 'apres'],
        };
    }
}

if (! function_exists('getIndicatifPays')) {
    function getIndicatifPays(string $pays): string
    {
        return match ($pays) {
            'maroc'    => '+212',
            'saudi'    => '+966',
            'uae'      => '+971',
            'france'   => '+33',
            'belgique' => '+32',
            default    => '+212',
        };
    }
}

if (! function_exists('getDelaiLivraison')) {
    function getDelaiLivraison(string $pays): string
    {
        return match ($pays) {
            'maroc'    => '2-4 jours ouvrés',
            'saudi'    => '2-5 jours',
            'uae'      => '1-3 jours',
            'france'   => '2-5 jours ouvrés',
            'belgique' => '2-4 jours',
            default    => '3-7 jours',
        };
    }
}

if (! function_exists('truncate')) {
    function truncate(string $text, int $length = 150): string
    {
        return mb_strlen($text) <= $length ? $text : mb_substr($text, 0, $length) . '...';
    }
}

if (! function_exists('jsonDecodeArray')) {
    function jsonDecodeArray(?string $json, array $default = []): array
    {
        if (empty($json)) {
            return $default;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : $default;
    }
}
