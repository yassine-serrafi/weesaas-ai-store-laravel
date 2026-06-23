@php
    $img0 = $images[0] ?? null;

    $attrsRaw = $product->attrs_json ?: [];
    $attrsMan = $product->attributs ?: [];
    $_groupes = [];
    if (!empty($attrsRaw['groupes'])) {
        $_groupes = $attrsRaw['groupes'];
    } elseif (!empty($attrsRaw['valeurs'])) {
        $_groupes = [['id'=>$attrsRaw['type'] ?? 'variant','label'=>ucfirst($attrsRaw['type'] ?? 'Option'),'type'=>'pills','valeurs'=>$attrsRaw['valeurs'],'required'=>false]];
    } elseif (!empty($attrsMan)) {
        foreach ($attrsMan as $manKey => $manVals) {
            if (is_array($manVals) && !empty($manVals)) {
                $_groupes[] = ['id'=>$manKey,'label'=>ucfirst($manKey),'type'=>'pills','valeurs'=>$manVals,'required'=>false];
            }
        }
    }

    $colorMap = [
        'noir'=>'#111','black'=>'#111','أسود'=>'#111','blanc'=>'#fff','white'=>'#fff','أبيض'=>'#fff',
        'rouge'=>'#DC2626','red'=>'#DC2626','أحمر'=>'#DC2626','bleu'=>'#2563EB','blue'=>'#2563EB','أزرق'=>'#2563EB',
        'vert'=>'#16A34A','green'=>'#16A34A','أخضر'=>'#16A34A','jaune'=>'#EAB308','yellow'=>'#EAB308','أصفر'=>'#EAB308',
        'orange'=>'#F97316','برتقالي'=>'#F97316','rose'=>'#EC4899','pink'=>'#EC4899','وردي'=>'#EC4899',
        'violet'=>'#7C3AED','purple'=>'#7C3AED','بنفسجي'=>'#7C3AED','gris'=>'#6B7280','grey'=>'#6B7280','gray'=>'#6B7280','رمادي'=>'#6B7280',
        'marron'=>'#92400E','brown'=>'#92400E','بني'=>'#92400E','beige'=>'#D4B896','crème'=>'#F5F0E8','كريمي'=>'#F5F0E8',
        'or'=>'#D97706','gold'=>'#D97706','ذهبي'=>'#D97706','argent'=>'#9CA3AF','silver'=>'#9CA3AF','فضي'=>'#9CA3AF',
        'camel'=>'#C19A6B','navy'=>'#1E3A5F','bordeaux'=>'#6B1D2B','kaki'=>'#6B7C5E','turquoise'=>'#0D9488','corail'=>'#FF6B6B','coral'=>'#FF6B6B',
    ];

    $prix = (float) $product->prix;
    $devise_sym = $product->symbole_devise ?: 'MAD';
    $devise_pos = $product->position_symbole ?: 'apres';
    $pays = $product->pays_effectif;
    $_lk = langKey($lang_code);
    $indicatif = getIndicatifPays($pays);

    $labels = [
        'fr' => ['titre'=>'Finaliser ma commande','sous'=>'Livraison rapide · Paiement à la livraison','nom'=>'Nom complet','tel'=>'Téléphone','ville'=>'Ville','adresse'=>'Adresse (optionnel)','note'=>'Note commande (optionnel)','submit'=>'🛒 Je commande maintenant','secure'=>'Commande sécurisée · Aucun paiement en ligne','err_req'=>'Champ requis','err_tel'=>'Numéro invalide'],
        'ar' => ['titre'=>'إتمام الطلب','sous'=>'توصيل سريع · الدفع عند الاستلام','nom'=>'الاسم الكامل','tel'=>'الهاتف','ville'=>'المدينة','adresse'=>'العنوان (اختياري)','note'=>'ملاحظة (اختياري)','submit'=>'🛒 اطلب الآن','secure'=>'طلب آمن · لا دفع إلكتروني','err_req'=>'هذا الحقل مطلوب','err_tel'=>'رقم غير صحيح'],
        'en' => ['titre'=>'Complete my order','sous'=>'Fast delivery · Cash on delivery','nom'=>'Full name','tel'=>'Phone','ville'=>'City','adresse'=>'Address (optional)','note'=>'Order note (optional)','submit'=>'🛒 Order now','secure'=>'Secure order · No online payment','err_req'=>'Required field','err_tel'=>'Invalid number'],
    ];
    $L = $labels[$_lk] ?? $labels['fr'];

    $stock_real   = (int) $product->stock_dispo;
    $out_of_stock = ! $product->is_available;            // suivi actif + stock 0
    $qte_max = max(2, (int) ($product->quantite_max ?? 10));
    $qte_max = $product->stock_untracked ? $qte_max : min($qte_max, max(1, $stock_real));
    $multi_allowed = $qte_max > 1;
    $livraison_val = (float) ($product->frais_livraison ?? 0);
    $livraison_gratuite = (bool) $product->livraison_gratuite;
