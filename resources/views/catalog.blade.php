@extends('layouts.front')

@section('content')
<style>
.catalog-hero{background:linear-gradient(135deg,#fffaf5 0%,#fff0e5 100%);border-radius:20px;padding:40px 20px;text-align:center;margin-bottom:40px;box-shadow:0 4px 24px rgba(255,107,0,0.04)}
.catalog-hero-title{font-size:32px;font-weight:800;color:#111;margin-bottom:12px}
.catalog-hero-subtitle{font-size:16px;color:#666;max-width:500px;margin:0 auto 24px;line-height:1.5}
.catalog-search-pill{display:flex;align-items:center;max-width:480px;margin:0 auto;background:#fff;border-radius:50px;padding:6px 6px 6px 20px;box-shadow:0 8px 32px rgba(0,0,0,0.06);transition:box-shadow .3s;border:1px solid #ffebe0}
.catalog-search-pill:focus-within{box-shadow:0 8px 32px rgba(255,107,0,0.15);border-color:#FF6B00}
.catalog-search-input{flex:1;border:none;background:transparent;font-size:15px;outline:none;{{ $lang_dir === 'rtl' ? 'padding-left:16px;' : 'padding-right:16px;' }}}
.catalog-search-btn{background:#FF6B00;color:#fff;border:none;border-radius:40px;padding:10px 24px;font-weight:600;font-size:14px;cursor:pointer;transition:transform .2s,background .2s}
.catalog-search-btn:hover{background:#e66000;transform:scale(1.02)}
.premium-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:24px}
.premium-card{text-decoration:none;display:flex;flex-direction:column;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.04);transition:transform .3s ease,box-shadow .3s ease;border:1px solid #f2f0eb;position:relative;opacity:0;transform:translateY(20px);animation:fadeInUp .5s ease forwards}
.premium-card:hover{transform:translateY(-6px);box-shadow:0 12px 32px rgba(255,107,0,0.12);border-color:#ffe8d6}
.premium-card-img-wrap{position:relative;aspect-ratio:1;overflow:hidden;background:#f9f9f9}
.premium-card-img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .5s cubic-bezier(.25,.46,.45,.94)}
.premium-card:hover .premium-card-img{transform:scale(1.08)}
.premium-stars{color:#FFB800;font-size:13px;margin-bottom:6px;letter-spacing:1px}
.premium-buy-tag{position:absolute;bottom:12px;{{ $lang_dir === 'rtl' ? 'left' : 'right' }}:12px;background:rgba(255,255,255,0.95);backdrop-filter:blur(4px);color:#FF6B00;font-size:12px;font-weight:700;padding:6px 12px;border-radius:20px;opacity:0;transform:translateY(10px);transition:all .3s ease;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.premium-card:hover .premium-buy-tag{opacity:1;transform:translateY(0)}
@keyframes fadeInUp{to{opacity:1;transform:translateY(0)}}
</style>

<section style="padding:0 16px 60px;max-width:1140px;margin:32px auto" dir="{{ $lang_dir }}">

  <div class="catalog-hero">
    <h1 class="catalog-hero-title">{{ $L['titre'] }}</h1>
    <p class="catalog-hero-subtitle">
      {{ $lang_code === 'ar' ? 'اكتشف أفضل المنتجات التي اخترناها لك بعناية فائقة' : 'Découvrez nos meilleurs produits soigneusement sélectionnés pour vous.' }}
    </p>
    <form method="get" class="catalog-search-pill">
      @if ($pays)<input type="hidden" name="pays" value="{{ $pays }}">@endif
      <input type="text" name="q" class="catalog-search-input" value="{{ $search }}" placeholder="{{ $L['rechercher'] }}">
      <button type="submit" class="catalog-search-btn">{{ $L['btn_search'] }}</button>
    </form>
    @if ($search || $pays)
      <div style="margin-top:16px;font-size:13px;color:#888;">
        {{ $lang_code === 'ar' ? 'نتائج البحث عن' : 'Résultats pour' }} : <strong>{{ $search ?: $pays }}</strong>
        — <a href="{{ url('/') }}" style="color:#FF6B00;text-decoration:underline">{{ $L['tout'] }}</a>
      </div>
    @endif
  </div>

  @if ($products->isEmpty())
  <div style="text-align:center;padding:60px 0;color:#888;background:#fff;border-radius:24px;border:1px dashed #ddd">
    <div style="font-size:48px;margin-bottom:16px">📭</div>
    <div style="font-size:16px;font-weight:500">{{ $L['vide'] }}</div>
  </div>
  @else
  <div class="premium-grid">
    @foreach ($products as $i => $p)
    <a href="{{ site_url('pages/' . $p->slug . '/') }}" class="premium-card" style="animation-delay: {{ $i * 0.05 }}s">
      <div class="premium-card-img-wrap">
        @if ($p->mainImage)
          <img src="{{ $p->mainImage->url }}" alt="{{ $p->nom_produit }}" class="premium-card-img" loading="lazy">
        @else
          <div style="width:100%;height:100%;background:#f0ede8;display:flex;align-items:center;justify-content:center;font-size:40px">📦</div>
        @endif
        @if ($p->badge_hero)
        <span style="position:absolute;top:12px;{{ $lang_dir === 'rtl' ? 'right' : 'left' }}:12px;background:#FF6B00;color:#fff;font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;box-shadow:0 2px 8px rgba(255,107,0,0.3);z-index:2">{{ $p->badge_hero }}</span>
        @endif
        @if ($p->stock_dispo <= 0)
        <div style="position:absolute;inset:0;background:rgba(255,255,255,0.7);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;color:#333;font-weight:800;font-size:14px;letter-spacing:1px;z-index:3">{{ $L['epuise'] }}</div>
        @endif
        <div class="premium-buy-tag">{{ $lang_code === 'ar' ? 'عرض التفاصيل' : 'Découvrir' }} →</div>
      </div>
      <div style="padding:16px 20px;display:flex;flex-direction:column;flex:1;{{ $lang_dir === 'rtl' ? 'text-align:right' : '' }}">
        <div class="premium-stars" style="display:flex;gap:2px;margin-bottom:6px">
          @for ($s = 0; $s < 5; $s++)<x-icon name="star-solid" :size="12" style="color:#FFB800" />@endfor
        </div>
        <div style="font-size:15px;font-weight:700;color:#222;line-height:1.4;margin-bottom:8px">{{ $p->nom_produit }}</div>
        <div style="margin-top:auto;display:flex;align-items:center;gap:10px;flex-direction:{{ $lang_dir === 'rtl' ? 'row-reverse' : 'row' }}">
          <span style="font-size:18px;font-weight:800;color:#FF6B00">{{ number_format((float)$p->prix,0,',',' ') }} {{ $p->symbole_devise }}</span>
          @if ((float)$p->prix_barre > (float)$p->prix)
          <span style="font-size:13px;color:#a0a0a0;text-decoration:line-through;font-weight:500">{{ number_format((float)$p->prix_barre,0,',',' ') }}</span>
          @endif
        </div>
      </div>
    </a>
    @endforeach
  </div>
  @endif

  @if ($products->hasPages())
  <div style="display:flex;justify-content:center;gap:8px;margin-top:40px;flex-wrap:wrap">
    @foreach (range(1, $products->lastPage()) as $i)
    <a href="{{ $products->url($i) }}"
       style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:12px;font-size:14px;font-weight:600;text-decoration:none;transition:all .2s;{{ $i === $products->currentPage() ? 'background:#FF6B00;color:#fff;box-shadow:0 4px 12px rgba(255,107,0,0.3);border:none;' : 'background:#fff;border:1.5px solid #e5e5e0;color:#444;' }}">{{ $i }}</a>
    @endforeach
  </div>
  @endif
</section>
@endsection
