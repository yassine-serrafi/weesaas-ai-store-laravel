<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════╗
 * ║  Bibliothèque d'icônes SVG Premium — Style Heroicons Outline 2.x   ║
 * ║  Fonction : getSvgIcon($name, $size, $class, $attrs)               ║
 * ║                                                                      ║
 * ║  FIABILITÉ : fallback automatique sur 'zap' si icône inconnue       ║
 * ║  ALIAS MAP : 80+ mappings (emojis ↔ noms, FR / EN / AR)            ║
 * ║  CSS/JS : aucun impact — SVG inline neutre, taille explicite        ║
 * ╚══════════════════════════════════════════════════════════════════════╝
 */

if (!function_exists('getSvgIcon')) :

function getSvgIcon(string $name = 'zap', int $size = 24, string $class = '', array $attrs = []): string
{
    // ════════════════════════════════════════════════════════════════════
    //  CATALOGUE DES ICÔNES — paths SVG (viewBox 0 0 24 24)
    //  Type 'stroke' : stroke="currentColor" fill="none" → colorable via CSS color
    //  Type 'fill'   : fill="currentColor"  stroke="none" → colorable via CSS color
    //  Type 'raw'    : rendu tel quel (logos de marque ex: WhatsApp)
    // ════════════════════════════════════════════════════════════════════
    $icons = [
        // ── Sécurité & Confiance ──────────────────────────────────────
        'shield'       => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />'],
        'lock'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />'],
        'key'          => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 0 1 21.75 8.25Z" />'],
        'eye'          => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />'],
        'verified'     => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />'],

        // ── Logistique & Livraison ────────────────────────────────────
        'truck'        => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />'],
        'refresh'      => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />'],
        'package'      => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />'],
        'map-pin'      => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />'],

        // ── Interaction & Statut ──────────────────────────────────────
        'check'        => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />'],
        'check-circle' => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />'],
        'x'            => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />'],
        'x-circle'     => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'],
        'minus'        => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />'],
        'plus'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />'],
        'minus-circle' => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'],
        'plus-circle'  => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'],
        'info'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />'],

        // ── Étoiles & Note ────────────────────────────────────────────
        'star'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.563.563 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />'],
        'star-solid'   => ['f','<path fill="currentColor" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" />'],
        'star-half'    => ['f','<path fill="currentColor" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0L12 4.845V18.354l-4.627 2.826c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" />'],

        // ── Cœur, Émotion & Social ────────────────────────────────────
        'heart'        => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />'],
        'thumb-up'     => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M6.633 10.25c.806 0 1.533-.446 2.031-1.08a9.041 9.041 0 0 1 2.861-2.4c.723-.384 1.35-.956 1.653-1.715a4.498 4.498 0 0 0 .322-1.672V2.75a.75.75 0 0 1 .75-.75 2.25 2.25 0 0 1 2.25 2.25c0 1.152-.26 2.243-.723 3.218-.266.558.107 1.282.725 1.282m0 0h3.126c1.026 0 1.945.694 2.054 1.715.045.422.068.85.068 1.285a11.95 11.95 0 0 1-2.649 7.521c-.388.482-.987.729-1.605.729H13.48c-.483 0-.964-.078-1.423-.23l-3.114-1.04a4.501 4.501 0 0 0-1.423-.23H5.25M6.633 10.25H5.25a.75.75 0 0 0-.75.75v7.5c0 .414.336.75.75.75h1.383" />'],
        'face-smile'   => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" />'],

        // ── Communication ─────────────────────────────────────────────
        'mail'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />'],
        'phone'        => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />'],
        'chat'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />'],
        'bell'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />'],
        'whatsapp'     => ['f','<path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z" />'],

        // ── Marketing & Accroche ──────────────────────────────────────
        'zap'          => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />'],
        'fire'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.325 3.538 3.75 3.75 0 0 0 .83 3.93Z" />'],
        'sparkles'     => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.456-2.454L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />'],
        'rocket'       => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.841m2.465-1.174a2.25 2.25 0 1 0 3.182-3.182 2.25 2.25 0 0 0-3.182 3.182Z" />'],
        'lightbulb'    => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-3m0 0a8.121 8.121 0 0 0 4.5-2.388c.905-.905 1.5-2.147 1.5-3.512 0-2.761-2.239-5-5-5s-5 2.239-5 5c0 1.365.595 2.607 1.5 3.512A8.121 8.121 0 0 0 12 15Zm-3 3h6v2.25a2.25 2.25 0 0 1-2.25 2.25h-1.5A2.25 2.25 0 0 1 9 20.25V18Z" />'],
        'target'       => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15M12 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /><circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" />'],
        'diamond'      => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />'],

        // ── Commerce & Produit ────────────────────────────────────────
        'shopping-bag' => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />'],
        'shopping-cart'=> ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />'],
        'banknotes'    => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />'],
        'tag'          => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />'],
        'gift'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />'],
        'percent'      => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="m9 14.25 6-6m4.5-.75a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 9a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />'],
        'receipt'      => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-.75a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 9a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0ZM3.75 5.25v13.5a.75.75 0 0 0 .75.75h15a.75.75 0 0 0 .75-.75V5.25a.75.75 0 0 0-.75-.75h-15a.75.75 0 0 0-.75.75Z" />'],

        // ── Personnes & Société ───────────────────────────────────────
        'users'        => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />'],
        'user'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />'],
        'award'        => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />'],
        'crown'        => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.563.563 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />'],

        // ── Temps & Calendrier ────────────────────────────────────────
        'clock'        => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'],
        'calendar'     => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />'],

        // ── Nature & Ambiance ─────────────────────────────────────────
        'sun'          => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />'],
        'globe'        => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-.778.099-1.533.284-2.253" />'],
        'leaf'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M12.75 3.03v.568c0 .334.148.65.405.864l1.068.89c.442.369.535 1.01.216 1.49l-.51.766a2.25 2.25 0 0 1-1.161.886l-.143.048a1.107 1.107 0 0 0-.57 1.664c.369.555.169 1.307-.427 1.605L9 13.125l.423 1.059a.956.956 0 0 1-1.652.928l-.679-.906a1.125 1.125 0 0 0-1.906.172L4.5 15.75l-.612.153M12.75 3.031a9 9 0 0 0-8.862 12.872M12.75 3.031a9 9 0 0 1 6.69 14.036m0 0-.177-.529A2.249 2.249 0 0 0 17.128 15H16.5l-.324-.324a1.453 1.453 0 0 0-2.328.377l-.036.073a1.586 1.586 0 0 1-.982.816l-.99.282c-.55.157-.894.702-.8 1.267l.073.438c.08.474.49.821.97.821.846 0 1.598.542 1.865 1.345l.215.643" />'],
        'drop'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a8.25 8.25 0 0 0 8.25-8.25c0-4.04-3.172-9.153-8.25-14.378C6.922 3.597 3.75 8.71 3.75 12.75A8.25 8.25 0 0 0 12 21Z" />'],

        // ── Navigation & UI ───────────────────────────────────────────
        'chevron-down' => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />'],
        'chevron-up'   => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />'],
        'chevron-right'=> ['s','<path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />'],
        'chevron-left' => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />'],
        'arrow-right'  => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />'],
        'arrow-left'   => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />'],
        'bars'         => ['s','<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />'],
        // map-pin défini une seule fois dans la section Logistique ci-dessus
    ];

    // ════════════════════════════════════════════════════════════════════
    //  ALIAS MAP — 90+ correspondances
    //  Ordre de résolution : (1) exact match → (2) alias → (3) fallback
    // ════════════════════════════════════════════════════════════════════
    $name = strtolower(trim((string)$name));

    // Cas vide immédiat
    if ($name === '') { $name = 'zap'; }

    // Alias map
    static $aliasMap = [
        // ── Emojis Unicode → nom d'icône ──────────────────────────────
        '⚡' => 'zap',       '🎯' => 'target',    '💡' => 'lightbulb', '🛡️' => 'shield',
        '🛡'  => 'shield',   '✨' => 'sparkles',   '🚀' => 'rocket',    '💎' => 'diamond',
        '🔥' => 'fire',      '🌟' => 'star',       '⭐'  => 'star',     '💪' => 'thumb-up',
        '🚚' => 'truck',     '🚛' => 'truck',      '💰' => 'banknotes', '✅' => 'check-circle',
        '❌' => 'x-circle',  '📦' => 'package',    '❤️' => 'heart',     '❤'  => 'heart',
        '🛒' => 'shopping-bag','🛍️' => 'shopping-bag','🔒' => 'lock',  '🔐' => 'lock',
        '📞' => 'phone',     '☎️'  => 'phone',     '🔄' => 'refresh',   '♻️' => 'refresh',
        '📧' => 'mail',      '📩' => 'mail',       '✉️' => 'mail',      '🏆' => 'award',
        '👑' => 'crown',     '🌍' => 'globe',      '🌐' => 'globe',     '🏷️' => 'tag',
        '🎁' => 'gift',      '⏰' => 'clock',      '⏱️' => 'clock',    '☀️' => 'sun',
        '👍' => 'thumb-up',  '🏅' => 'award',      '🎖️' => 'award',   '😊' => 'face-smile',
        '👏' => 'thumb-up',  '💬' => 'chat',       '🔔' => 'bell',     '📍' => 'map-pin',
        '🗓️' => 'calendar', '🌿' => 'leaf',       '💧' => 'drop',     '🔑' => 'key',
        '👁️' => 'eye',      '🧾' => 'receipt',    '💳' => 'banknotes', '🛜' => 'globe',
        '% ' => 'percent',   '👤' => 'user',       '👥' => 'users',    '🎉' => 'sparkles',
        '✔️' => 'check',    '✓'  => 'check',

        // ── Synonymes français ────────────────────────────────────────
        'livraison'   => 'truck',     'expédition'  => 'truck',    'envoi'       => 'truck',
        'retour'      => 'refresh',   'remboursement'=> 'banknotes','garantie'   => 'shield',
        'sécurité'    => 'shield',    'securite'    => 'shield',    'protection' => 'shield',
        'paiement'    => 'banknotes', 'cash'        => 'banknotes', 'prix'       => 'tag',
        'promotion'   => 'tag',       'promo'       => 'tag',       'remise'     => 'percent',
        'cadeau'      => 'gift',      'bon plan'    => 'gift',      'contact'    => 'mail',
        'service'     => 'phone',     'assistance'  => 'phone',     'sav'        => 'phone',
        'avis'        => 'star',      'note'        => 'star',      'qualité'    => 'star',
        'clients'     => 'users',     'commande'    => 'shopping-bag','achat'    => 'shopping-bag',
        'panier'      => 'shopping-cart','rapide'   => 'zap',       'urgent'    => 'zap',
        'délai'       => 'clock',     'temps'       => 'clock',     'offre'     => 'sparkles',
        'eco'         => 'leaf',      'naturel'     => 'leaf',      'bio'       => 'leaf',
        'mondial'     => 'globe',     'international' => 'globe',   'poids'     => 'drop',

        // ── Synonymes anglais ─────────────────────────────────────────
        'delivery'    => 'truck',     'shipping'    => 'truck',     'return'     => 'refresh',
        'security'    => 'shield',    'safe'        => 'shield',    'warranty'   => 'shield',
        'trust'       => 'shield',    'verified'    => 'check-circle','certified'=> 'verified',
        'quality'     => 'star',      'premium'     => 'diamond',   'best'       => 'award',
        'support'     => 'phone',     'email'       => 'mail',      'message'    => 'chat',
        'money'       => 'banknotes', 'payment'     => 'banknotes', 'refund'     => 'banknotes',
        'fast'        => 'zap',       'quick'       => 'zap',       'express'    => 'zap',
        'customer'    => 'users',     'community'   => 'users',     'people'     => 'users',
        'award'       => 'award',     'medal'       => 'award',     'trophy'     => 'award',
        'global'      => 'globe',     'world'       => 'globe',
        'natural'     => 'leaf',      'organic'     => 'leaf',      'drop'       => 'drop',
        'water'       => 'drop',      'sale'        => 'tag',       'discount'   => 'percent',
        'order'       => 'shopping-bag','buy'       => 'shopping-bag','cart'     => 'shopping-cart',
        'location'    => 'map-pin',   'address'     => 'map-pin',   'time'       => 'clock',
        'idea'        => 'lightbulb', 'innovation'  => 'lightbulb', 'smart'      => 'lightbulb',

        // ── Synonymes arabes courants ─────────────────────────────────
        'توصيل' => 'truck',   'شحن'   => 'truck',   'ضمان' => 'shield',
        'أمان'  => 'shield',  'حماية' => 'shield',  'دعم'  => 'phone',
        'جودة'  => 'star',    'مميز'  => 'diamond', 'عملاء' => 'users',
        'هدية'  => 'gift',    'سريع'  => 'zap',     'رسالة' => 'mail',
        'تسوق'  => 'shopping-bag', 'خصم' => 'percent', 'طلب' => 'shopping-bag',
    ];

    if (isset($aliasMap[$name])) {
        $name = $aliasMap[$name];
    }

    // ════════════════════════════════════════════════════════════════════
    //  FALLBACK : icône inconnue → 'zap' (jamais de rendu vide/cassé)
    // ════════════════════════════════════════════════════════════════════
    if (!isset($icons[$name])) {
        $name = 'zap';
    }

    [$type, $path] = $icons[$name];

    // ════════════════════════════════════════════════════════════════════
    //  RENDU SVG — 3 modes selon le type
    // ════════════════════════════════════════════════════════════════════

    // Échappement sécurisé des attributs supplémentaires
    $attrStr = '';
    if (!empty($attrs)) {
        foreach ($attrs as $k => $v) {
            // h() est définie dans config.php ; fallback htmlspecialchars si absent
            $safe    = function_exists('h') ? h($v) : htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
            $attrStr .= ' ' . htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') . '="' . $safe . '"';
        }
    }

    $classAttr = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';

    switch ($type) {
        case 'f': // fill only (étoile pleine, etc.)
            return sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" stroke="none" width="%d" height="%d"%s%s aria-hidden="true">%s</svg>',
                $size, $size, $classAttr, $attrStr, $path
            );

        case 'raw': // logo de marque (WhatsApp, etc.) — path contient son propre fill
            return sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" width="%d" height="%d"%s%s aria-hidden="true">%s</svg>',
                $size, $size, $classAttr, $attrStr, $path
            );

        default: // 's' — stroke (Heroicons standard)
            return sprintf(
                '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="%d" height="%d"%s%s aria-hidden="true">%s</svg>',
                $size, $size, $classAttr, $attrStr, $path
            );
    }
}

endif; // function_exists guard — pas de redéfinition si fichier inclus deux fois
