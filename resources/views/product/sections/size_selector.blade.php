@php
    $attrsRaw = $product->attrs_json ?: [];
    $groupes = [];
    if (!empty($attrsRaw['groupes'])) {
        $groupes = $attrsRaw['groupes'];
    } elseif (!empty($attrsRaw['valeurs'])) {
        $groupes = [['id'=>$attrsRaw['type'] ?? 'variant','label'=>ucfirst($attrsRaw['type'] ?? 'Option'),'type'=>'pills','valeurs'=>$attrsRaw['valeurs'],'required'=>false]];
    }
    $sizeGroup = null;
    $typeProd = strtolower($attrsRaw['type_produit'] ?? '');
    foreach ($groupes as $g) {
        if (in_array($g['id'] ?? '', ['taille','stockage','poids','volume','metal'])) { $sizeGroup = $g; break; }
    }
    if (!$sizeGroup || empty($sizeGroup['valeurs'])) return;

    $isShoe = str_contains($typeProd, 'chaussure') || str_contains($typeProd, 'shoe') || str_contains($typeProd, 'basket');
    $isVet  = preg_match('/vetement|shirt|robe|pantalon|hoodie/', $typeProd);
    $mainLabel = $sizeGroup['label'] ?? 'Taille';
    $valeurs = $sizeGroup['valeurs'];
    $guideLabel = ($isShoe || $isVet)
        ? ($lang_code === 'ar' ? 'جدول المقاسات' : ($lang_code === 'en' ? 'Size Guide' : 'Guide des tailles'))
        : ($lang_code === 'ar' ? 'الخيارات المتاحة' : ($lang_code === 'en' ? 'Available Options' : 'Options disponibles'));
@endphp

<section class="size-sel-section">
  <div class="container-section">
    <div class="size-sel-inner">
      <div class="size-sel-header">
        <h3 class="size-sel-title">{{ $lang_code === 'ar' ? 'اختر ' . $mainLabel : ($lang_code === 'en' ? 'Choose your ' . $mainLabel : 'Choisissez votre ' . $mainLabel) }}</h3>
        @if ($isShoe || $isVet)
        <button type="button" class="size-guide-link" id="size-guide-toggle">📏 {{ $guideLabel }}</button>
        @endif
      </div>

      <div class="size-sel-pills">
        @foreach ($valeurs as $sv)
        <button type="button" class="attr-btn attr-text-btn size-sel-pill" data-group="{{ $sizeGroup['id'] }}" data-val="{{ $sv }}">{{ $sv }}</button>
        @endforeach
      </div>

      @if ($isShoe)
      <div class="size-guide-panel" id="size-guide-panel" style="display:none">
        <table class="size-table">
          <thead><tr>
            <th>{{ $lang_code === 'ar' ? 'مقاس EU' : 'EU' }}</th>
            <th>{{ $lang_code === 'ar' ? 'مقاس FR' : 'FR / MA' }}</th>
            <th>{{ $lang_code === 'ar' ? 'طول القدم (cm)' : 'Longueur pied (cm)' }}</th>
          </tr></thead>
          <tbody>
            @foreach ([['36','36','22.5'],['37','37','23.0'],['38','38','24.0'],['39','39','25.0'],['40','40','25.5'],['41','41','26.0'],['42','42','26.5'],['43','43','27.5'],['44','44','28.0'],['45','45','28.5']] as [$eu,$fr,$cm])
              @if (in_array($eu, $valeurs))<tr><td>{{ $eu }}</td><td>{{ $fr }}</td><td>{{ $cm }}</td></tr>@endif
            @endforeach
          </tbody>
        </table>
      </div>
      @elseif ($isVet)
      <div class="size-guide-panel" id="size-guide-panel" style="display:none">
        <table class="size-table">
          <thead><tr>
            <th>{{ $lang_code === 'ar' ? 'المقاس' : 'Taille' }}</th>
            <th>{{ $lang_code === 'ar' ? 'الصدر (cm)' : 'Poitrine (cm)' }}</th>
            <th>{{ $lang_code === 'ar' ? 'الخصر (cm)' : 'Taille (cm)' }}</th>
          </tr></thead>
          <tbody>
            @foreach ([['XS','80-84','60-64'],['S','84-88','64-68'],['M','88-92','68-72'],['L','92-96','72-76'],['XL','96-100','76-80'],['XXL','100-106','80-86']] as [$s,$p,$t])
              @if (in_array($s, $valeurs))<tr><td>{{ $s }}</td><td>{{ $p }}</td><td>{{ $t }}</td></tr>@endif
            @endforeach
          </tbody>
        </table>
      </div>
      @endif

      <div class="size-sel-cta">
        <a href="#order-section" class="btn-order-now">{{ $lang_code === 'ar' ? '⬇ اطلب الآن' : ($lang_code === 'en' ? '⬇ Order now' : '⬇ Commander maintenant') }}</a>
      </div>
    </div>
  </div>
</section>

<style>
.size-sel-section{background:#fff;padding:48px 20px;border-top:1px solid #f0ede8}
.size-sel-inner{max-width:680px;margin:0 auto}
.size-sel-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px}
.size-sel-title{font-size:22px;font-weight:700;color:#111;margin:0}
.size-guide-link{background:none;border:none;color:var(--product-color,#FF6B00);font-size:13px;font-weight:600;cursor:pointer;text-decoration:underline;padding:0}
.size-sel-pills{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
.size-sel-pill{min-width:52px;padding:10px 16px;font-size:15px;font-weight:600;border-radius:10px}
.size-guide-panel{background:#f7f7f5;border-radius:12px;padding:16px;margin-bottom:20px;overflow-x:auto}
.size-table{width:100%;border-collapse:collapse;font-size:13px}
.size-table th{background:var(--product-color,#FF6B00);color:#fff;padding:8px 12px;text-align:left;font-weight:600}
.size-table td{padding:7px 12px;border-bottom:1px solid #e5e5e5;color:#333}
.size-table tr:last-child td{border-bottom:none}
.size-table tr:nth-child(even) td{background:#fafafa}
.size-sel-cta{text-align:center;margin-top:16px}
.btn-order-now{display:inline-block;padding:13px 32px;background:var(--product-color,#FF6B00);color:#fff;border-radius:12px;font-size:15px;font-weight:700;text-decoration:none;transition:opacity .15s}
.btn-order-now:hover{opacity:.88}
[dir="rtl"] .size-sel-header{flex-direction:row-reverse}
[dir="rtl"] .size-table th{text-align:right}
[dir="rtl"] .size-table td{text-align:right}
</style>
<script>
(function(){
  var toggle = document.getElementById('size-guide-toggle');
  var panel  = document.getElementById('size-guide-panel');
  if (toggle && panel) toggle.addEventListener('click', function(){ panel.style.display = panel.style.display !== 'none' ? 'none' : 'block'; });
  document.querySelectorAll('.size-sel-pill').forEach(function(btn){
    btn.addEventListener('click', function(){
      setTimeout(function(){ var sec = document.getElementById('order-section'); if (sec) sec.scrollIntoView({behavior:'smooth',block:'start'}); }, 350);
    });
  });
})();
</script>
