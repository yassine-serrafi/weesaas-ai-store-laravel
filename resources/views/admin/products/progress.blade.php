@extends('admin.layouts.app')
@section('title', 'Génération en cours')

@section('content')
<div class="card" style="max-width:560px;margin:0 auto">
  <div class="card-body" style="text-align:center">
    <h3 style="margin:0 0 6px;font-size:18px">Génération de la page produit</h3>
    <p id="gen-label" style="font-size:14px;color:var(--text-secondary);margin:0 0 20px">{{ $aiJob->step_label ?: 'Initialisation…' }}</p>

    <div class="gen-bar-track" style="background:var(--bg-page);border-radius:20px;height:14px;overflow:hidden;margin-bottom:10px">
      <div class="gen-bar-fill" id="gen-bar" style="height:100%;width:{{ $aiJob->progress_pct }}%;background:linear-gradient(90deg,#FF6B00,#ff9248);transition:width .4s"></div>
    </div>
    <div id="gen-pct" style="font-size:13px;color:var(--text-muted);margin-bottom:20px">{{ $aiJob->progress_pct }}%</div>

    <div id="gen-spinner" style="font-size:32px">⏳</div>
    <div id="gen-actions" style="display:none;margin-top:16px"></div>
    <div id="gen-error" class="alert alert-error" style="display:none;margin-top:16px;text-align:left"></div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  var statusUrl = @json(route('admin.products.generate.status', $aiJob->id));
  var productsUrl = @json(route('admin.products.index'));
  var bar = document.getElementById('gen-bar'), pct = document.getElementById('gen-pct'),
      label = document.getElementById('gen-label'), spinner = document.getElementById('gen-spinner'),
      actions = document.getElementById('gen-actions'), errBox = document.getElementById('gen-error');
  function poll() {
    fetch(statusUrl, {headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json()})
      .then(function(d){
        bar.style.width = (d.progress_pct || 0) + '%';
        pct.textContent = (d.progress_pct || 0) + '%';
        if (d.step_label) label.textContent = d.step_label;
        if (d.status === 'completed') {
          spinner.textContent = '✅'; actions.style.display = 'block';
          actions.innerHTML = '<a href="' + productsUrl + '" class="btn btn-primary btn-lg">Voir le produit →</a>';
          return;
        }
        if (d.status === 'failed') {
          spinner.textContent = '❌'; errBox.style.display = 'block';
          errBox.textContent = d.error || 'La génération a échoué.';
          return;
        }
        setTimeout(poll, 2000);
      })
      .catch(function(){ setTimeout(poll, 3000); });
  }
  poll();
})();
</script>
@endpush
@endsection
