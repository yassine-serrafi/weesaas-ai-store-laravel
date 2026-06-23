<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticPage extends Model
{
    protected $table = 'static_pages';

    protected $guarded = ['id'];

    protected $casts = [
        'blocks_json'         => 'array',
        'seo_json'            => 'array',
        'show_in_header_menu' => 'boolean',
        'show_in_footer_menu' => 'boolean',
        'ordre_affichage'     => 'integer',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Normalise les blocs renvoyés par l'IA vers la forme attendue par le front :
     * chaque bloc est un tableau associatif contenant une clé `type`.
     *
     * L'IA renvoie parfois des formes incorrectes (en mimant les exemples) :
     *   - {"hero_banner":"{\"type\":\"hero_banner\",...}"}  (valeur = chaîne JSON)
     *   - {"hero_banner":{...objet...}}                      (valeur = objet)
     * Cette méthode rétablit la forme canonique {"type":"hero_banner",...}.
     */
    public static function normalizeBlocks(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $block) {
            // Déjà au bon format.
            if (is_array($block) && isset($block['type'])) {
                $out[] = $block;
                continue;
            }

            // Bloc encodé sous forme {typeBloc: valeur}.
            if (is_array($block) && count($block) === 1) {
                $type = array_key_first($block);
                $value = $block[$type];

                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    $value = is_array($decoded) ? $decoded : ['contenu' => $value];
                }
                if (is_array($value)) {
                    $value['type'] = $value['type'] ?? $type;
                    $out[] = $value;
                    continue;
                }
            }

            // Bloc déjà objet mais sans type explicite : on le garde tel quel.
            if (is_array($block) && $block !== []) {
                $out[] = $block;
            }
        }

        return $out;
    }
}
