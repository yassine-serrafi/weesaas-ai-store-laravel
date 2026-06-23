@php
    // Choix de la langue d'affichage : arabe si direction RTL et description_ar dispo, sinon HTML (FR).
    $descActive = ($product->direction === 'rtl' && !empty($product->description_ar))
        ? $product->description_ar
        : ($product->description_html ?? '');
    if (trim((string) $descActive) === '') return;
@endphp

<section class="ws-description-section" id="description"
         style="padding:40px 20px;background:#fff;margin:20px 0;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.05);{{ $product->direction === 'rtl' ? 'direction:rtl;text-align:right;' : 'direction:ltr;text-align:left;' }}">
  <div class="ws-container" style="max-width:800px;margin:0 auto;line-height:1.8;color:#333;font-size:16px">
    <h3 style="font-size:22px;font-weight:700;margin-bottom:20px;color:{{ $product->couleur_theme ?: '#111' }}">
      {{ $product->direction === 'rtl' ? 'الوصف' : 'Description du produit' }}
    </h3>
    <div class="ws-description-content">
      {!! $descActive !!}
    </div>
  </div>
</section>

<style>
.ws-description-content p{margin-bottom:15px}
.ws-description-content ul{margin-bottom:15px;padding-inline-start:20px}
.ws-description-content li{margin-bottom:8px}
.ws-description-content strong{color:#111}
</style>
