<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Liste des villes par pays (port de weeadmin/ajax/get_villes.php).
 * Données statiques dans storage/app/villes/{pays}.json.
 */
class CityController extends Controller
{
    private const FALLBACK = ['Casablanca', 'Rabat', 'Marrakech', 'Fès', 'Tanger', 'Agadir', 'Meknès', 'Oujda', 'Tétouan', 'Salé'];

    public function index(Request $request): JsonResponse
    {
        $pays = preg_replace('/[^a-z_]/', '', strtolower((string) $request->query('pays', 'maroc')));
        $file = storage_path("app/villes/{$pays}.json");

        $villes = is_file($file)
            ? (json_decode((string) file_get_contents($file), true) ?: self::FALLBACK)
            : self::FALLBACK;

        return response()->json($villes)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
