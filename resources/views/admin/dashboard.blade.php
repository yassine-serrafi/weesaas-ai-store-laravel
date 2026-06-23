@extends('admin.layouts.app')
@section('title', 'Tableau de bord')

@section('content')

{{-- ───────── Barre LIVE (temps réel, auto-actualisée) ───────── --}}
<div class="live-bar">
  <span class="live-dot-wrap"><span class="live-dot"></span><strong id="live-count">0</strong>&nbsp;<span id="live-count-label">visiteur actif</span></span>
  <span class="live-sep">·</span>
  <span>Aujourd'hui : <strong id="live-ca">…</strong></span>
  <span class="live-sep">·</span>
  <span id="live-updated" style="color:#94a3b8;font-size:12px">Live</span>
  <button type="button" class="live-btn" id="live-toggle" onclick="window.weeToggleLive()">⏸ Live ON</button>
</div>

{{-- ───────── Bandeau « Argent encaissé » (wow) ───────── --}}
<div class="revenue-hero">
  <div class="revenue-hero-glow"></div>
  <div class="revenue-hero-main">
    <span class="revenue-hero-label">💰 Argent encaissé <em>(commandes livrées)</em></span>
    <div class="revenue-hero-value">
      {{ number_format($stats['ca_livre'], 0, ',', ' ') }}<span class="revenue-hero-cur">{{ $stats['devise'] }}</span>
    </div>
    <div class="revenue-hero-tags">
      <span class="rh-tag">📦 {{ $stats['nb_livrees'] }} livrées</span>
      <span class="rh-tag">🛒 Panier moyen {{ number_format($stats['panier_moyen'], 0, ',', ' ') }} {{ $stats['devise'] }}</span>
    </div>
  </div>
  <div class="revenue-hero-side">
    <div class="rh-metric">
      <span class="rh-metric-val">{{ $stats['taux_livraison'] }}%</span>
      <span class="rh-metric-lbl">Taux de livraison</span>
    </div>
    <div class="rh-metric">
      <span class="rh-metric-val">{{ number_format($stats['ca_en_cours'], 0, ',', ' ') }} {{ $stats['devise'] }}</span>
      <span class="rh-metric-lbl">{{ $stats['nb_en_cours'] }} en cours à livrer</span>
    </div>
  </div>
</div>

<div class="kpi-grid">
  <div class="kpi-card">
    <div class="kpi-card-top">
      <span class="kpi-label">Produits</span>
      <span class="kpi-icon kpi-icon-orange"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8z" clip-rule="evenodd"/></svg></span>
    </div>
    <div class="kpi-value">{{ $stats['produits'] }}</div>
    <div class="kpi-footer"><span class="kpi-vs">{{ $stats['produits_actifs'] }} actifs</span></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-card-top">
      <span class="kpi-label">Commandes</span>
      <span class="kpi-icon kpi-icon-blue"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222 1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/></svg></span>
    </div>
    <div class="kpi-value">{{ $stats['commandes'] }}</div>
    <div class="kpi-footer"><span class="kpi-vs">{{ $stats['commandes_jour'] }} aujourd'hui</span></div>
  </div>
  <div class="kpi-card">
    <div class="kpi-card-top">
      <span class="kpi-label">À livrer</span>
      <span class="kpi-icon kpi-icon-green"><svg viewBox="0 0 20 20" fill="currentColor"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v8a1 1 0 001 1h.05a2.5 2.5 0 014.9 0H10V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v5h.05a2.5 2.5 0 014.9 0H18a1 1 0 001-1v-2a1 1 0 00-.293-.707l-2-2A1 1 0 0016 7h-2z"/></svg></span>
    </div>
    <div class="kpi-value">{{ $stats['nb_en_cours'] }}</div>
    <div class="kpi-footer"><span class="kpi-vs">commandes confirmées en attente</span></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Dernières commandes</span></div>
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Réf.</th><th>Client</th><th>Produit</th><th>Total</th><th>Statut</th><th>Date</th></tr></thead>
      <tbody>
        @forelse ($dernieres as $o)
        <tr>
          <td class="order-ref">{{ $o->reference }}</td>
          <td>{{ $o->nom_client }}</td>
          <td>{{ Str::limit($o->product->nom_produit ?? '—', 30) }}</td>
          <td class="money">{{ number_format((float) $o->total_ttc, 0, ',', ' ') }} {{ $o->symbole_devise }}</td>
          <td><span class="badge badge-{{ $o->statut }}">{{ $o->statut }}</span></td>
          <td style="color:var(--text-muted)">{{ $o->created_at?->format('d/m H:i') }}</td>
        </tr>
        @empty
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">🛒</div><div class="empty-state-title">Aucune commande</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@push('scripts')
<script>
(function(){
  var btn = document.getElementById('live-toggle');
  if (!btn) return;
  var liveUrl = '{{ route('admin.analytics.live') }}';
  var active = true, timer = null;
  function fmt(n){ return Number(n||0).toLocaleString('fr-FR'); }
  function set(id, val){ var el = document.getElementById(id); if (el) el.textContent = val; }
  function fetchLive(){
    fetch(liveUrl).then(function(r){ return r.json(); }).then(function(d){
      set('live-count', d.actifs);
      set('live-count-label', d.actifs > 1 ? 'visiteurs actifs' : 'visiteur actif');
      set('live-ca', fmt(d.ca_today));
      set('live-updated', 'Maj ' + d.updated_at);
    }).catch(function(){});
  }
  function start(){ fetchLive(); timer = setInterval(fetchLive, 20000); active = true; btn.textContent = '⏸ Live ON'; btn.style.background = '#1e40af'; }
  function stop(){ clearInterval(timer); active = false; btn.textContent = '▶ Live OFF'; btn.style.background = '#64748b'; }
  window.weeToggleLive = function(){ active ? stop() : start(); };
  start();
})();
</script>
@endpush

{{-- ───────── Nouveaux clients ───────── --}}
<div class="card">
  <div class="card-header">
    <span class="card-title">✨ Nouveaux clients</span>
    <div class="card-header-right">
      @if ($stats['nouveaux_mois'] > 0)<span class="client-month-badge">+{{ $stats['nouveaux_mois'] }} ce mois-ci</span>@endif
      <a href="{{ route('admin.clients.index') }}" class="card-link">Voir tous →</a>
    </div>
  </div>

  @if ($nouveauxClients->isEmpty())
    <div class="empty-state"><div class="empty-state-icon">👥</div><div class="empty-state-title">Aucun client pour le moment</div></div>
  @else
  <div class="clients-grid">
    @foreach ($nouveauxClients as $c)
      @php
        $parts = preg_split('/\s+/', trim((string) ($c->nom_client ?: '?')));
        $ini = mb_strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
        $isNew = \Illuminate\Support\Carbon::parse($c->premiere_commande)->gt(now()->subDays(7));
      @endphp
      <div class="client-card">
        <div class="client-avatar">{{ $ini ?: '?' }}</div>
        <div class="client-info">
          <div class="client-name">
            {{ Str::limit($c->nom_client ?: 'Client', 18) }}
            @if ($isNew)<span class="client-new">Nouveau</span>@endif
          </div>
          <div class="client-meta">{{ $c->telephone }}@if ($c->ville) · {{ $c->ville }}@endif</div>
        </div>
        <div class="client-stats">
          <span class="client-spent">{{ number_format((float) $c->total_depense, 0, ',', ' ') }} {{ $stats['devise'] }}</span>
          <span class="client-orders">{{ $c->nb_commandes }} cmd</span>
        </div>
      </div>
    @endforeach
  </div>
  @endif
</div>
@endsection
