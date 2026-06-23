@php
    $lang_code = $lang_code ?? 'fr';
    $_fk = langKey($lang_code);
    $product = $product ?? null;
    $socialProofs = $SOCIAL_PROOFS ?? [];

    $ctext = [
        'fr' => 'Nous utilisons des cookies pour améliorer votre expérience et analyser notre trafic. <a href="#">Politique de confidentialité</a>.',
        'ar' => 'نستخدم ملفات تعريف الارتباط لتحسين تجربتك وتحليل حركة المرور. <a href="#">سياسة الخصوصية</a>.',
        'en' => 'We use cookies to improve your experience and analyze traffic. <a href="#">Privacy Policy</a>.',
    ];

    $LF = [
        'fr' => ['links'=>'Liens','cat'=>'Catalogue','track'=>'Suivi commande','contact'=>'Contact','payment'=>'Paiement','cod'=>'Paiement à la livraison','secure'=>'Transactions sécurisées','rights'=>'Tous droits réservés.','legal'=>'Mentions légales','priv'=>'Politique de confidentialité'],
        'ar' => ['links'=>'روابط','cat'=>'الفهرس','track'=>'تتبع طلبك','contact'=>'تواصل معنا','payment'=>'الدفع','cod'=>'الدفع عند الاستلام','secure'=>'معاملات آمنة','rights'=>'جميع الحقوق محفوظة.','legal'=>'الشروط القانونية','priv'=>'سياسة الخصوصية'],
        'en' => ['links'=>'Links','cat'=>'Catalog','track'=>'Track order','contact'=>'Contact','payment'=>'Payment','cod'=>'Cash on delivery','secure'=>'Secure transactions','rights'=>'All rights reserved.','legal'=>'Legal mentions','priv'=>'Privacy policy'],
    ];
    $currLF = $LF[$_fk] ?? $LF['fr'];
@endphp

<div class="cookies-bar" id="cookies-bar">
  <p class="cookies-text">{!! $ctext[$_fk] !!}</p>
  <div class="cookies-actions">
    <button id="cookies-refuse" class="cookies-refuse">{{ $_fk === 'ar' ? 'رفض' : ($_fk === 'en' ? 'Refuse' : 'Refuser') }}</button>
    <button id="cookies-accept" class="cookies-accept">{{ $_fk === 'ar' ? 'قبول' : ($_fk === 'en' ? 'Accept' : 'Accepter') }}</button>
  </div>
</div>

<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-hidden="true">
  <button class="lightbox-close" aria-label="Fermer">✕</button>
  <div class="lightbox-counter"><span class="lb-cur">1</span> / <span class="lb-total">1</span></div>
  <button class="lightbox-nav lightbox-prev" aria-label="Précédent" type="button">‹</button>
  <img class="lightbox-img" src="" alt="">
  <button class="lightbox-nav lightbox-next" aria-label="Suivant" type="button">›</button>
  <div class="lightbox-caption"></div>
  <div class="lightbox-thumbs"></div>
</div>

@if (!empty($socialProofs))
<div class="social-proof" id="social-proof">
  <div class="social-proof-icon">
    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>
  </div>
  <div class="social-proof-text"></div>
</div>
<script>window.SOCIAL_PROOFS = @json($socialProofs);</script>
@endif

