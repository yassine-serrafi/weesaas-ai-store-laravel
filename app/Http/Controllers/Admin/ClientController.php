<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\View\View;

/**
 * Liste des clients, dérivée des commandes (port de weeadmin/clients.php).
 * Un client = un numéro de téléphone, agrégé sur ses commandes.
 */
class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Order::query()
            ->selectRaw("telephone,
                MAX(nom_client) as nom_client,
                MAX(ville) as ville,
                MAX(pays) as pays,
                COUNT(*) as nb_commandes,
                SUM(total_ttc) as total_depense,
                MAX(created_at) as derniere_commande")
            ->where('telephone', '!=', '')
            ->groupBy('telephone')
            ->orderByDesc('derniere_commande')
            ->paginate(25);

        return view('admin.clients.index', compact('clients'));
    }
}
