@extends('admin.layouts.app')
@section('title', 'Analytics')

@section('content')

{{-- BARRE LIVE --}}
<div class="live-bar">
  <span class="live-dot-wrap"><span class="live-dot"></span><strong id="live-count">{{ $live }}</strong> visiteur{{ $live > 1 ? 's' : '' }} actif{{ $live > 1 ? 's' : '' }}</span>
  <span class="live-sep">·</span>
  <span>Aujourd'hui : <strong id="live-ca">…</strong></span>
  <span class="live-sep">·</span>
  <span id="live-updated" style="color:#94a3b8;font-size:12px">Live</span>
  <button type="button" class="live-btn" id="live-toggle" onclick="window.weeToggleLive()">⏸ Live ON</button>
</div>

<div class="page-header">
  <div>
    <div class="page-title">📊 Analytics</div>
    <div class="page-subtitle">{{ $product->nom_produit ?? 'Tous les produits' }} — {{ $period }} derniers jours</div>
  </div>
  <form method="get" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <select name="product_id" class="form-select" style="min-width:170px" onchange="this.form.submit()">
      <option value="">Tous les produits</option>
      @foreach ($productsList as $pl)
        <option value="{{ $pl->id }}" @selected($productId === $pl->id)>{{ Str::limit($pl->nom_produit, 32) }}</option>
      @endforeach
    </select>
    <select name="period" class="form-select" onchange="this.form.submit()">
      @foreach (['7'=>'7 jours','30'=>'30 jours','90'=>'90 jours'] as $v => $lbl)
        <option value="{{ $v }}" @selected($period === (int) $v)>{{ $lbl }}</option>
      @endforeach
    </select>
  </form>
</div>

{{-- KPIs --}}
<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-card-top"><span class="kpi-label">Visiteurs uniques</span><div class="kpi-icon kpi-icon-blue"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg></div></div>
    <div class="kpi-value">{{ number_format($vuesUniques, 0, ',', ' ') }}</div>
    <div class="kpi-footer"><span class="kpi-vs">{{ number_format($vues, 0, ',', ' ') }} vues totales</span></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-card-top"><span class="kpi-label">Commandes</span><div class="kpi-icon kpi-icon-orange"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/></svg></div></div>
    <div class="kpi-value">{{ number_format($commandes, 0, ',', ' ') }}</div>
    <div class="kpi-footer"><span class="kpi-vs">CA : {{ number_format($ca, 0, ',', ' ') }}</span></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-card-top"><span class="kpi-label">Taux conversion</span><div class="kpi-icon kpi-icon-green"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/></svg></div></div>
    <div class="kpi-value">{{ $conversion }}%</div>
    <div class="kpi-footer"><span class="kpi-vs">Visiteurs → Acheteurs</span></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-card-top"><span class="kpi-label">Panier moyen</span><div class="kpi-icon kpi-icon-amber"><svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/></svg></div></div>
    <div class="kpi-value">{{ number_format($panierMoyen, 0, ',', ' ') }}</div>
    <div class="kpi-footer"><span class="kpi-vs">Par commande</span></div>
  </div>
</div>

{{-- GRAPHE TRAFIC --}}
<div class="chart-wrap" style="margin-bottom:16px">
  <div class="chart-header" style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px">
    <div><div class="card-title">Trafic &amp; Conversions</div><div class="page-subtitle" style="margin:0">Visiteurs uniques et commandes par jour</div></div>
    <div style="display:flex;gap:12px;font-size:12px;color:var(--text-muted)">
      <span><span style="display:inline-block;width:10px;height:3px;background:#2563EB;border-radius:2px;vertical-align:middle"></span> Visiteurs</span>
      <span><span style="display:inline-block;width:10px;height:3px;background:#FF6B00;border-radius:2px;vertical-align:middle"></span> Commandes</span>
    </div>
  </div>
  <div class="chart-canvas-wrap"><canvas id="chart-trafic"></canvas></div>
</div>

{{-- TOP PRODUITS --}}
<div class="card" style="margin-bottom:16px">
  <div class="card-header"><span class="card-title">🏆 Top produits — visites &amp; conversions</span></div>
  <div class="table-wrap"><table class="admin-table">
    <thead><tr><th>#</th><th>Produit</th><th style="text-align:right">Vues</th><th style="text-align:right">Uniques</th><th style="text-align:right">Cmd</th><th style="text-align:right">Conv.</th></tr></thead>
    <tbody>
      @forelse ($topProduits as $i => $p)
      <tr>
        <td style="font-weight:700;color:var(--orange)">{{ $i + 1 }}</td>
        <td>@if($p->id)<a href="{{ route('admin.products.edit', $p->id) }}" style="color:inherit;text-decoration:none;font-weight:600">{{ Str::limit($p->nom, 40) }}</a>@else{{ Str::limit($p->nom, 40) }}@endif</td>
        <td style="text-align:right;font-weight:700">{{ number_format($p->vues, 0, ',', ' ') }}</td>
        <td style="text-align:right">{{ number_format($p->uniques, 0, ',', ' ') }}</td>
        <td style="text-align:right">{{ $p->cmd }}</td>
        <td style="text-align:right"><span class="badge {{ $p->conv >= 3 ? 'badge-active' : ($p->conv >= 1 ? 'badge-paused' : 'badge-draft') }}">{{ $p->conv }}%</span></td>
      </tr>
      @empty
      <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:24px">Aucune visite enregistrée sur cette période.</td></tr>
      @endforelse
    </tbody>
  </table></div>
