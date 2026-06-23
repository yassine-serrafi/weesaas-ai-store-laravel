@php
    $img0 = $images[0] ?? null;
    // Stock : illimité (suivi désactivé) → toujours dispo ; sinon stock réel (0 = épuisé).
    $in_stock  = $product->is_available;
    $stock_qty = $product->stock_untracked ? 99 : (int) $product->stock_dispo;
    $pct_vendu = min(90, max(30, 100 - $stock_qty * 2));

    $groupes = $product->attrs_json['groupes'] ?? [];
    $attrs = [];
    $attrs_labels = [];
    if (!empty($groupes)) {
        foreach ($groupes as $g) {
            $gid = $g['id'] ?? 'option';
            $attrs[$gid] = $g['valeurs'] ?? [];
            $attrs_labels[$gid] = $g['label'] ?? ucfirst($gid);
        }
    } else {
        $decoded = (array) $product->attributs;
        if ($decoded && ! isset($decoded[0])) {
            $attrs = $decoded;
            foreach (array_keys($decoded) as $k) $attrs_labels[$k] = ucfirst($k);
        }
    }

    $prix = (float) $product->prix;
    $prix_barre = (float) $product->prix_barre;
    $devise_sym = $product->symbole_devise ?: 'MAD';
    $devise_pos = $product->position_symbole ?: 'apres';
    $remise = $prix_barre > 0 ? round(($prix_barre - $prix) / $prix_barre * 100) : 0;
    $rating = (float) ($product->rating ?? 4.8);
    $reviews = (int) ($product->reviews_count ?? 0);
    $livraison_free = (bool) $product->livraison_gratuite;
    $delai_livraison = $product->delai_livraison ?: ($lang_code === 'ar' ? '48-72 ساعة' : '48-72h');

    $LH = [
        'fr' => ['urgency'=>'Offre limitée — Commandez maintenant !','reviews'=>'avis','vendu'=>'vendu','name'=>'Votre nom','saved'=>'Enregistré !'],
        'ar' => ['urgency'=>'عرض محدود — اطلب الآن !','reviews'=>'تقييم','vendu'=>'تم بيعه','name'=>'اسمك الكامل','saved'=>'تم التسجيل بنجاح !'],
        'en' => ['urgency'=>'Limited offer — Order now!','reviews'=>'reviews','vendu'=>'sold','name'=>'Your full name','saved'=>'Saved successfully!'],
    ];
    $currLH = $LH[$lang_code] ?? $LH['fr'];
@endphp

@if ($product->urgency_actif)
<div class="urgency-bar">
  <div class="urgency-bar-inner">
    <div>
      <div class="urgency-text" style="display:flex;align-items:center;gap:8px">
        <x-icon name="zap" :size="18" class="text-orange-500" />
        {{ $product->urgency_text ?: $currLH['urgency'] }}
      </div>
      @if ($product->urgency_sub)<div class="urgency-sub">{{ $product->urgency_sub }}</div>@endif
    </div>
    <div class="timer-wrap" id="timer-wrap" data-slug="{{ $product->slug }}" data-hours="{{ (int) ($product->timer_heures ?: 24) }}">
      <div class="timer-block timer-h">--</div><div class="timer-sep">:</div>
      <div class="timer-block timer-m">--</div><div class="timer-sep">:</div>
      <div class="timer-block timer-s">--</div>
    </div>
  </div>
</div>
@endif

