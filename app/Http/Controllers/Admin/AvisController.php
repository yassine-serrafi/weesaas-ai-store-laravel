<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AvisController extends Controller
{
    public function index(Request $request): View
    {
        $statut = $request->query('statut', '');

        $avis = Avis::with('product')
            ->when($statut !== '', fn ($q) => $q->where('statut', $statut))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.avis.index', compact('avis', 'statut'));
    }

    public function updateStatus(Request $request, Avis $avis): RedirectResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:en_attente,approuve,rejete'],
        ]);

        $avis->update(['statut' => $data['statut']]);

        return back()->with('success', "Avis #{$avis->id} → {$data['statut']}");
    }
}
