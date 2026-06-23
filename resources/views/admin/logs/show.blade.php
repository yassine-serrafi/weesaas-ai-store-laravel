@extends('admin.layouts.app')
@section('title', 'Détail génération')

@section('content')
<div class="page-header">
  <div>
    <div class="page-title">{!! $run['source_label'] !!} — {{ Str::limit($run['ref_label'], 50) }}</div>
    <div class="page-subtitle">Run <span class="tl-meta">{{ Str::limit($run['run_id'], 13, '…') }}</span> · démarré le {{ $run['started_full'] }}</div>
  </div>
  <a href="{{ route('admin.logs.index') }}" class="btn btn-ghost btn-sm">← Retour</a>
</div>

{{-- RÉSUMÉ --}}
<div class="kpi-grid">
  <div class="kpi-card"><div class="kpi-card-top"><span class="kpi-label">Statut</span></div><div class="kpi-value" style="font-size:20px"><span class="badge {{ $run['badge'] }}">{{ $run['status_label'] }}</span></div></div>
  <div class="kpi-card"><div class="kpi-card-top"><span class="kpi-label">Étape atteinte</span></div><div class="kpi-value">{{ $run['max_step'] }}@if($run['source'] === 'product')<span>/5</span>@endif</div></div>
  <div class="kpi-card"><div class="kpi-card-top"><span class="kpi-label">Durée totale</span></div><div class="kpi-value">{{ $run['duration'] }}</div></div>
  <div class="kpi-card"><div class="kpi-card-top"><span class="kpi-label">Événements</span></div><div class="kpi-value">{{ $run['lines'] }}</div></div>
</div>

@if ($run['ref_id'])
  @if ($run['source'] === 'product' && Route::has('admin.products.edit'))
    <a href="{{ route('admin.products.edit', $run['ref_id']) }}" class="btn btn-secondary btn-sm" style="margin-bottom:16px">Ouvrir le produit →</a>
  @elseif ($run['source'] === 'page' && Route::has('admin.pages.index'))
    <a href="{{ route('admin.pages.index') }}" class="btn btn-secondary btn-sm" style="margin-bottom:16px">Voir les pages →</a>
  @endif
@endif

{{-- TIMELINE --}}
<div class="card">
  <div class="card-header"><span class="card-title">Timeline détaillée</span></div>
  <div class="card-body">
    <div class="tl">
      @foreach ($lines as $l)
      <div class="tl-item">
        <span class="tl-dot {{ $l->level }}">{{ ['success' => '✓', 'error' => '✕', 'warning' => '!'][$l->level] ?? '·' }}</span>
        <div class="tl-head">
          @if ($l->step)<span class="tl-step">Étape {{ $l->step }}</span>@endif
          <span class="tl-msg">{{ $l->message }}</span>
          <span class="tl-meta">{{ $l->created_at?->format('H:i:s') }}@if($l->duration_ms) · +{{ $l->duration_ms }} ms @endif</span>
        </div>
        @if (!empty($l->context_json))
          <details class="tl-fold" style="margin-top:5px">
            <summary>contexte</summary>
            <div class="tl-ctx">{{ json_encode($l->context_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</div>
          </details>
        @endif
      </div>
      @endforeach
    </div>
  </div>
</div>
@endsection

@push('scripts')
@if ($run['status'] === 'running')
<script>
// Génération en cours : on rafraîchit la timeline automatiquement.
setTimeout(function () { location.reload(); }, 4000);
</script>
@endif
@endpush
