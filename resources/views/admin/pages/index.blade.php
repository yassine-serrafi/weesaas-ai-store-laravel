@extends('admin.layouts.app')
@section('title', 'Pages')

@section('content')
<div class="page-header">
  <div><div class="page-title">Pages</div><div class="page-subtitle">Pages institutionnelles générées par IA (Qui sommes-nous, CGV, FAQ…)</div></div>
  <div class="page-actions"><a href="{{ route('admin.pages.create') }}" class="btn btn-primary">＋ Créer une page</a></div>
</div>

@if ($pages->isEmpty())
<div class="card"><div class="card-body"><div class="empty-state">
  <div class="empty-state-icon">📄</div>
  <div class="empty-state-title">Aucune page créée</div>
  <div class="empty-state-text">Créez votre première page institutionnelle avec l'IA en quelques secondes.</div>
  <a href="{{ route('admin.pages.create') }}" class="btn btn-primary" style="margin-top:14px">Créer une page</a>
</div></div></div>
@else
<div class="card">
  <div class="table-wrap">
    <table class="admin-table">
      <thead><tr><th>Type</th><th>Titre</th><th>URL</th><th>Langue</th><th>Menus</th><th>Statut</th><th>Actions</th></tr></thead>
      <tbody>
        @foreach ($pages as $pg)
        @php $tl = $types[$pg->type] ?? ['emoji'=>'📄','label'=>$pg->type]; $url = site_url('pages/'.$pg->slug.'/'); @endphp
        <tr>
          <td><span style="font-size:18px">{{ $tl['emoji'] }}</span> <span style="color:var(--text-muted);font-size:12px">{{ $tl['label'] }}</span></td>
          <td><strong>{{ $pg->titre }}</strong></td>
          <td><a href="{{ $url }}" target="_blank" class="product-slug" style="text-decoration:none">/{{ $pg->slug }}/ ↗</a></td>
          <td>{{ ['fr'=>'🇫🇷 FR','en'=>'🇬🇧 EN','ar_marocain'=>'🇲🇦 AR','ar_standard'=>'🌍 AR','ar_golfe'=>'🇸🇦 AR'][$pg->langue] ?? $pg->langue }}</td>
          <td style="font-size:11px;color:var(--text-muted)">{{ $pg->show_in_header_menu ? '🔝 En-tête ' : '' }}{{ $pg->show_in_footer_menu ? '⬇️ Pied' : '' }}{{ (!$pg->show_in_header_menu && !$pg->show_in_footer_menu) ? '—' : '' }}</td>
          <td><span class="badge badge-{{ $pg->status === 'active' ? 'active' : 'draft' }}">{{ $pg->status === 'active' ? 'Publié' : 'Brouillon' }}</span></td>
          <td>
            <div style="display:flex;gap:6px;align-items:center">
              <form method="post" action="{{ route('admin.pages.status', $pg->id) }}">@csrf<button class="btn btn-ghost btn-sm">{{ $pg->status === 'active' ? 'Dépublier' : 'Publier' }}</button></form>
              <a href="{{ $url }}" target="_blank" class="btn btn-secondary btn-sm">Voir</a>
              <form method="post" action="{{ route('admin.pages.destroy', $pg->id) }}" onsubmit="return confirm('Supprimer cette page ?')">@csrf @method('DELETE')<button class="btn btn-danger btn-sm">✕</button></form>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif
@endsection
