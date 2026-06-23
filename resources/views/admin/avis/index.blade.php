@extends('admin.layouts.app')
@section('title', 'Avis clients')

@php $badgeMap = ['approuve'=>'active','rejete'=>'annulee','en_attente'=>'nouvelle']; @endphp

@section('content')
<div class="page-header">
  <div><div class="page-title">Avis clients</div></div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body">
    <form method="get" style="display:flex;gap:10px;align-items:end">
      <div class="form-group" style="margin:0"><label class="form-label">Statut</label>
        <select name="statut" class="form-select" style="min-width:180px">
          <option value="">Tous</option>
          @foreach (['en_attente','approuve','rejete'] as $s)<option value="{{ $s }}" @selected($statut === $s)>{{ $s }}</option>@endforeach
        </select></div>
      <button class="btn btn-primary">Filtrer</button>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Client</th><th>Produit</th><th>Note</th><th>Commentaire</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody>
        @forelse ($avis as $a)
        <tr>
          <td>{{ $a->nom_client }}</td>
          <td>{{ Str::limit($a->product->nom_produit ?? '—', 22) }}</td>
          <td style="color:var(--amber)">{{ str_repeat('★', (int) $a->note) }}</td>
          <td>{{ Str::limit($a->commentaire, 50) }}</td>
          <td><span class="badge badge-{{ $badgeMap[$a->statut] ?? 'draft' }}">{{ $a->statut }}</span></td>
          <td>
            <div style="display:flex;gap:6px">
              <form method="post" action="{{ route('admin.avis.status', $a->id) }}">@csrf<input type="hidden" name="statut" value="approuve"><button class="btn btn-success btn-sm">Approuver</button></form>
              <form method="post" action="{{ route('admin.avis.status', $a->id) }}">@csrf<input type="hidden" name="statut" value="rejete"><button class="btn btn-danger btn-sm">Rejeter</button></form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">⭐</div><div class="empty-state-title">Aucun avis</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<div style="margin-top:16px">{{ $avis->links() }}</div>
@endsection
