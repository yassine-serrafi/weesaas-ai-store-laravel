@php
    $avis = (!empty($product->testimonials)) ? $product->testimonials : ($product->temoignages_json ?: []);
    if (empty($avis)) return;
    $_tk = langKey($lang_code);
    $titre = $product->testimonials_title ?: ($_tk === 'ar' ? 'ما يقوله عملاؤنا' : ($_tk === 'en' ? 'What our customers say' : 'Ce que disent nos clients'));
    $LT = [
        'fr' => ['avis'=>'avis','verifie'=>'✓ Achat vérifié'],
        'ar' => ['avis'=>'تقييم','verifie'=>'✓ شراء مؤكد'],
        'en' => ['avis'=>'reviews','verifie'=>'✓ Verified Purchase'],
    ];
    $currLT = $LT[$_tk] ?? $LT['fr'];
    $rating = (float) ($product->rating ?? 4.8);
    $reviews_count = (int) ($product->reviews_count ?? count($avis));
    $couleurs = ['#FF6B00','#16A34A','#2563EB','#7C3AED','#DC2626'];
@endphp
<section class="testimonials-section reveal" id="testimonials-section">
  <div class="testimonials-inner">
    <h2 class="section-title">{{ $titre }}</h2>

    <div style="display:flex;align-items:center;justify-content:center;gap:20px;margin-bottom:32px;flex-wrap:wrap">
      <div style="text-align:center">
        <div style="font-size:48px;font-weight:800;line-height:1">{{ number_format($rating, 1) }}</div>
        <div style="display:flex;justify-content:center;gap:2px;margin-bottom:4px">
          @for ($i = 0; $i < 5; $i++)<x-icon :name="$i < round($rating) ? 'star-solid' : 'star'" :size="18" style="color:#F59E0B" />@endfor
        </div>
        <div style="font-size:13px;color:#777;margin-top:4px">{{ $reviews_count }}+ {{ $currLT['avis'] }}</div>
      </div>
      <div style="display:flex;flex-direction:column;gap:6px;min-width:180px">
        @for ($s = 5; $s >= 1; $s--)
        @php
          $cnt = 0; foreach ($avis as $a) { if ((int)($a['note'] ?? 5) === $s) $cnt++; }
          $pct = count($avis) > 0 ? round($cnt / count($avis) * 100) : ($s === 5 ? 80 : ($s === 4 ? 15 : 5));
        @endphp
        <div style="display:flex;align-items:center;gap:8px;font-size:12px">
          <span style="min-width:10px;color:#F59E0B">{{ $s }}★</span>
          <div style="flex:1;height:6px;background:#f0f0f0;border-radius:3px;overflow:hidden">
            <div style="width:{{ $pct }}%;height:100%;background:#F59E0B;border-radius:3px"></div>
          </div>
          <span style="color:#999;min-width:30px">{{ $pct }}%</span>
        </div>
        @endfor
      </div>
    </div>

    <div class="testimonials-grid testimonials-carousel">
      <div class="testimonials-track">
        @foreach ($avis as $av)
        @php
          $note = (int) ($av['note'] ?? 5);
          $initiale = mb_substr($av['prenom'] ?? $av['auteur'] ?? 'A', 0, 1);
          $col = $couleurs[ord($initiale[0] ?? 'A') % count($couleurs)];
        @endphp
        <div class="testimonial-card">
          <div class="testimonial-stars" style="display:flex;gap:2px;margin-bottom:8px">
            @for ($i = 0; $i < 5; $i++)<x-icon :name="$i < $note ? 'star-solid' : 'star'" :size="14" style="color:#F59E0B" />@endfor
          </div>
          <p class="testimonial-text">"{{ $av['texte'] ?? '' }}"</p>
          <div class="testimonial-footer">
            <div class="testimonial-avatar" style="background:{{ $col }}">{{ $initiale }}</div>
            <div>
              <div class="testimonial-name">{{ $av['prenom'] ?? $av['auteur'] ?? '' }}@if (!empty($av['nom'])) {{ mb_substr($av['nom'], 0, 1) }}.@endif</div>
              <div class="testimonial-meta">
                {{ $av['ville'] ?? '' }}@if (!empty($av['attr'])) · <span class="testimonial-attr">{{ $av['attr'] }}</span>@endif
              </div>
              @if (!empty($av['verifie']) || !isset($av['verifie']))
              <div style="font-size:10px;color:#16A34A;font-weight:600;margin-top:2px">{{ $currLT['verifie'] }}</div>
              @endif
            </div>
            @if (!empty($av['date']))<div style="margin-inline-start:auto;font-size:10px;color:#bbb">{{ $av['date'] }}</div>@endif
          </div>
        </div>
        @endforeach
      </div>
    </div>
    <div class="carousel-dots" id="carousel-dots">
      @for ($i = 0; $i < min(count($avis), 5); $i++)
      <button class="carousel-dot {{ $i === 0 ? 'active' : '' }}" type="button" aria-label="Avis {{ $i + 1 }}"></button>
      @endfor
    </div>
  </div>
</section>
