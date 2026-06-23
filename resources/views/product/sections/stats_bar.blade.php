@php
    $stats = $product->stats ?: [];
    if (empty($stats)) return;
@endphp
<section class="stats-bar reveal" id="stats-section">
  <div class="stats-inner">
    @foreach ($stats as $st)
    <div class="stat-item">
      <div class="stat-val">{{ $st['val'] ?? $st['valeur'] ?? $st['value'] ?? '' }}</div>
      <div class="stat-label">{{ $st['label'] ?? '' }}</div>
    </div>
    @endforeach
  </div>
</section>