<footer class="site-footer">
  <div class="footer-inner">
    <div class="footer-grid">
      <div class="footer-col">
        <div class="footer-logo">{{ $shop['nom_boutique'] ?? 'Boutique' }}</div>
        <p class="footer-desc">{{ $shop['footer_desc'] ?? '' }}</p>
        @if (!empty($shop['facebook']) || !empty($shop['instagram']) || !empty($shop['tiktok']))
        <div class="footer-socials">
          @if (!empty($shop['facebook']))
          <a href="{{ $shop['facebook'] }}" target="_blank" rel="noopener" class="footer-social" aria-label="Facebook">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
          @endif
          @if (!empty($shop['instagram']))
          <a href="{{ $shop['instagram'] }}" target="_blank" rel="noopener" class="footer-social" aria-label="Instagram">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
          </a>
          @endif
          @if (!empty($shop['tiktok']))
          <a href="{{ $shop['tiktok'] }}" target="_blank" rel="noopener" class="footer-social" aria-label="TikTok">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.72a8.27 8.27 0 004.84 1.55V6.83a4.85 4.85 0 01-1.07-.14z"/></svg>
          </a>
          @endif
        </div>
        @endif
      </div>
      <div class="footer-col">
        <h4 class="footer-col-title">{{ $currLF['links'] }}</h4>
        <ul class="footer-links">
          @forelse ($footerMenus ?? [] as $fm)
            <li><a href="{{ menu_url($fm->url) }}">{{ $fm->labelFor($_fk) }}</a></li>
          @empty
            <li><a href="{{ site_url() }}">{{ $currLF['cat'] }}</a></li>
            <li><a href="{{ site_url('suivi') }}">{{ $currLF['track'] }}</a></li>
          @endforelse
        </ul>
      </div>
      <div class="footer-col">
        <h4 class="footer-col-title">{{ $currLF['contact'] }}</h4>
        <ul class="footer-links">
          @if (!empty($shop['email_contact']))
          <li><a href="mailto:{{ $shop['email_contact'] }}">{{ $shop['email_contact'] }}</a></li>
          @endif
          @if (!empty($shop['tel_whatsapp']))
          <li><a href="https://wa.me/{{ preg_replace('/\D/','',$shop['tel_whatsapp']) }}" target="_blank">{{ $shop['tel_whatsapp'] }}</a></li>
          @endif
        </ul>
      </div>
      <div class="footer-col">
        <h4 class="footer-col-title">{{ $currLF['payment'] }}</h4>
        <p class="footer-desc" style="font-size:13px;display:flex;align-items:center;gap:6px">
          <x-icon name="banknotes" :size="16" style="color:#FF6B00;flex-shrink:0" /> {{ $currLF['cod'] }}
        </p>
        <p class="footer-desc" style="font-size:12px;margin-top:6px;display:flex;align-items:center;gap:6px">
          <x-icon name="lock" :size="14" style="color:#16A34A;flex-shrink:0" /> {{ $currLF['secure'] }}
        </p>
      </div>
    </div>
    <div class="footer-bottom">
      <p class="footer-copy">© {{ date('Y') }} {{ $shop['nom_boutique'] ?? 'WeeSaaS' }}. {{ $currLF['rights'] }}</p>
      <div class="footer-legal">
        <a href="#">{{ $currLF['legal'] }}</a>
        <a href="#">{{ $currLF['priv'] }}</a>
      </div>
    </div>
  </div>
</footer>

@if (!empty($shop['tel_whatsapp']))
<a href="https://wa.me/{{ preg_replace('/\D/','',$shop['tel_whatsapp']) }}?text={{ urlencode('Bonjour, je souhaite avoir plus d\'informations.') }}"
   target="_blank" rel="noopener" class="floating-whatsapp" aria-label="Contactez-nous sur WhatsApp">
  <span class="wa-badge">1</span>
  <x-icon name="whatsapp" :size="32" class="wa-icon" />
</a>
@endif

<div class="mobile-sticky-cta" id="mobile-sticky-cta" style="display:none">
  <div class="mobile-sticky-inner">
    @if ($product)
    @php
        $_p = (float) ($product->prix ?? 0);
        $_sym = $product->symbole_devise ?? 'MAD';
        $_pos = $product->position_symbole ?? 'apres';
    @endphp
    <div>
      <div class="mobile-sticky-price">{{ affPrix($_p, $_sym, $_pos) }}</div>
      <div style="font-size:11px;color:#777">{{ $_fk === 'ar' ? 'توصيل مجاني' : ($_fk === 'en' ? 'Free delivery' : 'Livraison gratuite') }}</div>
    </div>
    @if ($product->is_available)
    <button class="mobile-sticky-btn" onclick="document.getElementById('order-section')?.scrollIntoView({behavior:'smooth'})">
      <x-icon name="shopping-bag" :size="18" style="vertical-align:middle;margin-right:6px" />
      {{ $_fk === 'ar' ? 'اطلب الآن' : ($_fk === 'en' ? 'Order now' : 'Commander maintenant') }}
    </button>
    @else
    <button class="mobile-sticky-btn" data-open-modal="modal-demande-info" style="background:#9CA3AF">
      {{ $_fk === 'ar' ? 'أعلمني عند التوفر' : ($_fk === 'en' ? 'Notify me' : "M'alerter") }}
    </button>
    @endif
    @endif
  </div>
</div>