</div>

{{-- PAGES LES PLUS VISITÉES (tout le site) --}}
<div class="card" style="margin-bottom:16px">
  <div class="card-header"><span class="card-title">📄 Pages les plus visitées</span><span class="page-subtitle" style="margin:0">Audience par page sur tout le site</span></div>
  <div class="table-wrap"><table class="admin-table">
    <thead><tr><th>#</th><th>Page</th><th>Chemin</th><th style="width:34%">Visiteurs uniques</th><th style="text-align:right">Vues</th></tr></thead>
    <tbody>
      @forelse ($pages as $i => $pg)
      <tr>
        <td style="font-weight:700;color:var(--orange)">{{ $i + 1 }}</td>
        <td style="font-weight:600">{{ $pg->label }}</td>
        <td><a href="{{ site_url(ltrim($pg->path, '/')) }}" target="_blank" style="color:var(--text-muted);text-decoration:none;font-family:'DM Mono',monospace;font-size:12px">{{ $pg->path }}</a></td>
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div style="flex:1;height:6px;background:var(--bg-page);border-radius:3px;overflow:hidden"><div style="width:{{ round($pg->vues / $pagesMax * 100) }}%;height:100%;background:linear-gradient(90deg,#FF6B00,#ff9a52);border-radius:3px"></div></div>
            <span style="font-size:12px;min-width:28px;text-align:right">{{ number_format($pg->uniques, 0, ',', ' ') }}</span>
          </div>
        </td>
        <td style="text-align:right;font-weight:700">{{ number_format($pg->vues, 0, ',', ' ') }}</td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:24px">Aucune visite enregistrée sur cette période.</td></tr>
      @endforelse
    </tbody>
  </table></div>
</div>

{{-- FUNNEL / VILLES / DEVICES+SOURCES --}}
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;align-items:start">

  <div class="card">
    <div class="card-header"><span class="card-title">Funnel de conversion</span></div>
    <div class="card-body">
      @foreach ($funnel as $fi => $fs)
        @php $pct = round($fs['val'] / $funnelMax * 100);
             $drop = ($fi > 0 && $funnel[$fi-1]['val'] > 0) ? round((1 - $fs['val'] / max(1, $funnel[$fi-1]['val'])) * 100) : null; @endphp
        <div style="margin-bottom:13px">
          <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px">
            <span style="font-weight:600">{{ $fs['label'] }}</span>
            <span style="display:flex;gap:8px;align-items:center">@if($drop !== null && $drop > 0)<span style="font-size:11px;color:var(--red)">-{{ $drop }}%</span>@endif<strong>{{ number_format($fs['val'], 0, ',', ' ') }}</strong></span>
          </div>
          <div style="height:8px;background:var(--bg-page);border-radius:4px;overflow:hidden"><div style="width:{{ $pct }}%;height:100%;background:{{ $fs['color'] }};border-radius:4px;transition:width .5s"></div></div>
        </div>
      @endforeach
      @if (($funnel[1]['val'] ?? 0) === 0 && ($funnel[2]['val'] ?? 0) === 0)
        <p style="font-size:11px;color:var(--text-muted);margin-top:10px">ℹ️ Les étapes intermédiaires nécessitent le tracking JS actif sur les pages produit.</p>
      @endif
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">📍 Top villes</span></div>
    <div class="card-body">
      @php $maxV = max(1, $villes->max('n') ?? 1); @endphp
      @forelse ($villes as $vi => $v)
        <div style="margin-bottom:10px">
          <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px"><span>{{ $vi + 1 }}. {{ $v->ville }}</span><strong>{{ $v->n }}</strong></div>
          <div style="height:5px;background:var(--bg-page);border-radius:3px;overflow:hidden"><div style="width:{{ round($v->n / $maxV * 100) }}%;height:100%;background:var(--orange);border-radius:3px"></div></div>
        </div>
      @empty
        <p style="color:var(--text-muted);font-size:13px">Aucune commande encore.</p>
      @endforelse
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
      <div class="card-header"><span class="card-title">📱 Devices</span></div>
      <div class="card-body">
        <div style="display:flex;gap:16px;margin-bottom:12px">
          <div style="flex:1;text-align:center"><div style="font-size:26px;font-weight:800;color:#2563EB">{{ $mobilePct }}%</div><div style="font-size:12px;color:var(--text-muted)">📱 Mobile</div></div>
          <div style="flex:1;text-align:center"><div style="font-size:26px;font-weight:800;color:#7C3AED">{{ $desktopPct }}%</div><div style="font-size:12px;color:var(--text-muted)">💻 Desktop</div></div>
        </div>
        <div style="height:10px;background:var(--bg-page);border-radius:5px;overflow:hidden;display:flex"><div style="width:{{ $mobilePct }}%;background:#2563EB"></div><div style="width:{{ $desktopPct }}%;background:#7C3AED"></div></div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title">🔗 Sources</span></div>
      <div class="card-body">
        @forelse ($sources as $s)
          <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border-light);font-size:13px"><span>{{ $s->source ?: 'direct' }}</span><span class="badge badge-draft">{{ $s->n }}</span></div>
        @empty
          <p style="color:var(--text-muted);font-size:13px">Aucune source.</p>
        @endforelse
      </div>
    </div>
  </div>