<section class="hero" id="hero-section">
  <div class="hero-inner">
    <div class="hero-right">
      @if ($img0)
      <div class="hero-image-wrap">
        @if ($prix_barre > 0 && $remise > 0)<div class="hero-img-promo-badge">-{{ $remise }}%</div>@endif
        <img src="{{ $img0->url }}" alt="{{ $product->nom_produit }}" class="hero-main-img" id="hero-main-img"
             data-lightbox data-src="{{ $img0->url }}" loading="eager" fetchpriority="high" width="600" height="600">
        @if (count($images) > 1)
        <div class="hero-thumbnails">
          @foreach ($images as $i => $img)
          <img src="{{ $img->url }}" alt="" class="hero-thumb {{ $i === 0 ? 'active' : '' }}" data-full="{{ $img->url }}" loading="lazy" width="64" height="64">
          @endforeach
        </div>
        @endif
      </div>
      @else
      <div class="hero-image-wrap">
        <div style="aspect-ratio:1;background:#f5f5f3;border-radius:16px;display:flex;align-items:center;justify-content:center;color:#999">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </div>
      </div>
      @endif
    </div>

    <div class="hero-left">
      @if ($product->badge_hero)
      <div class="hero-badge"><x-icon name="sparkles" :size="14" class="mr-1" /> {{ $product->badge_hero }}</div>
      @endif

      @if ($reviews > 0)
      <div class="hero-rating">
        <div class="stars" style="display:flex;gap:2px;color:#FFB800">
          @for ($s = 0; $s < 5; $s++)<x-icon name="star-solid" :size="14" />@endfor
        </div>
        <span class="rating-text" style="margin-{{ $lang_dir === 'rtl' ? 'right' : 'left' }}:8px">
          {{ number_format($rating, 1) }} ({{ $reviews }}+ {{ $currLH['reviews'] }})
        </span>
      </div>
      @endif

      <h1 class="product-title hero-title">{{ $product->nom_produit }}</h1>

      @if ($product->texte_hero)<p class="hero-desc">{!! nl2br(e($product->texte_hero)) !!}</p>@endif
      @if ($product->texte_hero2)<p class="hero-desc" style="margin-top:-10px;font-size:14px;color:var(--text-light)">{!! nl2br(e($product->texte_hero2)) !!}</p>@endif

      <div class="hero-price-wrap">
        <span class="price-main">{{ affPrix($prix, $devise_sym, $devise_pos) }}</span>
        @if ($prix_barre > 0)
        <span class="price-crossed">{{ affPrix($prix_barre, $devise_sym, $devise_pos) }}</span>
        <span class="price-badge">-{{ $remise }}%</span>
        @endif
      </div>
      <div class="hero-delivery" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
        @if ($livraison_free)
        <span style="display:inline-flex;align-items:center;gap:4px;font-weight:700">
          <x-icon name="truck" :size="18" />
          {{ langKey($lang_code) === 'ar' ? 'توصيل مجاني' : ($lang_code === 'en' ? 'Free delivery' : 'Livraison gratuite') }}
        </span> —
        @endif
        <span>{{ langKey($lang_code) === 'ar' ? 'تسليم في ' : ($lang_code === 'en' ? 'Delivery in ' : 'Livraison en ') }}
          <b style="color:var(--primary)">{{ $delai_livraison }}</b></span>
      </div>

      @foreach ($attrs as $group => $vals)
      @if (!empty($vals))
      <div class="attr-section">
        <div class="attr-label">
          {{ $attrs_labels[$group] ?? ucfirst($group) }}
          <span class="attr-selected" data-group="{{ $group }}">{{ $vals[0] ?? '' }}</span>
          <input type="hidden" id="attr-hidden-{{ $group }}" name="attr_{{ $group }}" value="{{ $vals[0] ?? '' }}">
        </div>
        <div class="attr-grid">
          @foreach ($vals as $i => $val)
          <button class="attr-btn {{ $i === 0 ? 'selected' : '' }}" data-group="{{ $group }}" data-val="{{ $val }}" type="button">{{ $val }}</button>
          @endforeach
        </div>
      </div>
      @endif
      @endforeach

      <div class="attr-label" style="margin-top:12px">{{ $lang_code === 'ar' ? 'الكمية' : 'Quantité' }}</div>
      <div class="qty-wrap" style="margin-bottom:16px">
        <button class="qty-btn" data-delta="-1" type="button">−</button>
        <div class="qty-val" id="qty-val" data-min="1" data-max="{{ $stock_qty ?: 99 }}">1</div>
        <button class="qty-btn" data-delta="1" type="button">+</button>
        <input type="hidden" id="qty-input" name="quantite" value="1">
      </div>

      @if ($in_stock)
      <button class="btn-commander" onclick="document.getElementById('order-section')?.scrollIntoView({behavior:'smooth'})">
        <x-icon name="shopping-bag" :size="20" />
        {{ $lang_code === 'ar' ? 'اطلب الآن' : ($lang_code === 'en' ? 'Order now' : 'Commander maintenant') }}
      </button>
      @if (!empty($shop['tel_whatsapp']))
      <a href="https://wa.me/{{ preg_replace('/\D/','',$shop['tel_whatsapp']) }}?text={{ urlencode('Bonjour, je souhaite commander: ' . $product->nom_produit) }}" target="_blank" class="btn-whatsapp">
        <x-icon name="whatsapp" :size="20" /> WhatsApp
      </a>
      @endif
      @else
      <button class="btn-demande-info" data-open-modal="modal-demande-info">
        <x-icon name="mail" :size="18" />
        {{ $lang_code === 'ar' ? 'أعلمني عند التوفر' : ($lang_code === 'en' ? 'Notify me when available' : "M'alerter quand disponible") }}
      </button>
      @endif

      <div class="hero-trust">
        <div class="trust-item"><x-icon name="shield" :size="14" /> {{ $lang_code === 'ar' ? 'دفع عند الاستلام' : ($lang_code === 'en' ? 'Cash on delivery' : 'Paiement à la livraison') }}</div>
        <div class="trust-item"><x-icon name="truck" :size="14" /> {{ $lang_code === 'ar' ? 'توصيل سريع' : ($lang_code === 'en' ? 'Fast delivery' : 'Livraison rapide') }}</div>
        <div class="trust-item"><x-icon name="refresh" :size="14" /> {{ $lang_code === 'ar' ? 'ضمان الاسترجاع' : ($lang_code === 'en' ? 'Return guarantee' : 'Retour garanti') }}</div>
        <div class="trust-item"><x-icon name="phone" :size="14" /> {{ $lang_code === 'ar' ? 'دعم العملاء' : ($lang_code === 'en' ? 'Customer support' : 'Support client') }}</div>
      </div>
    </div>
  </div>
