@php
    $compa = $product->comparison_json ?: [];
    if (empty($compa)) return;

    $cmpData = [];
    foreach ($product->sections_json['sections'] ?? [] as $s) {
        if (($s['type'] ?? '') === 'comparison_table') { $cmpData = $s['data'] ?? []; break; }
    }
    $titre     = $cmpData['titre']         ?? ($lang_code === 'ar' ? 'لماذا نحن الأفضل؟' : ($lang_code === 'en' ? 'Why choose us?' : 'Pourquoi nous ?'));
    $col_nous  = $cmpData['notre_produit'] ?? ($lang_code === 'ar' ? 'نحن' : ($lang_code === 'en' ? 'Our Store' : 'Notre boutique'));
    $col_autres= $cmpData['concurrent']    ?? ($lang_code === 'ar' ? 'المنافسون' : ($lang_code === 'en' ? 'Competitors' : 'Autres'));
@endphp
<section class="comparison-section reveal" id="comparison-section">
  <div class="comparison-inner">
    <h2 class="section-title">{{ $titre }}</h2>
    <table class="comparison-table">
      <thead>
        <tr>
          <th>{{ $lang_code === 'ar' ? 'الميزة' : ($lang_code === 'en' ? 'Feature' : 'Critère') }}</th>
          <th class="ours" style="display:flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap">
            <x-icon name="check-circle" :size="16" style="color:#16A34A" /> {{ mb_substr($col_nous, 0, 25) }}
          </th>
          <th>{{ $col_autres }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($compa as $row)
        <tr>
          <td>{{ $row['feature'] ?? $row['point'] ?? $row['critere'] ?? '' }}</td>
          <td>
            <span class="check">
              <x-icon name="check-circle" :size="20" class="compa-check" style="color:#16A34A;vertical-align:middle" />
              @if (!empty($row['nous']) && $row['nous'] !== true)<span style="vertical-align:middle;margin-left:4px">{{ $row['nous'] }}</span>@endif
            </span>
          </td>
          <td style="color:#777">
            <span class="cross">
              <x-icon name="x-circle" :size="18" class="compa-cross" style="color:#9CA3AF;vertical-align:middle" />
              @if (!empty($row['concurrent']) && $row['concurrent'] !== true)<span style="vertical-align:middle;margin-left:4px">{{ $row['concurrent'] }}</span>@endif
            </span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>