</div>
@endsection

@push('head')
<style>
.live-bar{display:flex;align-items:center;gap:14px;flex-wrap:wrap;background:linear-gradient(90deg,#0f172a,#1e293b);color:#e2e8f0;border-radius:12px;padding:10px 18px;font-size:13px;margin-bottom:14px}
.live-dot-wrap{display:flex;align-items:center;gap:8px}
.live-dot{width:8px;height:8px;border-radius:50%;background:#22c55e;animation:livePulse 1.6s infinite}
@keyframes livePulse{0%,100%{box-shadow:0 0 0 0 rgba(34,197,94,.5)}50%{box-shadow:0 0 0 6px rgba(34,197,94,0)}}
.live-sep{color:#475569}
.live-btn{margin-inline-start:auto;background:#1e40af;color:#fff;border:none;border-radius:6px;padding:4px 12px;font-size:12px;cursor:pointer;transition:background .2s}
.live-btn:hover{background:#1d4ed8}
@media(max-width:880px){.card-body + .card-body,[style*="grid-template-columns:1fr 1fr 1fr"]{grid-template-columns:1fr !important}}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // ── Graphe trafic (double axe) ──────────────────────────────────────────
  var ctx = document.getElementById('chart-trafic');
  if (ctx && window.Chart) {
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: @json($chartLabels),
        datasets: [
          { label:'Visiteurs', data:@json($chartVues), borderColor:'#2563EB', backgroundColor:'rgba(37,99,235,.07)', fill:true, tension:.4, borderWidth:2, pointRadius:0, pointHoverRadius:4, yAxisID:'y' },
          { label:'Commandes', data:@json($chartCmd), borderColor:'#FF6B00', backgroundColor:'rgba(255,107,0,.1)', fill:true, tension:.4, borderWidth:2, pointRadius:0, pointHoverRadius:4, yAxisID:'y1' }
        ]
      },
      options: {
        responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
        scales:{
          y:{type:'linear',position:'left',beginAtZero:true,grid:{color:'#f0f0f0'},ticks:{color:'#999',font:{size:11},precision:0}},
          y1:{type:'linear',position:'right',beginAtZero:true,grid:{drawOnChartArea:false},ticks:{color:'#FF6B00',font:{size:11},precision:0}},
          x:{grid:{display:false},ticks:{color:'#999',font:{size:11},maxTicksLimit:10}}
        },
        plugins:{legend:{display:false}}
      }
    });
  }

  // ── Live polling ────────────────────────────────────────────────────────
  var liveUrl = '{{ route('admin.analytics.live') }}?product_id={{ $productId }}';
  var active = true, timer = null, btn = document.getElementById('live-toggle');
  function fmt(n){ return Number(n||0).toLocaleString('fr-FR'); }
  function fetchLive(){
    fetch(liveUrl).then(function(r){return r.json();}).then(function(d){
      document.getElementById('live-count').textContent = d.actifs;
      document.getElementById('live-ca').textContent = fmt(d.ca_today);
      document.getElementById('live-updated').textContent = 'Maj ' + d.updated_at;
    }).catch(function(){});
  }
  function start(){ fetchLive(); timer = setInterval(fetchLive, 30000); active = true; btn.textContent = '⏸ Live ON'; btn.style.background = '#1e40af'; }
  function stop(){ clearInterval(timer); active = false; btn.textContent = '▶ Live OFF'; btn.style.background = '#64748b'; }
  window.weeToggleLive = function(){ active ? stop() : start(); };
  start();
});
</script>
@endpush