@endphp

<section class="order-section" id="order-section">
  <div class="order-inner">
    <div class="order-card">
      <div class="order-card-head">
        <div class="order-card-title">{{ $L['titre'] }}</div>
        <div class="order-card-sub">{{ $L['sous'] }}</div>
      </div>
      <div class="order-card-body">

        <div class="order-summary">
          @if ($img0)
          <img src="{{ $img0->url }}" alt="{{ $product->nom_produit }}" class="order-summary-img" loading="lazy">
          @endif
          <div class="order-summary-info">
            <div class="order-summary-name">{{ $product->nom_produit }}</div>
            <div class="order-summary-attr" id="summary-attrs"></div>
            @if ($out_of_stock)
            <div style="font-size:11px;color:#DC2626;font-weight:700;margin-top:2px">✕ {{ $_lk === 'ar' ? 'نفذ المخزون' : ($_lk === 'en' ? 'Out of stock' : 'Épuisé') }}</div>
            @else
            <div style="font-size:11px;color:#16A34A;margin-top:2px">✓ {{ $_lk === 'ar' ? 'في المخزن' : ($_lk === 'en' ? 'In stock' : 'En stock') }}</div>
            @endif
          </div>
          <div class="order-summary-price" id="summary-price">{{ affPrix($prix, $devise_sym, $devise_pos) }}</div>
        </div>

        @if (!empty($_groupes))
        <div class="attr-selectors" id="attr-selectors">
          @foreach ($_groupes as $_g)
          @php $gId = e($_g['id'] ?? 'v'); $gLab = e($_g['label'] ?? 'Option'); $gTyp = $_g['type'] ?? 'pills'; $gVals = $_g['valeurs'] ?? []; $gReq = !empty($_g['required']); @endphp
          @if (!empty($gVals))
          <div class="attr-group" data-group="{{ $gId }}" data-required="{{ $gReq ? '1' : '0' }}">
            <div class="attr-group-head">
              <span class="attr-group-label">{!! $gLab !!}</span>
              <span class="attr-selected" id="attr-sel-{{ $gId }}"></span>
            </div>
            <div class="attr-pills">
              @foreach ($gVals as $_v)
              @php $_hex = $colorMap[strtolower(trim($_v))] ?? null; @endphp
              @if ($gTyp === 'color_pills')
              <button type="button" class="attr-btn attr-color-btn" data-group="{{ $gId }}" data-val="{{ $_v }}" title="{{ $_v }}" style="{{ $_hex ? 'background:'.$_hex.';border-color:'.$_hex : '' }}">
                @if (!$_hex)<span>{{ $_v }}</span>@endif
              </button>
              @else
              <button type="button" class="attr-btn attr-text-btn" data-group="{{ $gId }}" data-val="{{ $_v }}">{{ $_v }}</button>
              @endif
              @endforeach
            </div>
            <input type="hidden" name="attr_{{ $gId }}" id="attr-hid-{{ $gId }}" value="" form="order-form">
            @if ($gReq)
            <div class="attr-error" id="attr-err-{{ $gId }}" style="display:none;color:#DC2626;font-size:12px;margin-top:4px">
              {{ $_lk === 'ar' ? 'الرجاء اختيار ' . $gLab : ($_lk === 'en' ? 'Please choose: ' . $gLab : 'Veuillez choisir : ' . $gLab) }}
            </div>
            @endif
          </div>
          @endif
          @endforeach
        </div>
        @endif

        <form id="order-form" novalidate>
          @csrf
          <input type="hidden" name="product_id" value="{{ (int) $product->id }}">
          <input type="hidden" name="slug" value="{{ $product->slug }}">

          <div class="form-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-field">
              <label for="of-nom">{{ $L['nom'] }} <span style="color:#DC2626">*</span></label>
              <input type="text" id="of-nom" name="nom" class="form-input" required autocomplete="name" placeholder="{{ $_lk === 'ar' ? 'محمد الأمين' : 'Mohamed Amine' }}">
              <div class="form-error">{{ $L['err_req'] }}</div>
            </div>
            <div class="form-field">
              <label for="of-tel">{{ $L['tel'] }} <span style="color:#DC2626">*</span></label>
              <div class="field-tel-wrap">
                <div class="indicatif-box">{{ $indicatif }}</div>
                <input type="tel" id="of-tel" name="telephone" class="form-input tel-input" required autocomplete="tel" pattern="[0-9]{6,14}" placeholder="{{ $pays === 'maroc' ? '0612345678' : '0501234567' }}">
              </div>
              <div class="form-error">{{ $L['err_tel'] }}</div>
            </div>
          </div>

          <div class="form-field">
            <label for="of-ville">{{ $L['ville'] }} <span style="color:#DC2626">*</span></label>
            <div class="city-select-wrap">
              <input type="text" id="of-ville" class="form-input city-input" required readonly placeholder="{{ $_lk === 'ar' ? 'اختر مدينتك' : 'Sélectionner votre ville' }}">
              <input type="hidden" id="ville-hidden" name="ville" required>
              <div class="city-dropdown" id="city-dropdown">
                <div class="city-search">
                  <input type="text" class="city-search-input" placeholder="{{ $_lk === 'ar' ? 'بحث...' : 'Rechercher...' }}">
                </div>
                <div class="city-options">
                  <div class="city-option" style="color:#999;font-size:12px">{{ $_lk === 'ar' ? 'جاري التحميل...' : 'Chargement...' }}</div>
                </div>
              </div>
            </div>
            <div class="form-error">{{ $L['err_req'] }}</div>
          </div>

          <div class="form-field">
            <label for="of-adresse">{{ $L['adresse'] }}</label>
            <input type="text" id="of-adresse" name="adresse" class="form-input" placeholder="{{ $_lk === 'ar' ? 'شارع، حي...' : 'Rue, quartier...' }}">
          </div>

          <div class="form-field">
            <label for="of-note">{{ $L['note'] }}</label>
            <textarea id="of-note" name="note" class="form-input" rows="2" placeholder="{{ $_lk === 'ar' ? 'ملاحظة...' : 'Précision sur votre commande...' }}"></textarea>
          </div>

          @if ($multi_allowed)
          <div class="qty-row">
            <span class="qty-label">{{ $_lk === 'ar' ? 'الكمية' : ($_lk === 'en' ? 'Quantity' : 'Quantité') }}</span>
            <div class="qty-stepper">
              <button type="button" class="qty-btn" id="qty-order-minus" aria-label="Moins">−</button>
              <input type="number" id="qty-order-input" name="quantite" value="1" min="1" max="{{ $qte_max }}" class="qty-val" readonly>
              <button type="button" class="qty-btn" id="qty-order-plus" aria-label="Plus">+</button>
            </div>
          </div>
          @else
          <input type="hidden" name="quantite" value="1">
          @endif

          <div class="price-recap" id="price-recap">
            <div class="price-recap-row">
              <span id="prix-label-produit">{{ $_lk === 'ar' ? 'المنتج' : ($_lk === 'en' ? 'Product' : 'Produit') }}</span>
              <span id="prix-produit-val" class="money">{{ affPrix($prix, $devise_sym, $devise_pos) }}</span>
            </div>
            <div class="price-recap-row">
              <span>{{ $_lk === 'ar' ? 'التوصيل' : ($_lk === 'en' ? 'Shipping' : 'Livraison') }}</span>
              <span style="color:#16A34A;font-weight:600" id="livraison-val">{{ $livraison_gratuite ? ($_lk === 'ar' ? 'مجاني' : ($_lk === 'en' ? 'Free' : 'Gratuite')) : affPrix($livraison_val, $devise_sym, $devise_pos) }}</span>
            </div>
            <hr style="border:none;border-top:1px solid #ddd;margin:8px 0">
            <div class="price-recap-row">
              <span style="font-weight:700">{{ $_lk === 'ar' ? 'المجموع' : ($_lk === 'en' ? 'Total' : 'Total') }}</span>
              <span id="total-val" class="money" style="font-size:17px;color:var(--product-color)">{{ affPrix($prix + $livraison_val, $devise_sym, $devise_pos) }}</span>
            </div>
          </div>

          <script>
          var WEE_PRIX      = {{ (float) $prix }};
          var WEE_LIVRAISON = {{ $livraison_gratuite ? 0 : (float) $livraison_val }};
          var WEE_SYM       = @json($devise_sym);
          var WEE_POS       = @json($devise_pos);
          var WEE_LANG      = @json($_lk);
          </script>

          <div id="form-error" style="display:none;background:#FEF2F2;color:#DC2626;padding:10px 12px;border-radius:8px;font-size:13px;margin-bottom:10px"></div>

          @if ($out_of_stock)
          <button type="button" class="btn-submit" disabled style="opacity:.55;cursor:not-allowed;background:#9CA3AF;box-shadow:none">
            {{ $_lk === 'ar' ? 'نفذ المخزون' : ($_lk === 'en' ? 'Out of stock' : 'Produit épuisé') }}
          </button>
          <button type="button" class="btn-whatsapp" data-open-modal="modal-demande-info" style="margin-top:8px;width:100%">
            {{ $_lk === 'ar' ? 'أعلمني عند التوفر' : ($_lk === 'en' ? 'Notify me when available' : "M'alerter quand disponible") }}
          </button>
          @else
          <button type="submit" class="btn-submit">{{ $L['submit'] }}</button>
          @endif

          <div class="order-trust-badges">
            <span class="order-trust-badge"><x-icon name="shield" :size="18" /> {{ $_lk === 'ar' ? 'آمن 100%' : ($_lk === 'en' ? '100% Secure' : '100% Sécurisé') }}</span>
            <span class="order-trust-badge"><x-icon name="truck" :size="18" /> {{ $_lk === 'ar' ? 'توصيل سريع' : ($_lk === 'en' ? 'Fast delivery' : 'Livraison rapide') }}</span>
            <span class="order-trust-badge"><x-icon name="banknotes" :size="18" /> {{ $_lk === 'ar' ? 'دفع عند الاستلام' : ($_lk === 'en' ? 'Cash on delivery' : 'Paiement à la livraison') }}</span>
          </div>

          <div class="submit-note"><x-icon name="lock" :size="16" /> {{ $L['secure'] }}</div>
        </form>
      </div>
    </div>
  </div>
