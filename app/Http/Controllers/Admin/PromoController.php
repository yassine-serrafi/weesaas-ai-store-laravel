<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CodePromo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestion des codes promo (port de weeadmin/promotions.php).
 */
class PromoController extends Controller
{
    public function index(): View
    {
        $promos = CodePromo::orderByDesc('created_at')->paginate(25);
        return view('admin.promotions.index', compact('promos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'       => ['required', 'string', 'max:50', 'unique:codes_promo,code'],
            'type'       => ['required', 'in:pct,fixe,livraison_gratuite'],
            'valeur'     => ['nullable', 'numeric', 'min:0'],
            'min_achat'  => ['nullable', 'numeric', 'min:0'],
            'max_usage'  => ['nullable', 'integer', 'min:0'],
            'date_debut' => ['nullable', 'date'],
            'date_fin'   => ['nullable', 'date'],
        ]);

        CodePromo::create([
            'code'       => strtoupper($data['code']),
            'type'       => $data['type'],
            'valeur'     => $data['valeur'] ?? 0,
            'min_achat'  => $data['min_achat'] ?? 0,
            'max_usage'  => $data['max_usage'] ?? 0,
            'date_debut' => $data['date_debut'] ?? null,
            'date_fin'   => $data['date_fin'] ?? null,
            'actif'      => true,
        ]);

        return back()->with('success', 'Code promo créé.');
    }

    public function toggle(CodePromo $codePromo): RedirectResponse
    {
        $codePromo->actif = ! $codePromo->actif;
        $codePromo->save();
        return back()->with('success', "Code « {$codePromo->code} » " . ($codePromo->actif ? 'activé' : 'désactivé'));
    }

    public function destroy(CodePromo $codePromo): RedirectResponse
    {
        $codePromo->delete();
        return back()->with('success', 'Code promo supprimé.');
    }
}
