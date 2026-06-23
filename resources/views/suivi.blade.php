@extends('layouts.front')

@php
    $steps_map = [
        'fr' => [
            'nouvelle'=>['label'=>'Commande reçue','desc'=>'Votre commande est en cours de traitement'],
            'confirmee'=>['label'=>'Commande confirmée','desc'=>'Votre commande a été confirmée et va être préparée'],
            'expediee'=>['label'=>'En cours de livraison','desc'=>'Votre colis est en route'],
            'livree'=>['label'=>'Livrée','desc'=>'Votre commande a été livrée. Profitez-en !'],
            'annulee'=>['label'=>'Annulée','desc'=>'Cette commande a été annulée'],
            'retour'=>['label'=>'Retour','desc'=>'Un retour est en cours'],
        ],
        'ar' => [
            'nouvelle'=>['label'=>'تم استلام الطلب','desc'=>'طلبك قيد المعالجة'],
            'confirmee'=>['label'=>'تم تأكيد الطلب','desc'=>'تم تأكيد طلبك وسيتم تجهيزه'],
            'expediee'=>['label'=>'في الطريق إليك','desc'=>'طردك في الطريق إليك'],
            'livree'=>['label'=>'تم التسليم','desc'=>'تم تسليم طلبك. استمتع به!'],
            'annulee'=>['label'=>'ملغى','desc'=>'تم إلغاء هذا الطلب'],
            'retour'=>['label'=>'إرجاع','desc'=>'الإرجاع جارٍ'],
        ],
        'en' => [
            'nouvelle'=>['label'=>'Order received','desc'=>'Your order is being processed'],
            'confirmee'=>['label'=>'Order confirmed','desc'=>'Your order has been confirmed and will be prepared'],
            'expediee'=>['label'=>'Out for delivery','desc'=>'Your package is on its way'],
            'livree'=>['label'=>'Delivered','desc'=>'Your order has been delivered. Enjoy!'],
            'annulee'=>['label'=>'Cancelled','desc'=>'This order has been cancelled'],
            'retour'=>['label'=>'Return','desc'=>'A return is in progress'],
        ],
    ];
    $ordered = ['nouvelle','confirmee','expediee','livree'];
    $steps = $steps_map[$lang_code] ?? $steps_map['fr'];
    $current = $order->statut ?? '';
    $errMsg = ['ar'=>'الطلب غير موجود. تحقق من رقمك.','en'=>'Order not found. Please check your reference.','fr'=>'Commande introuvable. Vérifiez votre référence.'];
@endphp

@section('content')
<div class="suivi-wrap">
  <div class="suivi-card">
    <h1 class="suivi-title">📦 {{ $lang_code === 'ar' ? 'تتبع طلبك' : 'Suivi de commande' }}</h1>

    <form method="get" action="">
      <div class="form-field">
        <label class="form-label">{{ $lang_code === 'ar' ? 'رقم الطلب' : 'Numéro de commande' }}</label>
        <div style="display:flex;gap:8px">
          <input type="text" name="ref" class="form-input" value="{{ $ref }}" placeholder="MA-240101-XXXXX" style="flex:1;text-transform:uppercase">
          <button type="submit" class="btn-submit" style="width:auto;padding:0 20px">{{ $lang_code === 'ar' ? 'بحث' : 'Suivre' }}</button>
        </div>
      </div>
    </form>

    @if ($error)
    <div class="alert alert-error" style="margin-top:16px">{{ $errMsg[$lang_code] ?? $errMsg['fr'] }}</div>
    @endif

    @if ($order)
    <div style="background:#f7f7f5;border-radius:10px;padding:14px 16px;margin-top:20px">
      <div style="font-size:13px;color:#777;margin-bottom:4px">{{ $lang_code === 'ar' ? 'رقم الطلب' : 'Référence' }}</div>
      <div style="font-size:16px;font-weight:700;color:#FF6B00;font-family:monospace">{{ $order->reference }}</div>
      <div style="font-size:13px;color:#555;margin-top:6px">{{ $order->product->nom_produit }} × {{ (int) $order->quantite }}</div>
      <div style="font-size:14px;font-weight:700;margin-top:4px">{{ number_format((float) $order->total_ttc, 0, ',', ' ') }} {{ $order->symbole_devise }}</div>
    </div>

    @if (!in_array($current, ['annulee','retour']))
    <div class="suivi-steps">
      @foreach ($ordered as $i => $s)
      @php
        $statutIdx = array_search($s, $ordered);
        $currentIdx = array_search($current, $ordered);
        $isDone = $currentIdx !== false && $statutIdx <= $currentIdx;
        $isCurrent = $s === $current;
        $info = $steps[$s] ?? ['label'=>$s,'desc'=>''];
      @endphp
      <div class="suivi-step {{ $isDone ? 'done' : '' }} {{ $isCurrent ? 'current' : '' }}">
        <div class="suivi-step-dot">{{ $isDone ? '✓' : $statutIdx + 1 }}</div>
        <div class="suivi-step-content">
          <div class="suivi-step-label">{{ $info['label'] }}</div>
          @if ($isCurrent)<div class="suivi-step-date" style="color:#FF6B00">{{ $info['desc'] }}</div>@endif
          @if ($s === 'livree' && $isDone)
            <div class="suivi-step-date" style="color:#16A34A">{{ ['ar'=>'✓ تم التسليم','en'=>'✓ Delivered','fr'=>'✓ Livré'][$lang_code] ?? '✓ Livré' }}</div>
          @endif
        </div>
      </div>
      @endforeach
    </div>
    @else
    <div class="alert alert-error" style="margin-top:20px">{{ $steps[$current]['desc'] ?? $current }}</div>
    @endif
    @endif
  </div>
</div>
@endsection
