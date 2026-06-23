@extends('admin.layouts.app')
@section('title', 'Commande ' . $order->reference)

@section('content')
<div class="page-header">
  <div><div class="page-title">Commande <span class="order-ref">{{ $order->reference }}</span></div></div>
  <div class="page-actions"><a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">← Retour</a></div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px">
  <div class="card">
    <div class="card-header"><span class="card-title">Détails</span></div>
    <div class="card-body">
      <table class="admin-table">
        <tr><th style="width:170px">Client</th><td>{{ $order->nom_client }}</td></tr>
        <tr><th>Téléphone</th><td>{{ $order->telephone }}</td></tr>
        <tr><th>Ville</th><td>{{ $order->ville }}</td></tr>
        <tr><th>Adresse</th><td>{{ $order->adresse ?: '—' }}</td></tr>
        <tr><th>Produit</th><td>{{ $order->product->nom_produit ?? '—' }}</td></tr>
        @foreach (($order->attributs ?: []) as $k => $v)
        <tr><th>{{ ucfirst($k) }}</th><td>{{ $v }}</td></tr>
        @endforeach
        <tr><th>Quantité</th><td>{{ $order->quantite }}</td></tr>
        <tr><th>Frais livraison</th><td>{{ number_format((float) $order->frais_livraison, 0, ',', ' ') }} {{ $order->symbole_devise }}</td></tr>
        <tr><th>Total</th><td class="money" style="font-size:16px;color:var(--orange);font-weight:700">{{ number_format((float) $order->total_ttc, 0, ',', ' ') }} {{ $order->symbole_devise }}</td></tr>
        <tr><th>Note client</th><td>{{ $order->note ?: '—' }}</td></tr>
        <tr><th>Source</th><td>{{ $order->source }}@if($order->utm_source) · {{ $order->utm_source }}@endif</td></tr>
        <tr><th>Date</th><td>{{ $order->created_at?->format('d/m/Y H:i') }}</td></tr>
      </table>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-header"><span class="card-title">Statut</span> <span class="badge badge-{{ $order->statut }}">{{ $order->statut }}</span></div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.orders.status', $order->id) }}">
          @csrf
          <div class="form-group"><select name="statut" class="form-select">
            @foreach ($statuts as $s)<option value="{{ $s }}" @selected($order->statut === $s)>{{ $s }}</option>@endforeach
          </select></div>
          <div class="form-group"><textarea name="note" rows="2" class="form-control" placeholder="Note (optionnel)"></textarea></div>
          <button class="btn btn-primary" style="width:100%">Mettre à jour</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">Historique</span></div>
      <div class="card-body">
        @forelse ($order->history as $h)
        <div style="padding:8px 0;border-bottom:1px solid var(--border-light);font-size:13px">
          <span class="badge badge-{{ $h->statut }}">{{ $h->statut }}</span>
          <span style="color:var(--text-muted);font-size:11px;margin-left:6px">{{ $h->created_at?->format('d/m H:i') }}</span>
          @if ($h->note)<div style="color:var(--text-secondary);margin-top:4px">{{ $h->note }}</div>@endif
        </div>
        @empty
        <div style="color:var(--text-muted);font-size:13px">Aucun changement.</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
