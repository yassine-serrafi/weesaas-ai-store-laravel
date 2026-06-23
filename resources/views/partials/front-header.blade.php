@php
    $lang_code = $lang_code ?? 'fr';
    $_lk = langKey($lang_code);
    $catLabels = ['fr' => 'Catalogue', 'en' => 'Catalog', 'ar' => 'المتجر'];
    $catLbl = $catLabels[$_lk] ?? 'Catalogue';
@endphp

<header class="site-header">
  <div class="header-inner">
    <a href="{{ site_url() }}" class="header-logo">
      @if (!empty($shop['logo_url']))
        <img src="{{ $shop['logo_url'] }}" alt="{{ $shop['nom_boutique'] ?? '' }}">
      @else
        {{ $shop['nom_boutique'] ?? 'Boutique' }}
      @endif
    </a>
    <nav class="header-nav" aria-label="Navigation principale">
      @forelse ($headerMenus ?? [] as $m)
        <a href="{{ menu_url($m->url) }}" class="nav-link">{{ $m->labelFor($_lk) }}</a>
      @empty
        <a href="{{ site_url() }}" class="nav-link">{{ $catLbl }}</a>
      @endforelse
    </nav>
    <div class="header-right">
      @if (!empty($shop['tel_whatsapp']))
      <a href="https://wa.me/{{ preg_replace('/\D/', '', $shop['tel_whatsapp']) }}" target="_blank" rel="noopener" class="header-cta">
        <x-icon name="whatsapp" :size="16" style="margin-right:4px" /> WhatsApp
      </a>
      @endif
      <button class="hamburger-btn" aria-label="Menu" id="hamburger-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
      </button>
    </div>
  </div>
</header>

<div class="mobile-nav" id="mobile-nav">
  <div class="mobile-nav-overlay" onclick="toggleMobileNav(false)"></div>
  <nav class="mobile-nav-drawer">
    <div class="mobile-nav-head">
      <span style="font-size:16px;font-weight:700">{{ $shop['nom_boutique'] ?? 'Menu' }}</span>
      <button class="mobile-nav-close" onclick="toggleMobileNav(false)">✕</button>
    </div>
    <div class="mobile-nav-links">
      @forelse ($headerMenus ?? [] as $m)
        <a href="{{ menu_url($m->url) }}">{{ $m->labelFor($_lk) }}</a>
      @empty
        <a href="{{ site_url() }}">{{ $catLbl }}</a>
      @endforelse
    </div>
  </nav>
</div>
