@php
    $lang_dir   = $lang_dir   ?? 'ltr';
    $lang_code  = $lang_code  ?? 'fr';
    $product    = $product    ?? null;
    $page_title = $page_title ?? ($shop['nom_boutique'] ?? 'Boutique');
    $page_desc  = $page_desc  ?? ($shop['meta_description'] ?? '');
    $robots     = $robots     ?? 'index,follow';
    $page_url   = $page_url   ?? url()->current();
    $og_image   = $og_image   ?? site_url('assets/img/og-default.jpg');
    $og_type    = $og_type    ?? ($product ? 'product' : 'website');
    $shop_name  = $shop['nom_boutique'] ?? 'Boutique';
    $og_locale  = ['fr' => 'fr_FR', 'ar' => 'ar_AR', 'en' => 'en_US'][$lang_code] ?? 'fr_FR';
    $promo_bar  = $promo_bar  ?? ($shop['promo_bar_text'] ?? '');
    $favicon    = $shop['favicon_url'] ?? '';
    $ga_id      = $shop['ga_id'] ?? '';
    $fb_pixel   = $shop['fb_pixel_id'] ?? '';
    $tt_pixel   = $shop['tt_pixel_id'] ?? '';
@endphp
<!DOCTYPE html>
<html lang="{{ $lang_code }}" dir="{{ $lang_dir }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="{{ $product->couleur_accent ?? '#FF6B00' }}">
  <title>{{ $page_title }}</title>
  <meta name="description" content="{{ $page_desc }}">
  <meta name="robots" content="{{ $robots }}">
  <link rel="canonical" href="{{ $page_url }}">

  <meta property="og:title" content="{{ $page_title }}">
  <meta property="og:description" content="{{ $page_desc }}">
  <meta property="og:image" content="{{ $og_image }}">
  <meta property="og:url" content="{{ $page_url }}">
  <meta property="og:type" content="{{ $og_type }}">
  <meta property="og:site_name" content="{{ $shop_name }}">
  <meta property="og:locale" content="{{ $og_locale }}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $page_title }}">
  <meta name="twitter:description" content="{{ $page_desc }}">
  <meta name="twitter:image" content="{{ $og_image }}">

  @if ($product)
  <script type="application/ld+json">
  {!! json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'Product',
      'name' => $product->nom_produit,
      'image' => $og_image,
      'description' => strip_tags($product->texte_hero ?? ''),
      'sku' => $product->slug,
      'offers' => [
          '@type' => 'Offer',
          'priceCurrency' => $product->devise ?? 'MAD',
          'price' => (float) $product->prix,
          'availability' => $product->stock_dispo > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
          'seller' => ['@type' => 'Organization', 'name' => $shop_name],
      ],
      'aggregateRating' => [
          '@type' => 'AggregateRating',
          'ratingValue' => (float) ($product->rating ?? 4.8),
          'reviewCount' => (int) ($product->reviews_count ?? 120),
      ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
  </script>
  @else
  <script type="application/ld+json">
  {!! json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'WebSite',
      'name' => $shop_name,
      'url' => site_url(),
      'inLanguage' => $lang_code,
      'description' => $page_desc,
      'potentialAction' => [
          '@type' => 'SearchAction',
          'target' => [
              '@type' => 'EntryPoint',
              'urlTemplate' => site_url() . '?q={search_term_string}',
          ],
          'query-input' => 'required name=search_term_string',
      ],
      'publisher' => [
          '@type' => 'Organization',
          'name' => $shop_name,
          'url' => site_url(),
      ] + (!empty($shop['logo_url']) ? ['logo' => $shop['logo_url']] : []),
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
  </script>
  @endif

  <link rel="stylesheet" href="{{ asset_v('assets/css/front.css') }}">
  @if (!empty($product?->couleur_accent) && $product->couleur_accent !== '#FF6B00')
  <style>:root { --product-color: {{ $product->couleur_accent }}; }</style>
  @endif

  @if ($favicon)
  <link rel="icon" href="{{ $favicon }}" type="image/x-icon">
  @endif

  <script>
    var BASE_URL = @json(site_url());
    var FB_PIXEL_ID = @json($fb_pixel);
    var TT_PIXEL_ID = @json($tt_pixel);
    var GA_ID = @json($ga_id);
  </script>
  @stack('head')
</head>
<body class="{{ $product ? 'has-sticky-cta' : '' }}">

@if ($promo_bar)
<div class="header-promo-bar">{{ $promo_bar }}</div>
@endif

@include('partials.front-header')

@yield('content')

@include('partials.front-footer')

<script>var BASE_URL = @json(site_url());</script>
<script src="{{ asset_v('assets/js/front.js') }}"></script>
<script src="{{ asset_v('assets/js/cookies.js') }}"></script>
@stack('scripts')
</body>
</html>
