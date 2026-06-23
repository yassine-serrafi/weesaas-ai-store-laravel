@extends('admin.layouts.app')
@section('title', 'Créer un produit (IA)')

@section('content')
<div class="page-header">
  <div><div class="page-title">Créer un produit par IA</div><div class="page-subtitle">L'IA analyse l'image, génère les visuels puis rédige la landing page</div></div>
  <div class="page-actions"><a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">← Retour</a></div>
</div>

@if ($errors->any())
<div class="alert alert-error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

<div class="card" style="max-width:720px">
  <div class="card-body">
    <form method="post" action="{{ route('admin.products.generate.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="form-group">
        <label class="form-label">Image du produit <span class="required">*</span></label>
        <input type="file" name="image" accept="image/*" required class="form-control">
      </div>
      <div class="form-row-3">
        <div class="form-group"><label class="form-label">Prix <span class="required">*</span></label><input type="number" step="0.01" name="prix" value="{{ old('prix') }}" required class="form-control"></div>
        <div class="form-group"><label class="form-label">Prix barré</label><input type="number" step="0.01" name="prix_barre" value="{{ old('prix_barre') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Frais livraison</label><input type="number" step="0.01" name="frais_livraison" value="{{ old('frais_livraison') }}" placeholder="Défaut réglages : {{ rtrim(rtrim(number_format((float) $fraisDefaut, 2, '.', ''), '0'), '.') ?: '0' }}" class="form-control"><span class="form-hint" style="font-size:11px;color:var(--text-muted)">Laisser vide = frais par défaut (Réglages).</span></div>
      </div>
      <div class="form-row-3">
        <div class="form-group"><label class="form-label">Langue</label>
          <select name="langue" class="form-select">
            @foreach (['fr'=>'Français','ar_marocain'=>'Arabe marocain','ar_standard'=>'Arabe standard','ar_golfe'=>'Arabe Golfe','en'=>'English'] as $v=>$lbl)
            <option value="{{ $v }}" @selected(old('langue','fr')===$v)>{{ $lbl }}</option>@endforeach
          </select></div>
        <div class="form-group"><label class="form-label">Pays</label>
          <select name="pays_vente" class="form-select">
            @foreach (['maroc'=>'Maroc','saudi'=>'Arabie S.','uae'=>'UAE','france'=>'France','belgique'=>'Belgique'] as $v=>$lbl)
            <option value="{{ $v }}" @selected(old('pays_vente','maroc')===$v)>{{ $lbl }}</option>@endforeach
          </select></div>
        <div class="form-group"><label class="form-label">Style</label>
          <select name="style_page" class="form-select">
            @foreach (['moderne','luxe','minimaliste','energique','confiance'] as $v)<option value="{{ $v }}" @selected(old('style_page','moderne')===$v)>{{ ucfirst($v) }}</option>@endforeach
          </select></div>
      </div>
      <div class="form-row-3">
        <div class="form-group"><label class="form-label">Nb. visuels IA (0-4)</label><input type="number" name="nb_images" min="0" max="4" value="{{ old('nb_images', 3) }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Garantie (jours)</label><input type="number" name="garantie_jours" value="{{ old('garantie_jours', 30) }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Stock disponible</label><input type="number" name="stock_quantite" min="0" value="{{ old('stock_quantite', 100) }}" class="form-control"><span class="form-hint" style="font-size:11px;color:var(--text-muted)">Nombre de pièces en vente (la quantité commandable est plafonnée par ce stock).</span></div>
      </div>
      <div class="form-group"><label class="form-label">Nom du produit (optionnel — sinon détecté par l'IA)</label><input type="text" name="nom_produit" value="{{ old('nom_produit') }}" class="form-control"></div>
      <div class="form-group"><label class="form-label">Instructions spécifiques</label><textarea name="instructions_libres" rows="2" class="form-control" placeholder="Tailles dispo, contraintes…">{{ old('instructions_libres') }}</textarea></div>
      <div class="form-group" style="display:flex;gap:24px">
        <label class="toggle-label" style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="disable_colors" value="1"> Désactiver les couleurs</label>
        <label class="toggle-label" style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="mode_rapide" value="1"> Mode rapide (sans images)</label>
      </div>
      <button class="btn btn-primary btn-lg">🚀 Lancer la génération</button>
      <p class="form-hint" style="margin-top:8px">Le traitement s'exécute en file d'attente. Assurez-vous que <code>php artisan queue:work</code> tourne.</p>
    </form>
  </div>
</div>
@endsection
