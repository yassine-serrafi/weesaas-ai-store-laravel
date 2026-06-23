@extends('admin.layouts.app')
@section('title', 'Notifications')

@section('content')
<div class="page-header">
  <div><div class="page-title">🔔 Notifications</div><div class="page-subtitle">{{ $unread }} non lue(s)</div></div>
  <div style="display:flex;gap:8px">
    @if ($unread > 0)
    <form method="post" action="{{ route('admin.notifications.read-all') }}">@csrf<button class="btn btn-ghost btn-sm">Tout marquer lu</button></form>
    @endif
    <form method="post" action="{{ route('admin.notifications.clear-read') }}" onsubmit="return confirm('Supprimer toutes les notifications lues ?')">@csrf<button class="btn btn-ghost btn-sm">🗑️ Vider les lues</button></form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th style="width:90px">Type</th><th>Notification</th><th style="width:150px">Quand</th><th style="width:120px">Actions</th></tr></thead>
      <tbody>
        @forelse ($notifications as $n)
        <tr style="{{ $n->lu ? '' : 'background:var(--orange-light)' }}">
          <td><span class="badge badge-{{ $n->type === 'order' ? 'confirmee' : ($n->type === 'produit' ? 'active' : 'draft') }}">{{ $n->type }}</span></td>
          <td>
            <div style="font-weight:600">{{ $n->titre }}</div>
            @if ($n->message)<div style="font-size:12px;color:var(--text-muted);margin-top:2px">{{ $n->message }}</div>@endif
          </td>
          <td class="tl-meta">{{ $n->created_at?->diffForHumans() }}</td>
          <td>
            <div style="display:flex;gap:6px">
              @if ($n->lien)
              <form method="post" action="{{ route('admin.notifications.read', $n->id) }}">@csrf<button class="btn btn-secondary btn-sm">Ouvrir</button></form>
              @endif
              <form method="post" action="{{ route('admin.notifications.destroy', $n->id) }}">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">✕</button></form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">🔔</div><div class="empty-state-title">Aucune notification</div><div class="empty-state-text">Les nouvelles commandes et générations apparaîtront ici.</div></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<div style="margin-top:16px">{{ $notifications->links() }}</div>
@endsection