</section>

<script>
(function() {
  var attrBtns = document.querySelectorAll('.attr-btn');
  function updateSummaryAttrs() {
    var parts = [];
    document.querySelectorAll('.attr-selected').forEach(function(el){ var v = el.dataset.val || el.textContent.trim(); if (v) parts.push(v); });
    var el = document.getElementById('summary-attrs'); if (el) el.textContent = parts.join(' · ');
  }
  attrBtns.forEach(function(btn) {
    btn.addEventListener('click', function() {
      var group = btn.dataset.group;
      document.querySelectorAll('.attr-btn[data-group="' + group + '"]').forEach(function(b){ b.classList.remove('is-active'); if (b.classList.contains('attr-color-btn')) b.style.outline = ''; });
      btn.classList.add('is-active');
      if (btn.classList.contains('attr-color-btn')) { btn.style.outline = '3px solid var(--product-color, #FF6B00)'; btn.style.outlineOffset = '2px'; }
      var val = btn.dataset.val;
      var hid = document.getElementById('attr-hid-' + group); if (hid) hid.value = val;
      var sel = document.getElementById('attr-sel-' + group); if (sel) { sel.textContent = val; sel.dataset.val = val; }
      var err = document.getElementById('attr-err-' + group); if (err) err.style.display = 'none';
      updateSummaryAttrs();
    });
  });
  var form = document.getElementById('order-form');
  if (form) form.addEventListener('submit', function(e) {
    var allOk = true;
    document.querySelectorAll('.attr-group[data-required="1"]').forEach(function(grp) {
      var group = grp.dataset.group; var hid = document.getElementById('attr-hid-' + group);
      if (!hid || !hid.value) { var err = document.getElementById('attr-err-' + group); if (err) err.style.display = 'block'; grp.scrollIntoView({behavior:'smooth',block:'center'}); allOk = false; }
    });
    if (!allOk) { e.preventDefault(); e.stopImmediatePropagation(); return false; }
  }, true);
  updateSummaryAttrs();

  var qtyInput = document.getElementById('qty-order-input');
  var qtyMinus = document.getElementById('qty-order-minus');
  var qtyPlus  = document.getElementById('qty-order-plus');
  function formatMoney(n) { var fmt = n.toLocaleString('fr-FR', {maximumFractionDigits:0}); return WEE_POS === 'avant' ? WEE_SYM + ' ' + fmt : fmt + ' ' + WEE_SYM; }
  function updateTotal() {
    if (!qtyInput) return;
    var qty = parseInt(qtyInput.value,10) || 1; var sous = WEE_PRIX * qty; var total = sous + WEE_LIVRAISON;
    var elProd = document.getElementById('prix-produit-val'); var elTotal = document.getElementById('total-val'); var elSum = document.getElementById('summary-price'); var elLabel = document.getElementById('prix-label-produit');
    if (elProd) elProd.textContent = formatMoney(sous);
    if (elTotal) elTotal.textContent = formatMoney(total);
    if (elSum) elSum.textContent = formatMoney(total);
    if (elLabel && qty > 1) { var baseLabel = WEE_LANG === 'ar' ? 'المنتج' : (WEE_LANG === 'en' ? 'Product' : 'Produit'); elLabel.textContent = baseLabel + ' × ' + qty; }
    if (elTotal) { elTotal.style.transform = 'scale(1.08)'; elTotal.style.transition = 'transform .15s'; setTimeout(function(){ elTotal.style.transform=''; }, 200); }
  }
  if (qtyMinus) qtyMinus.addEventListener('click', function(){ var v = parseInt(qtyInput.value,10)||1; if (v>1){qtyInput.value=v-1;updateTotal();} qtyMinus.disabled = parseInt(qtyInput.value,10)<=1; qtyPlus.disabled=false; });
  if (qtyPlus) qtyPlus.addEventListener('click', function(){ var v = parseInt(qtyInput.value,10)||1; var max = parseInt(qtyInput.max,10)||99; if (v<max){qtyInput.value=v+1;updateTotal();} qtyPlus.disabled = parseInt(qtyInput.value,10)>=max; qtyMinus.disabled=false; });
  if (qtyMinus && parseInt(qtyInput?.value,10) <= 1) qtyMinus.disabled = true;
  if (qtyInput) qtyInput.setAttribute('data-order-qty','1');
})();
</script>

