@php
    if (count($images) < 2) return;
    $titre = $product->gallery_title ?: ($lang_code === 'ar' ? 'صور المنتج' : ($lang_code === 'en' ? 'Product Gallery' : 'Galerie photos'));
    $zoomLabel = $lang_code === 'ar' ? 'تكبير' : ($lang_code === 'en' ? 'Zoom' : 'Agrandir');
    $total = count($images);
@endphp
<section class="gallery-section reveal" id="gallery-section">
  <div class="gallery-inner">
    <h2 class="section-title">{{ $titre }}</h2>
    <div class="gallery-grid" id="gallery-grid">
      @foreach ($images as $i => $img)
      <figure class="gallery-item" data-lightbox data-gallery="product"
              data-src="{{ $img->url }}"
              data-caption="{{ $product->nom_produit }} — {{ $i + 1 }}/{{ $total }}"
              tabindex="0" role="button" aria-label="{{ $zoomLabel }} ({{ $i + 1 }}/{{ $total }})">
        <img src="{{ $img->url }}" alt="{{ $product->nom_produit }} - photo {{ $i + 1 }}"
             loading="lazy" width="600" height="450">
        <span class="gallery-overlay">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
          <span>{{ $zoomLabel }}</span>
        </span>
        <span class="gallery-num">{{ $i + 1 }}/{{ $total }}</span>
      </figure>
      @endforeach
    </div>
  </div>
</section>
