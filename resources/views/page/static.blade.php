@extends('layouts.front')

@php
    $heroBanner = null;
    $contentBlocks = [];
    foreach ($blocks as $block) {
        if (($block['type'] ?? '') === 'hero_banner') $heroBanner = $block;
        else $contentBlocks[] = $block;
    }
@endphp

@section('content')
<style>
.sp-hero{background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);color:#fff;padding:60px 24px;text-align:center}
.sp-hero h1{font-size:clamp(1.8rem,5vw,3rem);font-weight:800;margin:0 0 12px;letter-spacing:-.02em}
.sp-hero p{font-size:1.1rem;opacity:.8;max-width:560px;margin:0 auto;line-height:1.6}
.sp-breadcrumb{max-width:860px;margin:0 auto;padding:14px 24px;font-size:13px;color:#999;display:flex;align-items:center;gap:6px}
.sp-breadcrumb a{color:#FF6B00;text-decoration:none}
.sp-content{max-width:860px;margin:0 auto;padding:0 24px 64px}
.sp-text-block{margin-bottom:48px}
.sp-text-block h2{font-size:1.5rem;font-weight:700;color:#1a1a2e;margin:0 0 16px;padding-bottom:10px;border-bottom:3px solid #FF6B00;display:inline-block}
.sp-text-block p{font-size:15px;line-height:1.8;color:#444;margin:0 0 16px}
.sp-values{margin-bottom:48px}
.sp-values h2{font-size:1.5rem;font-weight:700;color:#1a1a2e;margin:0 0 24px;text-align:center}
.sp-values-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px}
.sp-value-card{background:#f9f8f5;border:1.5px solid #ede9e0;border-radius:14px;padding:24px;text-align:center;transition:transform .2s,box-shadow .2s}
.sp-value-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.08)}
.sp-value-card .emoji{font-size:32px;display:block;margin-bottom:10px}
.sp-value-card h3{font-size:15px;font-weight:700;margin:0 0 8px;color:#1a1a2e}
.sp-value-card p{font-size:13px;color:#666;margin:0;line-height:1.6}
.sp-stats{background:linear-gradient(135deg,#FF6B00 0%,#e55a00 100%);border-radius:16px;padding:32px 24px;margin-bottom:48px;display:flex;justify-content:space-around;flex-wrap:wrap;gap:20px;text-align:center}
.sp-stat-item .val{font-size:2rem;font-weight:800;color:#fff;display:block}
.sp-stat-item .lbl{font-size:13px;color:rgba(255,255,255,.8);margin-top:4px;display:block}
.sp-faq{margin-bottom:48px}
.sp-faq h2{font-size:1.5rem;font-weight:700;color:#1a1a2e;margin:0 0 24px}
.sp-faq-item{border:1.5px solid #ede9e0;border-radius:12px;margin-bottom:10px;overflow:hidden}
.sp-faq-q{padding:16px 20px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-weight:600;font-size:14px;color:#1a1a2e;user-select:none;background:#faf9f7;transition:background .2s}
.sp-faq-q:hover{background:#f0ede8}
.sp-faq-q .arrow{font-size:18px;transition:transform .25s;color:#FF6B00;flex-shrink:0;margin-inline-start:12px}
.sp-faq-q.open .arrow{transform:rotate(180deg)}
.sp-faq-a{display:none;padding:0 20px 16px;font-size:14px;line-height:1.7;color:#555;background:#fff}
.sp-faq-a.open{display:block}
.sp-contact{margin-bottom:48px}.sp-contact h2{font-size:1.5rem;font-weight:700;color:#1a1a2e;margin:0 0 16px}
.sp-contact-msg{font-size:15px;color:#555;margin-bottom:24px;line-height:1.7}
.sp-contact-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}
.sp-contact-item{background:#f9f8f5;border:1.5px solid #ede9e0;border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px}
.sp-contact-item .icon{font-size:22px;flex-shrink:0}
.sp-contact-item .info .lbl{font-size:11px;color:#aaa;text-transform:uppercase;letter-spacing:.05em}
.sp-contact-item .info .val{font-size:14px;color:#1a1a2e;font-weight:600}
</style>

<div class="sp-hero">
  <h1>{{ $heroBanner['titre'] ?? $page->titre }}</h1>
  @if (!empty($heroBanner['sous_titre']))<p>{{ $heroBanner['sous_titre'] }}</p>@endif
</div>

<div class="sp-breadcrumb" dir="{{ $lang_dir }}">
  <a href="{{ site_url() }}">{{ $lang_code === 'ar' ? 'الرئيسية' : 'Accueil' }}</a>
  <span>›</span><span>{{ $page->titre }}</span>
</div>

<div class="sp-content" dir="{{ $lang_dir }}">
  @foreach ($contentBlocks as $block)
  @php $bType = $block['type'] ?? 'text_block'; @endphp

  @if ($bType === 'text_block')
  <div class="sp-text-block">
    @if (!empty($block['titre']))<h2>{{ $block['titre'] }}</h2>@endif
    @foreach (array_filter(array_map('trim', explode("\n\n", $block['contenu'] ?? ''))) as $para)
    <p>{!! nl2br(e($para)) !!}</p>
    @endforeach
  </div>

  @elseif ($bType === 'values_block')
  <div class="sp-values">
    @if (!empty($block['titre']))<h2>{{ $block['titre'] }}</h2>@endif
    <div class="sp-values-grid">
      @foreach ((array)($block['valeurs'] ?? []) as $v)
      <div class="sp-value-card"><span class="emoji">{{ $v['emoji'] ?? '⭐' }}</span><h3>{{ $v['titre'] ?? '' }}</h3><p>{{ $v['texte'] ?? '' }}</p></div>
      @endforeach
    </div>
  </div>

  @elseif ($bType === 'stats_block')
  <div class="sp-stats">
    @foreach ((array)($block['stats'] ?? []) as $s)
    <div class="sp-stat-item"><span class="val">{{ $s['valeur'] ?? '' }}</span><span class="lbl">{{ $s['label'] ?? '' }}</span></div>
    @endforeach
  </div>

  @elseif ($bType === 'faq_block')
  <div class="sp-faq">
    @if (!empty($block['titre']))<h2>{{ $block['titre'] }}</h2>@endif
    @foreach ((array)($block['items'] ?? []) as $item)
    <div class="sp-faq-item">
      <div class="sp-faq-q" onclick="toggleFaq(this)">{{ $item['q'] ?? '' }}<span class="arrow">▾</span></div>
      <div class="sp-faq-a">{!! nl2br(e($item['a'] ?? '')) !!}</div>
    </div>
    @endforeach
  </div>

  @elseif ($bType === 'contact_block')
  <div class="sp-contact">
    @if (!empty($block['titre']))<h2>{{ $block['titre'] }}</h2>@endif
    @if (!empty($block['message']))<div class="sp-contact-msg">{!! nl2br(e($block['message'])) !!}</div>@endif
    <div class="sp-contact-grid">
      @if (!empty($block['email']))<div class="sp-contact-item"><span class="icon">📧</span><div class="info"><div class="lbl">{{ $lang_code === 'ar' ? 'البريد الإلكتروني' : 'Email' }}</div><a href="mailto:{{ $block['email'] }}" class="val" style="color:#FF6B00;text-decoration:none">{{ $block['email'] }}</a></div></div>@endif
      @if (!empty($block['tel']))<div class="sp-contact-item"><span class="icon">📞</span><div class="info"><div class="lbl">{{ $lang_code === 'ar' ? 'الهاتف' : 'Téléphone' }}</div><a href="tel:{{ $block['tel'] }}" class="val" style="color:#FF6B00;text-decoration:none">{{ $block['tel'] }}</a></div></div>@endif
      @if (!empty($block['whatsapp']))<div class="sp-contact-item"><span class="icon">💬</span><div class="info"><div class="lbl">WhatsApp</div><a href="https://wa.me/{{ preg_replace('/\D/','',$block['whatsapp']) }}" target="_blank" class="val" style="color:#25D366;text-decoration:none">{{ $block['whatsapp'] }}</a></div></div>@endif
      @if (!empty($block['adresse']))<div class="sp-contact-item"><span class="icon">📍</span><div class="info"><div class="lbl">{{ $lang_code === 'ar' ? 'العنوان' : 'Adresse' }}</div><div class="val">{{ $block['adresse'] }}</div></div></div>@endif
      @if (!empty($block['horaires']))<div class="sp-contact-item"><span class="icon">🕐</span><div class="info"><div class="lbl">{{ $lang_code === 'ar' ? 'ساعات العمل' : 'Horaires' }}</div><div class="val">{{ $block['horaires'] }}</div></div></div>@endif
    </div>
  </div>
  @endif
  @endforeach
</div>

<script>
function toggleFaq(el){
  var isOpen = el.classList.contains('open');
  el.closest('.sp-faq').querySelectorAll('.sp-faq-q').forEach(function(q){ q.classList.remove('open'); q.nextElementSibling.classList.remove('open'); });
  if (!isOpen){ el.classList.add('open'); el.nextElementSibling.classList.add('open'); }
}
</script>
@endsection
