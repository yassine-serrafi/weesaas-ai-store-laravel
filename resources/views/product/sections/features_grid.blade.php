@php
    $features = $product->features ?: [];
    if (empty($features)) return;
    $titre = $product->features_title ?: (langKey($lang_code) === 'ar' ? 'مميزات المنتج' : ($lang_code === 'en' ? 'Why choose this product?' : 'Pourquoi choisir ce produit ?'));
    $iconFallbacks = ['zap','shield','truck','heart','star','sparkles','rocket','diamond','fire','check-circle'];
@endphp
<section class="features-section reveal" id="features-section">
  <div class="features-inner">
    <h2 class="section-title">{{ $titre }}</h2>
    <div class="features-grid">
      @foreach ($features as $i => $feat)
      @php
        $fIcon = $feat['emoji'] ?? $feat['icone'] ?? $feat['icon'] ?? $iconFallbacks[$i % count($iconFallbacks)];
      @endphp
      <div class="feature-card">
        <div class="feature-icon" style="background:var(--orange-bg)">
          <x-icon :name="$fIcon" :size="28" class="feature-svg" />
        </div>
        <h3 class="feature-title">{{ $feat['titre'] ?? $feat['title'] ?? '' }}</h3>
        <p class="feature-text">{{ $feat['texte'] ?? $feat['text'] ?? $feat['desc'] ?? '' }}</p>
      </div>
      @endforeach
    </div>
  </div>
</section>
