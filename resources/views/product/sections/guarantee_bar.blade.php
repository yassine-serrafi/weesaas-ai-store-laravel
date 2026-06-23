@php
    $_lk = langKey($lang_code);
    $defaults = [
        'fr' => [['icon'=>'🚚','text'=>'Livraison rapide'],['icon'=>'💰','text'=>'Paiement à la livraison'],['icon'=>'🔄','text'=>'Retour facile'],['icon'=>'🔒','text'=>'Achat sécurisé']],
        'ar' => [['icon'=>'🚚','text'=>'توصيل سريع'],['icon'=>'💰','text'=>'الدفع عند الاستلام'],['icon'=>'🔄','text'=>'استرجاع سهل'],['icon'=>'🔒','text'=>'شراء آمن']],
        'en' => [['icon'=>'🚚','text'=>'Fast delivery'],['icon'=>'💰','text'=>'Cash on delivery'],['icon'=>'🔄','text'=>'Easy returns'],['icon'=>'🔒','text'=>'Secure purchase']],
    ];
    $guarantees = $product->garanties_json ?: ($defaults[$_lk] ?? $defaults['fr']);
@endphp
<section class="guarantee-bar reveal" id="guarantee-bar">
  <div class="guarantee-inner">
    @foreach ($guarantees as $g)
    <div class="guarantee-item">
      <div class="guarantee-icon" style="background:#fff;border:1.5px solid #eee">
        <x-icon :name="$g['icon'] ?? 'check'" :size="20" class="guarantee-svg" />
      </div>
      <div class="guarantee-text">{{ $g['text'] ?? '' }}</div>
    </div>
    @endforeach
  </div>
</section>
