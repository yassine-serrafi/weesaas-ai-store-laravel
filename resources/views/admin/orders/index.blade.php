@extends('admin.layouts.app')
@section('title', 'Commandes')

@section('content')
<div class="page-header">
  <div><div class="page-title">Commandes</div><div class="page-subtitle">{{ $orders->total() }} commande(s)</div></div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body">
    <form method="get" class="form-row" style="grid-template-columns:2fr 1fr auto;align-items:end">
      <div class="form-group" style="margin:0"><label class="form-label">Recherche</label>
        <input type="text" name="q" value="{{ $q }}" placeholder="Réf, client, tél…" class="form-control"></div>
      <div class="form-group" style="margin:0"><label class="form-label">Statut</label>
        <select name="statut" class="form-select">
          <option value="">Tous</option>
          @foreach ($statuts as $s)<option value="{{ $s }}" @selected($statut === $s)>{{ $s }}</option>@endforeach
        </select></div>
      <button class="btn btn-primary">Filtrer</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Réf.</th><th>Client</th><th>Tél</th><th>Ville</th><th>Produit</th><th>Total</th><th>Statut</th><th>Date</th><th></th></tr></thead>
      <tbody>
        @forelse ($orders as $o)
        <tr>
          <td class="order-ref">{{ $o->reference }}</td>
          <td>{{ $o->nom_client }}</td>
          <td>{{ $o->telephone }}</td>
          <td>{{ $o->ville }}</td>
          <td>{{ Str::limit($o->product->nom_produit ?? '—', 22) }}</td>
          <td class="money">{{ number_format((float) $o->total_ttc, 0, ',', ' ') }} {{ $o->symbole_devise }}</td>
          <td><span class="badge badge-{{ $o->statut }}">{{ $o->statut }}</span></td>
          <td style="color:var(--text-muted);white-space:nowrap">{{ $o->created_at?->format('d/m H:i') }}</td>
          <td>
            <div class="order-actions">
              <a href="{{ route('admin.orders.show', $o->id) }}" class="btn btn-secondary btn-sm">Voir</a>
              @unless (in_array($o->statut, ['confirmee', 'livree', 'annulee'], true))
              <form method="post" action="{{ route('admin.orders.confirm', $o->id) }}" onsubmit="return confirm('Confirmer la commande {{ $o->reference }} ? (le client a validé par téléphone)')">
                @csrf
                <button class="btn btn-primary btn-sm" title="Le client a confirmé par téléphone">✓ Confirmer</button>
              </form>
              @endunless
              @unless (in_array($o->statut, ['livree', 'annulee', 'retour'], true))
              <form method="post" action="{{ route('admin.orders.deliver', $o->id) }}" onsubmit="return confirm('Marquer la commande {{ $o->reference }} comme LIVRÉE ? (elle sera comptée dans le CA encaissé)')">
                @csrf
                <button class="btn btn-success btn-sm" title="Commande livrée — comptée dans le chiffre d'affaires">📦 Livré</button>
              </form>
              @endunless
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">🛒</div><div class="empty-state-title">Aucune commande</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<div style="margin-top:16px">{{ $orders->links() }}</div>
@endsection
