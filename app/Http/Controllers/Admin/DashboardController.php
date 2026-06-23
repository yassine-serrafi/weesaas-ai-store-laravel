<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\SettingsRepository;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(SettingsRepository $settings): View
    {
        $commandes = Order::count();

        // CA encaissé = commandes réellement LIVRÉES (le vrai cash en COD).
        $caLivre   = (float) Order::where('statut', 'livree')->sum('total_ttc');
        $nbLivrees = Order::where('statut', 'livree')->count();

        // En cours = confirmées/expédiées, en attente de livraison.
        $caEnCours = (float) Order::whereIn('statut', ['confirmee', 'expediee'])->sum('total_ttc');
        $nbEnCours = Order::whereIn('statut', ['confirmee', 'expediee'])->count();

        $stats = [
            'produits'        => Product::count(),
            'produits_actifs' => Product::where('status', 'active')->count(),
            'commandes'       => $commandes,
            'commandes_jour'  => Order::whereDate('created_at', Carbon::today())->count(),

            // Argent
            'ca_livre'        => $caLivre,
            'nb_livrees'      => $nbLivrees,
            'ca_en_cours'     => $caEnCours,
            'nb_en_cours'     => $nbEnCours,
            'panier_moyen'    => $nbLivrees > 0 ? $caLivre / $nbLivrees : 0.0,
            'taux_livraison'  => $commandes > 0 ? round($nbLivrees / $commandes * 100) : 0,
            'devise'          => getSymboleDevise($settings->get('devise_defaut') ?: 'MAD')['symbole'],
        ];

        $dernieres = Order::with('product')->orderByDesc('created_at')->limit(10)->get();

        // Nouveaux clients : un client = un téléphone, classé par sa PREMIÈRE commande.
        $nouveauxClients = Order::query()
            ->selectRaw("telephone,
                MAX(nom_client)   as nom_client,
                MAX(ville)        as ville,
                COUNT(*)          as nb_commandes,
                SUM(total_ttc)    as total_depense,
                MIN(created_at)   as premiere_commande")
            ->where('telephone', '!=', '')
            ->groupBy('telephone')
            ->orderByDesc('premiere_commande')
            ->limit(6)
            ->get();

        // Nombre de nouveaux clients ce mois-ci (1re commande dans le mois courant).
        $stats['nouveaux_mois'] = Order::query()
            ->where('telephone', '!=', '')
            ->groupBy('telephone')
            ->havingRaw('MIN(created_at) >= ?', [Carbon::now()->startOfMonth()])
            ->get(['telephone'])
            ->count();

        return view('admin.dashboard', compact('stats', 'dernieres', 'nouveauxClients'));
    }
}
