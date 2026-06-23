<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $statut = $request->query('statut', '');
        $q = trim((string) $request->query('q', ''));

        $orders = Order::with('product')
            ->when($statut !== '', fn ($query) => $query->where('statut', $statut))
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('reference', 'like', "%$q%")
                ->orWhere('nom_client', 'like', "%$q%")
                ->orWhere('telephone', 'like', "%$q%")))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders'  => $orders,
            'statut'  => $statut,
            'q'       => $q,
            'statuts' => Order::STATUTS,
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['product.mainImage', 'history']);
        return view('admin.orders.show', ['order' => $order, 'statuts' => Order::STATUTS]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:' . implode(',', Order::STATUTS)],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        $this->transition($order, $data['statut'], $request, $data['note'] ?? '');

        return back()->with('success', "Statut mis à jour : {$data['statut']}");
    }

    /** Action rapide : l'agent a appelé le client, la commande est confirmée. */
    public function markConfirmed(Request $request, Order $order): RedirectResponse
    {
        if (in_array($order->statut, ['confirmee', 'livree'], true)) {
            return back()->with('success', "Commande {$order->reference} déjà confirmée.");
        }
        $this->transition($order, 'confirmee', $request);

        return back()->with('success', "✓ Commande {$order->reference} confirmée.");
    }

    /** Action rapide : la commande a été livrée (comptée dans le CA encaissé). */
    public function markDelivered(Request $request, Order $order): RedirectResponse
    {
        if ($order->statut === 'livree') {
            return back()->with('success', "Commande {$order->reference} déjà livrée.");
        }
        $this->transition($order, 'livree', $request);

        return back()->with('success', "📦 Commande {$order->reference} marquée livrée.");
    }

    /** Change le statut + journalise l'historique + crée une notification (logique partagée). */
    private function transition(Order $order, string $statut, Request $request, string $note = ''): void
    {
        $ancien = $order->statut;
        if ($ancien === $statut) {
            return;
        }

        $order->update(['statut' => $statut]);

        OrderHistory::create([
            'order_id' => $order->id,
            'statut'   => $statut,
            'note'     => $note,
            'admin_id' => $request->session()->get('admin_id'),
        ]);

        Notification::create([
            'type'    => 'order_status',
            'titre'   => "Commande {$order->reference} → {$statut}",
            'message' => "Statut changé de $ancien à {$statut}.",
            'lien'    => route('admin.orders.show', $order->id),
        ]);
    }
}