</section>

<input type="hidden" id="product-id" value="{{ (int) $product->id }}">
<input type="hidden" id="product-name" value="{{ $product->nom_produit }}">
<input type="hidden" id="product-price" value="{{ (float) $product->prix }}">
<input type="hidden" id="product-currency" value="{{ $product->devise ?: 'MAD' }}">
<input type="hidden" id="prix-base" value="{{ (float) $product->prix }}">
<input type="hidden" id="devise-symbole" value="{{ $devise_sym }}">
<input type="hidden" id="devise-position" value="{{ $devise_pos }}">
<input type="hidden" id="pays-produit" value="{{ $product->pays_effectif }}">
<input type="hidden" id="base-url" value="{{ site_url() }}">

<div class="modal-overlay" id="modal-demande-info">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">📩 {{ $lang_code === 'ar' ? 'أعلمني' : "M'alerter" }}</span>
      <button class="modal-close" data-close-modal="modal-demande-info">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:14px;margin-bottom:16px;color:#555">
        {{ $lang_code === 'ar' ? 'سيتم إعلامك فور توفر المنتج' : 'Laissez votre numéro, on vous prévient dès que le produit est disponible.' }}
      </p>
      <form id="form-demande-info">
        @csrf
        <input type="hidden" name="product_id" value="{{ (int) $product->id }}">
        <div class="form-field">
          <label>{{ $lang_code === 'ar' ? 'الاسم' : 'Nom' }}</label>
          <input type="text" class="form-input" name="nom" required placeholder="{{ $currLH['name'] }}">
        </div>
        <div class="form-field">
          <label>{{ $lang_code === 'ar' ? 'الهاتف' : 'Téléphone' }}</label>
          <input type="tel" class="form-input" name="telephone" required placeholder="06XXXXXXXX">
        </div>
        <button type="submit" class="btn-submit" style="margin-top:4px">{{ $lang_code === 'ar' ? 'أرسل' : "M'alerter" }}</button>
      </form>
    </div>
  </div>
</div>
<script>
(function(){
  var f = document.getElementById('form-demande-info');
  if (f) f.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch(BASE_URL + 'demande-info', {method:'POST', body:new FormData(f), headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json()})
      .then(function(d){
        if (d.success) {
          f.innerHTML = '<p style="text-align:center;color:#16A34A;padding:20px">' + @json($currLH['saved']) + '</p>';
          setTimeout(function(){ if (window.closeModal) closeModal('modal-demande-info') }, 1500);
        }
      });
  });
})();
</script>
