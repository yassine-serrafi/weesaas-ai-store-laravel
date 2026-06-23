@extends('admin.layouts.app')
@section('title', 'Logs')

@section('content')
<div class="page-header">
  <div><div class="page-title">🧾 Logs</div><div class="page-subtitle">Journal détaillé des générations et console système</div></div>
  <form method="post" action="{{ route('admin.logs.purge') }}" onsubmit="return confirm('Supprimer tous les logs de plus de 30 jours ?')">@csrf
    <button class="btn btn-ghost btn-sm">🗑️ Vider les logs &gt; 30 jours</button>
  </form>
</div>

{{-- STATS --}}
<div class="kpi-grid">
  <div class="kpi-card"><div class="kpi-card-top"><span class="kpi-label">Générations</span></div><div class="kpi-value">{{ number_format($stats['total'], 0, ',', ' ') }}</div></div>
  <div class="kpi-card"><div class="kpi-card-top"><span class="kpi-label">Aujourd'hui</span></div><div class="kpi-value">{{ $stats['today'] }}</div></div>
  <div class="kpi-card"><div class="kpi-card-top"><span class="kpi-label">Erreurs</span></div><div class="kpi-value" style="color:{{ $stats['errors'] ? 'var(--red)' : 'inherit' }}">{{ $stats['errors'] }}</div></div>
  <div class="kpi-card"><div class="kpi-card-top"><span class="kpi-label">Lignes de log</span></div><div class="kpi-value">{{ number_format($stats['lines'], 0, ',', ' ') }}</div></div>
</div>

{{-- TABS --}}
<div class="log-tabs">
  <a href="{{ route('admin.logs.index', ['tab' => 'generations']) }}" class="log-tab {{ $tab === 'generations' ? 'active' : '' }}">Générations</a>
  <a href="{{ route('admin.logs.index', ['tab' => 'system']) }}" class="log-tab {{ $tab === 'system' ? 'active' : '' }}">Système (laravel.log)</a>
</div>

@if ($tab === 'generations')
  {{-- FILTRES --}}
  <form method="get" class="card" style="margin-bottom:16px">
    <input type="hidden" name="tab" value="generations">
    <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="margin:0"><label class="form-label">Type</label>
        <select name="source" class="form-select" onchange="this.form.submit()">
          <option value="">Tous</option>
          <option value="product" @selected($source === 'product')>🖼️ Produit</option>
          <option value="page" @selected($source === 'page')>📄 Page</option>
        </select>
      </div>
      <div class="form-group" style="margin:0"><label class="form-label">Statut</label>
        <select name="status" class="form-select" onchange="this.form.submit()">
          <option value="">Tous</option>
          <option value="completed" @selected($status === 'completed')>Terminé</option>
          <option value="failed" @selected($status === 'failed')>Échec</option>
          <option value="running" @selected($status === 'running')>En cours</option>
        </select>
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:180px"><label class="form-label">Recherche</label>
        <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Nom du produit / page…">
      </div>
      <button class="btn btn-primary">Filtrer</button>
    </div>
  </form>

  <div class="card">
    <div class="card-header"><span class="card-title">Générations récentes</span><span id="live-tag" class="page-subtitle" style="margin:0">⟳ live</span></div>
    <div class="table-wrap"><table class="admin-table" id="runs-table">
      <thead><tr><th>Type</th><th>Élément</th><th>Statut</th><th>Étapes</th><th>Durée</th><th>Lignes</th><th>Démarré</th></tr></thead>
      <tbody id="runs-body">
        @forelse ($runs as $r)
        <tr class="log-run-row" data-href="{{ route('admin.logs.show', $r['run_id']) }}">
          <td>{!! $r['source_label'] !!}</td>
          <td style="font-weight:600">{{ Str::limit($r['ref_label'], 40) }}</td>
          <td><span class="badge {{ $r['badge'] }}">{{ $r['status_label'] }}</span></td>
          <td><span class="log-steps">@for ($i = 1; $i <= 5; $i++)<i class="{{ $r['status'] === 'failed' && $i === $r['max_step'] ? 'err' : ($i <= $r['max_step'] ? 'on' : '') }}"></i>@endfor</span></td>
          <td class="tl-meta">{{ $r['duration'] }}</td>
          <td>{{ $r['lines'] }}</td>
          <td class="tl-meta">{{ $r['started'] }}</td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:30px">Aucune génération enregistrée. Lancez une génération de produit ou de page.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </div>
@else
  {{-- CONSOLE SYSTÈME --}}
  <form method="get" style="display:flex;gap:8px;margin-bottom:12px">
    <input type="hidden" name="tab" value="system">
    <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Filtrer les lignes…" style="max-width:320px">
    <button class="btn btn-primary">Filtrer</button>
  </form>
  <div class="console">
    @forelse ($system as $e)
      <div class="console-line {{ $e['class'] }}">
        <span class="t">{{ $e['time'] }}</span>
        <span class="lvl">{{ $e['channel'] }}.{{ $e['level'] }}</span>
        <span class="m">{{ $e['message'] }}@if($e['detail'])<details class="tl-fold" style="margin-top:4px"><summary>trace</summary><div class="tl-ctx">{{ $e['detail'] }}</div></details>@endif</span>
      </div>
    @empty
      <div class="console-empty">storage/logs/laravel.log est vide ou introuvable.</div>
    @endforelse
  </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Lignes cliquables → détail du run
  document.querySelectorAll('.log-run-row').forEach(function (row) {
    row.addEventListener('click', function () { location.href = row.dataset.href; });
  });

  // Rafraîchissement live de la liste des générations (si onglet Générations, sans filtre).
  var body = document.getElementById('runs-body');
  @if ($tab === 'generations' && $source === '' && $status === '' && $search === '')
  var showBase = '{{ route('admin.logs.show', 'RID') }}';
  function badge(s){ return s==='completed'?'badge-active':(s==='failed'?'badge-annulee':'badge-generating'); }
  function lbl(s){ return s==='completed'?'Terminé':(s==='failed'?'Échec':'En cours'); }
  function steps(r){ var h=''; for(var i=1;i<=5;i++){var c=(r.status==='failed'&&i===r.max_step)?'err':(i<=r.max_step?'on':'');h+='<i class="'+c+'"></i>';} return h; }
  function esc(t){ var d=document.createElement('div'); d.textContent=t||''; return d.innerHTML; }
  function refresh(){
    fetch('{{ route('admin.logs.feed') }}').then(function(r){return r.json();}).then(function(d){
      if(!d.runs || !d.runs.length) return;
      body.innerHTML = d.runs.map(function(r){
        return '<tr class="log-run-row" data-href="'+showBase.replace('RID', r.run_id)+'">'
          +'<td>'+r.source_label+'</td>'
          +'<td style="font-weight:600">'+esc(r.ref_label)+'</td>'
          +'<td><span class="badge '+badge(r.status)+'">'+lbl(r.status)+'</span></td>'
          +'<td><span class="log-steps">'+steps(r)+'</span></td>'
          +'<td class="tl-meta">'+r.duration+'</td>'
          +'<td>'+r.lines+'</td>'
          +'<td class="tl-meta">'+r.started+'</td></tr>';
      }).join('');
      body.querySelectorAll('.log-run-row').forEach(function(row){ row.addEventListener('click', function(){ location.href = row.dataset.href; }); });
    }).catch(function(){});
  }
  setInterval(refresh, 5000);
  @endif
});
</script>
@endpush