<style>
.qty-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;padding:10px 14px;background:#f7f7f5;border-radius:10px}
.qty-label{font-size:13px;font-weight:700;color:#222}
.qty-stepper{display:flex;align-items:center;gap:0;border:1.5px solid #e0dcd5;border-radius:10px;overflow:hidden;background:#fff}
.qty-stepper .qty-btn{width:38px;height:38px;border:none;background:transparent;font-size:18px;font-weight:600;color:#333;cursor:pointer;transition:background .12s,color .12s;display:flex;align-items:center;justify-content:center;line-height:1}
.qty-stepper .qty-btn:hover:not(:disabled){background:var(--product-color,#FF6B00);color:#fff}
.qty-stepper .qty-btn:disabled{color:#ccc;cursor:default}
.qty-val{width:44px;text-align:center;border:none;border-left:1px solid #e8e5e0;border-right:1px solid #e8e5e0;font-size:15px;font-weight:700;color:#111;outline:none;-moz-appearance:textfield;background:#fff}
.qty-val::-webkit-outer-spin-button,.qty-val::-webkit-inner-spin-button{-webkit-appearance:none}
.price-recap{background:#f7f7f5;border-radius:10px;padding:12px 14px;margin-bottom:14px;font-size:13px}
.price-recap-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;color:#555}
.price-recap-row:last-child{margin-bottom:0}
.attr-selectors{margin-bottom:16px;display:flex;flex-direction:column;gap:14px}
.attr-group-head{display:flex;align-items:baseline;gap:8px;margin-bottom:8px}
.attr-group-label{font-size:13px;font-weight:700;color:#222}
.attr-selected{font-size:13px;color:var(--product-color,#FF6B00);font-weight:600}
.attr-pills{display:flex;flex-wrap:wrap;gap:8px}
.attr-text-btn{padding:7px 14px;border-radius:8px;border:1.5px solid #ddd;background:#fff;color:#222;font-size:14px;font-weight:500;cursor:pointer;transition:all .15s;line-height:1}
.attr-text-btn:hover{border-color:var(--product-color,#FF6B00);color:var(--product-color,#FF6B00)}
.attr-text-btn.is-active{background:var(--product-color,#FF6B00);border-color:var(--product-color,#FF6B00);color:#fff;font-weight:700}
.attr-color-btn{width:32px;height:32px;border-radius:50%;border:2px solid #ccc;cursor:pointer;transition:all .15s;position:relative;display:flex;align-items:center;justify-content:center;overflow:visible;padding:0;background:#eee}
.attr-color-btn span{font-size:10px;white-space:nowrap;position:absolute;bottom:-18px;left:50%;transform:translateX(-50%);color:#555;pointer-events:none}
.attr-color-btn.is-active{transform:scale(1.15)}
[dir="rtl"] .attr-group-head{flex-direction:row-reverse}
[dir="rtl"] .attr-pills{flex-direction:row-reverse}
[dir="rtl"] .qty-row{flex-direction:row-reverse}
[dir="rtl"] .price-recap-row{flex-direction:row-reverse}
</style>
