@extends('admin.layouts.app')
@section('title', 'Créer une page')

@section('content')
<div class="page-header">
  <div><div class="page-title">Créer une page institutionnelle</div><div class="page-subtitle">L'IA rédige le contenu dans la langue choisie</div></div>
  <div class="page-actions"><a href="{{ route('admin.pages.index') }}" class="btn btn-secondary btn-sm">← Retour</a></div>
</div>

@if ($errors->any())
<div class="alert alert-error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
@endif

<div class="card" style="max-width:640px">
  <div class="card-body">
    <form method="post" action="{{ route('admin.pages.store') }}">
      @csrf
      <div class="form-group"><label class="form-label">Type de page</label>
        <select name="type" class="form-select">
          @foreach ($types as $key => $t)<option value="{{ $key }}" @selected(old('type','about')===$key)>{{ $t['emoji'] }} {{ $t['label'] }}</option>@endforeach
        </select>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Titre (optionnel)</label><input type="text" name="titre" value="{{ old('titre') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Langue</label>
          <select name="langue" class="form-select">
            @foreach (['fr'=>'Français','ar_marocain'=>'Arabe marocain','ar_standard'=>'Arabe standard','ar_golfe'=>'Arabe Golfe','en'=>'English'] as $v=>$lbl)
            <option value="{{ $v }}" @selected(old('langue','fr')===$v)>{{ $lbl }}</option>@endforeach
          </select></div>
      </div>
      <div class="form-group"><label class="form-label">Instructions spécifiques (optionnel)</label>
        <textarea name="instructions_libres" rows="3" class="form-control" placeholder="Ex : mentionner notre garantie 30 jours, ton chaleureux…">{{ old('instructions_libres') }}</textarea>
      </div>
      <div class="form-group" style="display:flex;gap:24px">
        <label class="toggle-label" style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="show_in_header_menu" value="1"> Menu en-tête</label>
        <label class="toggle-label" style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="show_in_footer_menu" value="1" {{ old('show_in_footer_menu') ? 'checked' : '' }}> Pied de page</label>
      </div>
      <div class="form-group"><label class="form-label">Statut</label>
        <select name="status" class="form-select">
          <option value="active" @selected(old('status','active')==='active')>Publié</option>
          <option value="draft" @selected(old('status')==='draft')>Brouillon</option>
        </select>
      </div>
      <button class="btn btn-primary btn-lg">✨ Générer et créer la page</button>
      <p class="form-hint" style="margin-top:8px">La génération utilise OpenAI. Sans clé valide, une page de base modifiable est créée.</p>
    </form>
  </div>
</div>
@endsection
