@extends('admin.layouts.app')
@section('title', 'Paramètres')

@php
  function sv($shop, $k, $d = '') { return $shop[$k] ?? $d; }
  $couleur = sv($shop, 'couleur_principale', '#FF6B00') ?: '#FF6B00';
  $livGratuite = sv($shop, 'livraison_gratuite_defaut', '0') === '1';
@endphp

@section('content')
<div class="page-header"><div><div class="page-title">Paramètres</div></div></div>

<div class="tabs" id="settings-tabs">
  <button type="button" class="tab-btn active" data-tab="t-boutique">Boutique</button>
  <button type="button" class="tab-btn" data-tab="t-localisation">Localisation</button>
  <button type="button" class="tab-btn" data-tab="t-livraison">Livraison</button>
  <button type="button" class="tab-btn" data-tab="t-contact">Contact &amp; Réseaux</button>
  <button type="button" class="tab-btn" data-tab="t-marketing">Marketing</button>
  <button type="button" class="tab-btn" data-tab="t-smtp">Email (SMTP)</button>
  <button type="button" class="tab-btn" data-tab="t-ia">Intelligence (IA)</button>
</div>

<form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" style="max-width:800px">
  @csrf @method('PUT')

  @if ($errors->any())
  <div class="alert alert-error">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
  @endif

  {{-- BOUTIQUE --}}
  <div class="tab-panel active" id="t-boutique">
    <div class="card"><div class="card-body">
      <div class="form-group"><label class="form-label">Nom de la boutique</label><input type="text" name="nom_boutique" value="{{ sv($shop,'nom_boutique') }}" class="form-control"></div>
      <div class="form-group"><label class="form-label">Description (SEO / footer)</label><textarea name="description_boutique" class="form-control">{{ sv($shop,'description_boutique') }}</textarea></div>
      <div class="form-group"><label class="form-label">Texte du footer</label><input type="text" name="footer_desc" value="{{ sv($shop,'footer_desc') }}" class="form-control"></div>
      <div class="form-group"><label class="form-label">Adresse de la boutique</label><input type="text" name="adresse_boutique" value="{{ sv($shop,'adresse_boutique') }}" class="form-control"></div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Logo</label>
          @if (sv($shop,'logo_url'))<div style="margin-bottom:8px"><img src="{{ sv($shop,'logo_url') }}" alt="logo" style="max-height:48px;max-width:160px;border:1px solid var(--border-light);border-radius:6px;padding:4px;background:#fff"></div>@endif
          <input type="file" name="logo" accept="image/*" class="form-control">
          <input type="text" name="logo_url" value="{{ sv($shop,'logo_url') }}" class="form-control" placeholder="…ou collez une URL" style="margin-top:8px">
          <div class="form-hint">Uploadez un fichier (≤ 2 Mo) ou collez une URL. Le fichier remplace l'URL.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Favicon</label>
          @if (sv($shop,'favicon_url'))<div style="margin-bottom:8px"><img src="{{ sv($shop,'favicon_url') }}" alt="favicon" style="height:32px;width:32px;border:1px solid var(--border-light);border-radius:6px;padding:2px;background:#fff"></div>@endif
          <input type="file" name="favicon" accept=".png,.ico,.svg,.jpg,.webp" class="form-control">
          <input type="text" name="favicon_url" value="{{ sv($shop,'favicon_url') }}" class="form-control" placeholder="…ou collez une URL" style="margin-top:8px">
          <div class="form-hint">PNG / ICO / SVG (≤ 1 Mo) ou URL.</div>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Couleur principale</label>
        <div class="color-input-wrap">
          <span class="color-swatch"><input type="color" id="couleur_principale" name="couleur_principale" value="{{ $couleur }}" oninput="document.getElementById('couleur_hex').value=this.value"></span>
          <input type="text" id="couleur_hex" class="form-control" value="{{ $couleur }}" readonly style="max-width:130px">
        </div>
      </div>
    </div></div>
  </div>

  {{-- LOCALISATION --}}
  <div class="tab-panel" id="t-localisation">
    <div class="card"><div class="card-body">
      <div class="form-row-3">
        <div class="form-group"><label class="form-label">Langue par défaut</label>
          <select name="langue_defaut" class="form-select">
            @foreach (['fr'=>'Français','ar_marocain'=>'Arabe marocain','ar_standard'=>'Arabe standard','ar_golfe'=>'Arabe Golfe','en'=>'English'] as $v=>$l)
            <option value="{{ $v }}" @selected(sv($shop,'langue_defaut','fr')===$v)>{{ $l }}</option>@endforeach
          </select></div>
        <div class="form-group"><label class="form-label">Pays par défaut</label>
          <select name="pays_defaut" class="form-select">
            @foreach (['maroc'=>'Maroc','saudi'=>'Arabie Saoudite','uae'=>'UAE','france'=>'France','belgique'=>'Belgique'] as $v=>$l)
            <option value="{{ $v }}" @selected(sv($shop,'pays_defaut','maroc')===$v)>{{ $l }}</option>@endforeach
          </select></div>
        <div class="form-group"><label class="form-label">Devise par défaut</label>
          <select name="devise_defaut" class="form-select">
            @foreach (['MAD','SAR','AED','EUR','USD','GBP'] as $v)
            <option value="{{ $v }}" @selected(sv($shop,'devise_defaut','MAD')===$v)>{{ $v }}</option>@endforeach
          </select></div>
      </div>
    </div></div>
  </div>

  {{-- LIVRAISON --}}
  <div class="tab-panel" id="t-livraison">
    <div class="card"><div class="card-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Frais de livraison par défaut</label><input type="number" step="0.01" name="frais_livraison_defaut" value="{{ sv($shop,'frais_livraison_defaut','0') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Délai de livraison par défaut</label><input type="text" name="delai_livraison_defaut" value="{{ sv($shop,'delai_livraison_defaut') }}" class="form-control" placeholder="2-4 jours ouvrés"></div>
      </div>
      <div class="form-group">
        <label class="toggle-wrap">
          <span class="toggle">
            <input type="checkbox" name="livraison_gratuite_defaut" value="1" @checked($livGratuite)>
            <span class="toggle-track"></span><span class="toggle-thumb"></span>
          </span>
          <span class="toggle-label">Livraison gratuite par défaut</span>
        </label>
      </div>
    </div></div>
  </div>

  {{-- CONTACT & RÉSEAUX --}}
  <div class="tab-panel" id="t-contact">
    <div class="card"><div class="card-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Email de contact</label><input type="email" name="email_contact" value="{{ sv($shop,'email_contact') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Email notifications commandes</label><input type="email" name="email_notif_admin" value="{{ sv($shop,'email_notif_admin') }}" class="form-control"></div>
      </div>
      <div class="form-group"><label class="form-label">Téléphone WhatsApp</label><input type="text" name="tel_whatsapp" value="{{ sv($shop,'tel_whatsapp') }}" class="form-control" placeholder="+212…"></div>
      <div class="form-row-3">
        <div class="form-group"><label class="form-label">Facebook</label><input type="text" name="facebook" value="{{ sv($shop,'facebook') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">Instagram</label><input type="text" name="instagram" value="{{ sv($shop,'instagram') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">TikTok</label><input type="text" name="tiktok" value="{{ sv($shop,'tiktok') }}" class="form-control"></div>
      </div>
    </div></div>
  </div>

  {{-- MARKETING --}}
  <div class="tab-panel" id="t-marketing">
    <div class="card"><div class="card-body">
      <div class="form-group"><label class="form-label">Texte de la barre promo</label><input type="text" name="promo_bar_text" value="{{ sv($shop,'promo_bar_text') }}" class="form-control"></div>
      <div class="form-row-3">
        <div class="form-group"><label class="form-label">Google Analytics ID</label><input type="text" name="ga_id" value="{{ sv($shop,'ga_id') }}" class="form-control" placeholder="G-XXXXX"></div>
        <div class="form-group"><label class="form-label">Facebook Pixel ID</label><input type="text" name="fb_pixel_id" value="{{ sv($shop,'fb_pixel_id') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">TikTok Pixel ID</label><input type="text" name="tt_pixel_id" value="{{ sv($shop,'tt_pixel_id') }}" class="form-control"></div>
      </div>
    </div></div>
  </div>

  {{-- SMTP --}}
  <div class="tab-panel" id="t-smtp">
    <div class="card"><div class="card-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">SMTP Host</label><input type="text" name="smtp_host" value="{{ sv($shop,'smtp_host') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">SMTP Port</label><input type="text" name="smtp_port" value="{{ sv($shop,'smtp_port') }}" class="form-control" placeholder="587"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">SMTP User</label><input type="text" name="smtp_user" value="{{ sv($shop,'smtp_user') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">SMTP Password {{ !empty($shop['smtp_pass']) ? '🔒' : '' }}</label><input type="password" name="smtp_pass" value="" autocomplete="new-password" placeholder="{{ !empty($shop['smtp_pass']) ? '•••••••• (configuré)' : 'Non configuré' }}" class="form-control"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">From (expéditeur)</label><input type="text" name="smtp_from" value="{{ sv($shop,'smtp_from') }}" class="form-control"></div>
        <div class="form-group"><label class="form-label">From Name</label><input type="text" name="smtp_from_name" value="{{ sv($shop,'smtp_from_name') }}" class="form-control"></div>
      </div>
    </div></div>
  </div>

  {{-- IA --}}
  <div class="tab-panel" id="t-ia">
    <div class="card">
      <div class="card-header">
        <div><span class="card-title">Clés API (chiffrées)</span><span class="card-subtitle">Laissez vide pour conserver la valeur actuelle</span></div>
        <button type="submit" form="reset-api-form" class="btn btn-danger btn-sm"
                onclick="return confirm('Réinitialiser les clés API Gemini et OpenAI ? Il faudra les ressaisir.')">↺ Réinitialiser les clés API</button>
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Clé API Gemini {{ !empty($shop['gemini_api_key']) ? '🔒' : '' }}</label><input type="password" name="gemini_api_key" value="" autocomplete="new-password" placeholder="{{ !empty($shop['gemini_api_key']) ? '•••••••• (configuré)' : 'Non configuré' }}" class="form-control"></div>
          <div class="form-group"><label class="form-label">Clé API OpenAI {{ !empty($shop['openai_api_key']) ? '🔒' : '' }}</label><input type="password" name="openai_api_key" value="" autocomplete="new-password" placeholder="{{ !empty($shop['openai_api_key']) ? '•••••••• (configuré)' : 'Non configuré' }}" class="form-control"></div>
        </div>
      </div>
    </div>
  </div>

  <button class="btn btn-primary btn-lg" style="margin-top:16px">Enregistrer les paramètres</button>
</form>

{{-- Formulaire séparé (hors du form principal) pour la réinitialisation des clés API --}}
<form id="reset-api-form" method="post" action="{{ route('admin.settings.reset-api') }}" style="display:none">@csrf</form>
@endsection

@push('scripts')
<script>
document.querySelectorAll('#settings-tabs .tab-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.querySelectorAll('#settings-tabs .tab-btn').forEach(function(b){ b.classList.remove('active'); });
    document.querySelectorAll('.tab-panel').forEach(function(p){ p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');
  });
});
</script>
@endpush
