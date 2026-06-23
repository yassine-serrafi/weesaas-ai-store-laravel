@extends('admin.layouts.app')
@section('title', 'Éditer le produit')

@section('content')
<div class="page-header">
  <div><div class="page-title">{{ Str::limit($product->nom_produit, 50) }}</div><div class="page-subtitle">{{ $product->slug }}</div></div>
  <div class="page-actions"><a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">← Retour</a></div>
</div>

@if ($errors->any())
<div class="alert alert-error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

<div class="card" style="max-width:680px">
  <div class="card-body">
    <form method="post" action="{{ route('admin.products.update', $product->id) }}">
      @csrf @method('PUT')
      <div class="form-group">
        <label class="form-label">Nom du produit</label>
        <input type="text" name="nom_produit" value="{{ old('nom_produit', $product->nom_produit) }}" class="form-control">
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Prix</label><input type="number" step="0.01" name="prix" value="{{ old('prix', $product->prix) }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Prix barré</label><input type="number" step="0.01" name="prix_barre" value="{{ old('prix_barre', $product->prix_barre) }}" class="form-control"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Stock</label><input type="number" name="stock_quantite" value="{{ old('stock_quantite', $product->stock_quantite) }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Frais livraison</label><input type="number" step="0.01" name="frais_livraison" value="{{ old('frais_livraison', $product->frais_livraison) }}" class="form-control"></div>
      </div>
      <div class="form-group"><label class="form-label">Badge héro</label><input type="text" name="badge_hero" value="{{ old('badge_hero', $product->badge_hero) }}" class="form-control"></div>
      <div class="form-group"><label class="form-label">Statut</label>
        <select name="status" class="form-select">
          @foreach (['draft','generating','active','paused','archived'] as $s)<option value="{{ $s }}" @selected(old('status', $product->status) === $s)>{{ $s }}</option>@endforeach
        </select>
      </div>
      <div class="alert alert-info">ℹ️ Le contenu IA (sections, témoignages, FAQ) est géré par le générateur. Cet écran couvre les champs commerciaux essentiels.</div>
      <button class="btn btn-primary btn-lg">Enregistrer</button>
    </form>
  </div>
</div>

@php $imgs = $product->images()->orderBy('position')->get(); @endphp
@if ($imgs->count())
<div class="card" style="max-width:680px;margin-top:16px">
  <div class="card-header">
    <span class="card-title">🖼️ Images générées</span>
    <span class="page-subtitle" style="margin:0">Régénère une photo — elle remplace l'ancienne</span>
  </div>
  <div class="card-body">
    <div class="regen-grid">
      @foreach ($imgs as $img)
      <div class="regen-item">
        <div class="regen-thumb"><img src="{{ $img->url }}" alt="" loading="lazy"></div>
        <div class="regen-label">{{ $img->position == 0 ? '⭐ Principale' : 'Photo ' . ($img->position + 1) }}</div>
        <form method="post" action="{{ route('admin.products.image.regenerate', [$product->id, $img->position]) }}" onsubmit="return weeRegen(this)">
          @csrf
          <button type="submit" class="btn btn-secondary btn-sm" style="width:100%">🔄 Régénérer</button>
        </form>
      </div>
      @endforeach
    </div>
    <p style="font-size:12px;color:var(--text-muted);margin-top:12px">
      ⏱️ Chaque régénération prend ~10-20s (appel IA Gemini). La page se recharge avec la nouvelle image.
      La photo principale (⭐) utilise un rendu studio e-commerce ; les suivantes des décors variés.
    </p>
  </div>
</div>
@endif
@endsection

@push('head')
<style>
.regen-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px}
.regen-item{display:flex;flex-direction:column;gap:8px}
.regen-thumb{aspect-ratio:1;border-radius:10px;overflow:hidden;border:1px solid var(--border-light);background:var(--bg-page)}
.regen-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.regen-label{font-size:12px;font-weight:600;color:var(--text-primary);text-align:center}
</style>
@endpush

@push('scripts')
<script>
function weeRegen(form){
  var b = form.querySelector('button');
  b.disabled = true;
  b.textContent = '⏳ Génération…';
  b.closest('.regen-item').style.opacity = '.6';
  return true;
}
</script>
@endpush
