@extends('layouts.front')

@section('content')
<div class="merci-wrap">
  <div class="merci-card">
    <div class="merci-check">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    </div>

    <h1 class="merci-title">{{ $lang_code === 'ar' ? '🎉 شكراً لك!' : '🎉 Merci pour votre commande !' }}</h1>
    <p class="merci-sub">
      {{ $lang_code === 'ar' ? 'تم تسجيل طلبك بنجاح. سيتم التواصل معك قريباً للتأكيد.' : 'Votre commande a bien été enregistrée. Notre équipe vous contactera bientôt pour confirmer.' }}
    </p>

    <div class="merci-ref">Réf: {{ $order->reference }}</div>

    <div class="merci-details">
      <div class="merci-row"><span class="merci-row-key">{{ $lang_code === 'ar' ? 'المنتج' : 'Produit' }}</span><span class="merci-row-val">{{ $order->product->nom_produit }}</span></div>
      @foreach (($order->attributs ?: []) as $k => $v)
      <div class="merci-row"><span class="merci-row-key">{{ ucfirst($k) }}</span><span class="merci-row-val">{{ $v }}</span></div>
      @endforeach
      <div class="merci-row"><span class="merci-row-key">{{ $lang_code === 'ar' ? 'الكمية' : 'Quantité' }}</span><span class="merci-row-val">{{ (int) $order->quantite }}</span></div>
      <div class="merci-row"><span class="merci-row-key">{{ $lang_code === 'ar' ? 'الاسم' : 'Client' }}</span><span class="merci-row-val">{{ $order->nom_client }}</span></div>
      <div class="merci-row"><span class="merci-row-key">{{ $lang_code === 'ar' ? 'الهاتف' : 'Téléphone' }}</span><span class="merci-row-val">{{ $order->telephone }}</span></div>
      <div class="merci-row"><span class="merci-row-key">{{ $lang_code === 'ar' ? 'المدينة' : 'Ville' }}</span><span class="merci-row-val">{{ $order->ville }}</span></div>
      <div class="merci-row merci-total">
        <span class="merci-row-key">{{ $lang_code === 'ar' ? 'المجموع' : 'Total' }}</span>
        <span class="merci-row-val money">{{ number_format((float) $order->total_ttc, 0, ',', ' ') }} {{ $order->symbole_devise }}</span>
      </div>
    </div>

    <div class="merci-actions">
      <a href="{{ site_url('suivi?ref=' . urlencode($order->reference)) }}" class="btn btn-secondary">
        📦 {{ $lang_code === 'ar' ? 'تتبع طلبي' : 'Suivre ma commande' }}
      </a>

      @if (!empty($shop['tel_whatsapp']))
      <a href="https://wa.me/{{ preg_replace('/\D/','',$shop['tel_whatsapp']) }}?text={{ urlencode('Bonjour, je viens de passer la commande ' . $order->reference) }}"
         target="_blank" class="btn-whatsapp">
        <x-icon name="whatsapp" :size="18" /> {{ $lang_code === 'ar' ? 'تواصل عبر واتساب' : 'Contacter sur WhatsApp' }}
      </a>
      @endif
    </div>
  </div>

  @if ($upsells->isNotEmpty())
  <div class="upsell-section" style="max-width:480px;margin:20px auto 0">
    <div class="upsell-badge">🔥 {{ $lang_code === 'ar' ? 'ربما يعجبك أيضاً' : 'Vous pourriez aussi aimer' }}</div>
    <div class="upsell-title" style="font-size:15px;font-weight:700;margin-bottom:8px">{{ $lang_code === 'ar' ? 'منتجات مميزة' : 'Produits populaires' }}</div>
    @foreach ($upsells as $up)
    <a href="{{ site_url('pages/' . $up->slug . '/') }}" class="upsell-card">
      @if ($up->mainImage)<img src="{{ $up->mainImage->url }}" alt="{{ $up->nom_produit }}" class="upsell-img" loading="lazy">@endif
      <div class="upsell-info">
        <div class="upsell-name">{{ $up->nom_produit }}</div>
        <div class="upsell-price">{{ number_format((float) $up->prix, 0, ',', ' ') }} {{ $up->symbole_devise }}</div>
      </div>
      <svg width="16" height="16" viewBox="0 0 20 20" fill="none" stroke="#999" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
    </a>
    @endforeach
  </div>
  @endif
</div>
@endsection
