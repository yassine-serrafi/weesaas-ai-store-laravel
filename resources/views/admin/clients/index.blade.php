@extends('admin.layouts.app')
@section('title', 'Clients')

@section('content')
<div class="page-header">
  <div><div class="page-title">Clients</div><div class="page-subtitle">{{ $clients->total() }} client(s) — agrégés par téléphone</div></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Client</th><th>Téléphone</th><th>Ville</th><th>Commandes</th><th>Total dépensé</th><th>Dernière commande</th></tr></thead>
      <tbody>
        @forelse ($clients as $c)
        <tr>
          <td><strong>{{ $c->nom_client ?: '—' }}</strong></td>
          <td>{{ $c->telephone }}</td>
          <td>{{ $c->ville }}</td>
          <td>{{ $c->nb_commandes }}</td>
          <td class="money">{{ number_format((float) $c->total_depense, 0, ',', ' ') }}</td>
          <td style="color:var(--text-muted)">{{ \Illuminate\Support\Carbon::parse($c->derniere_commande)->format('d/m/Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">👥</div><div class="empty-state-title">Aucun client</div><div class="empty-state-text">Les clients apparaîtront dès la première commande.</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<div style="margin-top:16px">{{ $clients->links() }}</div>
@endsection
