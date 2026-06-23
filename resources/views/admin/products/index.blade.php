@extends('admin.layouts.app')
@section('title', 'Produits')

@section('content')
<div class="page-header">
  <div><div class="page-title">Produits</div><div class="page-subtitle">{{ $products->total() }} produit(s)</div></div>
  <div class="page-actions"><a href="{{ route('admin.products.create') }}" class="btn btn-primary">🚀 Créer un produit (IA)</a></div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body">
    <form method="get" class="form-row" style="grid-template-columns:2fr 1fr auto;align-items:end">
      <div class="form-group" style="margin:0"><label class="form-label">Recherche</label>
        <input type="text" name="q" value="{{ $q }}" placeholder="Nom, slug…" class="form-control"></div>
      <div class="form-group" style="margin:0"><label class="form-label">Statut</label>
        <select name="status" class="form-select">
          <option value="">Tous</option>
          @foreach (['draft','generating','active','paused','archived'] as $s)<option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>@endforeach
        </select></div>
      <button class="btn btn-primary">Filtrer</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th></th><th>Produit</th><th>Prix</th><th>Stock</th><th>Cmd</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse ($products as $p)
        <tr>
          <td style="width:52px">@if ($p->mainImage)<img src="{{ $p->mainImage->url }}" class="product-thumb" style="width:42px;height:42px;object-fit:cover;border-radius:8px">@else 📦 @endif</td>
          <td><div class="product-name">{{ Str::limit($p->nom_produit, 36) }}</div><div class="product-slug">{{ $p->slug }}</div></td>
          <td class="money">{{ number_format((float) $p->prix, 0, ',', ' ') }} {{ $p->symbole_devise }}</td>
          <td>{{ $p->stock_dispo }}</td>
          <td>{{ $p->orders_count }}</td>
          <td><span class="badge badge-{{ $p->status }}">{{ $p->status }}</span></td>
          <td>
            <div style="display:flex;gap:6px;align-items:center">
              <a href="{{ route('admin.products.edit', $p->id) }}" class="btn btn-secondary btn-sm">Éditer</a>
              <a href="{{ site_url('pages/' . $p->slug . '/') }}" target="_blank" class="btn btn-ghost btn-sm">Voir</a>
              <form method="post" action="{{ route('admin.products.status', $p->id) }}" style="display:inline">@csrf<button class="btn btn-ghost btn-sm">{{ $p->status === 'active' ? 'Pause' : 'Activer' }}</button></form>
              <form method="post" action="{{ route('admin.products.destroy', $p->id) }}" style="display:inline" onsubmit="return confirm('Supprimer définitivement « {{ addslashes(Str::limit($p->nom_produit, 36)) }} » ? Cette action est irréversible (le produit sera archivé s\'il a des commandes).')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">Supprimer</button></form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">📦</div><div class="empty-state-title">Aucun produit</div><div class="empty-state-text">Créez votre premier produit avec l'IA.</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<div style="margin-top:16px">{{ $products->links() }}</div>
@endsection
