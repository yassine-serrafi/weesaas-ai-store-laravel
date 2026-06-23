@php
    $faq = (!empty($product->faqs)) ? $product->faqs : ($product->faq_json ?: []);
    if (empty($faq)) return;
    $titre = $product->faq_title ?: ($lang_code === 'ar' ? 'الأسئلة الشائعة' : ($lang_code === 'en' ? 'Frequently Asked Questions' : 'Questions fréquentes'));
@endphp
<section class="faq-section reveal" id="faq-section">
  <div class="faq-inner">
    <h2 class="section-title">{{ $titre }}</h2>
    <div class="faq-list">
      @foreach ($faq as $fi => $item)
      <div class="faq-item{{ $fi === 0 ? ' open' : '' }}">
        <button class="faq-question" type="button" aria-expanded="{{ $fi === 0 ? 'true' : 'false' }}">
          {{ $item['q'] ?? $item['question'] ?? '' }}
          <span class="faq-icon" aria-hidden="true"><x-icon name="chevron-down" :size="18" class="faq-chevron" /></span>
        </button>
        <div class="faq-answer">
          <p>{!! nl2br(e($item['a'] ?? $item['r'] ?? $item['reponse'] ?? '')) !!}</p>
        </div>
      </div>
      @endforeach
    </div>
  </div>
</section>
