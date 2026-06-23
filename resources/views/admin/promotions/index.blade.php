@extends('admin.layouts.app')
@section('title', 'Promotions')

@php $typeLabels = ['pct'=>'% Pourcentage','fixe'=>'Montant fixe','livraison_gratuite'=>'Livraison gratuite']; @endphp

@section('content')
<div class="page-header">
  <div><div class="page-title">Codes promo</div></div>
</div>

@if ($errors->any())
<div class="alert alert-error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

<div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;align-items:start">
  <div class="card">
    <div class="card-header"><span class="card-title">Nouveau code</span></div>
    <div class="card-body">
      <form method="post" action="{{ route('admin.promotions.store') }}">
        @csrf
        <div class="form-group"><label class="form-label">Code <span class="required">*</span></label><input type="text" name="code" value="{{ old('code') }}" class="form-control" style="text-transform:uppercase" required></div>
        <div class="form-group"><label class="form-label">Type</label>
          <select name="type" class="form-select">
            @foreach ($typeLabels as $v=>$lbl)<option value="{{ $v }}" @selected(old('type')===$v)>{{ $lbl }}</option>@endforeach
          </select></div>
        <div class="form-group"><label class="form-label">Valeur</label><input type="number" step="0.01" name="valeur" value="{{ old('valeur') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Achat minimum</label><input type="number" step="0.01" name="min_achat" value="{{ old('min_achat', 0) }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Usage max (0 = illimité)</label><input type="number" name="max_usage" value="{{ old('max_usage', 0) }}" class="form-control"></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Début</label><input type="date" name="date_debut" value="{{ old('date_debut') }}" class="form-control"></div>
          <div class="form-group"><label class="form-label">Fin</label><input type="date" name="date_fin" value="{{ old('date_fin') }}" class="form-control"></div>
        </div>
        <button class="btn btn-primary" style="width:100%">Créer le code</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-wrap">
      <table class="admin-table">
        <thead><tr><th>Code</th><th>Type</th><th>Valeur</th><th>Usage</th><th>Validité</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          @forelse ($promos as $p)
          <tr>
            <td class="order-ref">{{ $p->code }}</td>
            <td>{{ $typeLabels[$p->type] ?? $p->type }}</td>
            <td>{{ $p->type === 'livraison_gratuite' ? '—' : number_format((float) $p->valeur, 0, ',', ' ') . ($p->type === 'pct' ? ' %' : '') }}</td>
            <td>{{ $p->nb_usage }}{{ $p->max_usage ? ' / ' . $p->max_usage : '' }}</td>
            <td style="font-size:12px;color:var(--text-muted)">{{ $p->date_debut?->format('d/m/y') ?? '∞' }} → {{ $p->date_fin?->format('d/m/y') ?? '∞' }}</td>
            <td><span class="badge badge-{{ $p->actif ? 'active' : 'draft' }}">{{ $p->actif ? 'Actif' : 'Inactif' }}</span></td>
            <td>
              <div style="display:flex;gap:6px">
                <form method="post" action="{{ route('admin.promotions.toggle', $p->id) }}">@csrf<button class="btn btn-ghost btn-sm">{{ $p->actif ? 'Désactiver' : 'Activer' }}</button></form>
                <form method="post" action="{{ route('admin.promotions.destroy', $p->id) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">✕</button></form>
              </div>
            </td>
          </tr>
          @empty
          <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">🏷️</div><div class="empty-state-title">Aucun code promo</div></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
