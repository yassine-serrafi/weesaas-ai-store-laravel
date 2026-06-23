@extends('admin.layouts.app')
@section('title', 'Gestion des menus')

@section('content')
<div class="page-header">
  <div><div class="page-title">Gestion des menus</div><div class="page-subtitle">Liens affichés dans l'en-tête et le pied de page de la boutique</div></div>
</div>

@if ($errors->any())
<div class="alert alert-error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

{{-- AJOUTER UN LIEN --}}
<div class="card" style="margin-bottom:16px">
  <div class="card-header"><span class="card-title">Ajouter un lien</span></div>
  <div class="card-body">
    <form method="post" action="{{ route('admin.menus.store') }}">
      @csrf
      <div class="form-row-3">
        <div class="form-group"><label class="form-label">Libellé FR <span class="required">*</span></label><input type="text" name="label_fr" value="{{ old('label_fr') }}" class="form-control" required></div>
        <div class="form-group"><label class="form-label">Libellé AR</label><input type="text" name="label_ar" value="{{ old('label_ar') }}" class="form-control" dir="rtl"></div>
        <div class="form-group"><label class="form-label">Libellé EN</label><input type="text" name="label_en" value="{{ old('label_en') }}" class="form-control"></div>
      </div>
      <div class="form-row-3">
        <div class="form-group" style="grid-column:span 1"><label class="form-label">URL <span class="required">*</span></label><input type="text" name="url" value="{{ old('url') }}" class="form-control" placeholder="suivi  ·  pages/contact/  ·  https://…" required></div>
        <div class="form-group"><label class="form-label">Position</label>
          <select name="position" class="form-select">
            <option value="header" @selected(old('position')==='header')>En-tête</option>
            <option value="footer" @selected(old('position','footer')==='footer')>Pied de page</option>
          </select></div>
        <div class="form-group"><label class="form-label">Ordre</label><input type="number" name="ordre" value="{{ old('ordre', 0) }}" class="form-control"></div>
      </div>
      <button class="btn btn-primary">Ajouter le lien</button>
    </form>
  </div>
</div>

{{-- TABLES ÉDITABLES PAR POSITION --}}
@foreach (['header'=>'En-tête','footer'=>'Pied de page'] as $pos => $posLabel)
<div class="card" style="margin-bottom:16px">
  <div class="card-header"><span class="card-title">{{ $posLabel }}</span></div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th style="width:70px">Ordre</th><th>Libellé FR</th><th>AR</th><th>EN</th><th>URL</th><th>Position</th><th>Statut</th><th style="width:150px">Actions</th></tr></thead>
      <tbody>
        @forelse (($menus[$pos] ?? collect()) as $m)
        <tr>
          <td><input form="mf{{ $m->id }}" type="number" name="ordre" value="{{ $m->ordre }}" class="form-control" style="width:60px"></td>
          <td><input form="mf{{ $m->id }}" type="text" name="label_fr" value="{{ $m->label_fr }}" class="form-control"></td>
          <td><input form="mf{{ $m->id }}" type="text" name="label_ar" value="{{ $m->label_ar }}" class="form-control" dir="rtl" style="min-width:90px"></td>
          <td><input form="mf{{ $m->id }}" type="text" name="label_en" value="{{ $m->label_en }}" class="form-control" style="min-width:90px"></td>
          <td><input form="mf{{ $m->id }}" type="text" name="url" value="{{ $m->url }}" class="form-control" style="min-width:140px"></td>
          <td>
            <select form="mf{{ $m->id }}" name="position" class="form-select" style="min-width:100px">
              <option value="header" @selected($m->position==='header')>En-tête</option>
              <option value="footer" @selected($m->position==='footer')>Pied</option>
            </select>
          </td>
          <td><span class="badge badge-{{ $m->statut ? 'active' : 'draft' }}">{{ $m->statut ? 'Visible' : 'Masqué' }}</span></td>
          <td>
            <div style="display:flex;gap:6px;align-items:center">
              <button form="mf{{ $m->id }}" class="btn btn-primary btn-sm" title="Enregistrer">💾</button>
              <form method="post" action="{{ route('admin.menus.toggle', $m->id) }}">@csrf<button class="btn btn-ghost btn-sm">{{ $m->statut ? 'Masquer' : 'Afficher' }}</button></form>
              <form method="post" action="{{ route('admin.menus.destroy', $m->id) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">✕</button></form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:20px">Aucun lien dans cette zone.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endforeach

{{-- Formulaires d'édition (hors des tableaux, référencés par l'attribut form=) --}}
@foreach ($menus->flatten() as $m)
<form id="mf{{ $m->id }}" method="post" action="{{ route('admin.menus.update', $m->id) }}" style="display:none">@csrf @method('PUT')</form>
@endforeach
@endsection
