@php
  // Icônes SVG de la sidebar (reprises de la charte admin legacy).
  $icons = [
    'home'  => '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>',
    'box'   => '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/></svg>',
    'cart'  => '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>',
    'file'  => '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/></svg>',
    'star'  => '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>',
    'gear'  => '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>',
    'chart' => '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/></svg>',
    'users' => '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/></svg>',
    'menu'  => '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>',
    'tag'   => '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17.707 9.293a1 1 0 010 1.414l-7 7a1 1 0 01-1.414 0l-7-7A.997.997 0 012 10V5a3 3 0 013-3h5c.256 0 .512.098.707.293l7 7zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>',
    'terminal' => '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2H3zm2.293 4.293a1 1 0 011.414 0l2 2a1 1 0 010 1.414l-2 2a1 1 0 11-1.414-1.414L6.586 10 5.293 8.707a1 1 0 010-1.414zM11 12a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>',
  ];
  $nav = [
    ['route'=>'admin.dashboard','match'=>'admin.dashboard','icon'=>'home','label'=>'Tableau de bord'],
    ['route'=>'admin.products.index','match'=>'admin.products.*','icon'=>'box','label'=>'Produits'],
    ['route'=>'admin.orders.index','match'=>'admin.orders.*','icon'=>'cart','label'=>'Commandes'],
    ['route'=>'admin.analytics.index','match'=>'admin.analytics.*','icon'=>'chart','label'=>'Analytics'],
    ['route'=>'admin.clients.index','match'=>'admin.clients.*','icon'=>'users','label'=>'Clients'],
    ['section'=>'Contenu'],
    ['route'=>'admin.pages.index','match'=>'admin.pages.*','icon'=>'file','label'=>'Pages'],
    ['route'=>'admin.menus.index','match'=>'admin.menus.*','icon'=>'menu','label'=>'Gestion Menu'],
    ['route'=>'admin.avis.index','match'=>'admin.avis.*','icon'=>'star','label'=>'Avis'],
    ['route'=>'admin.promotions.index','match'=>'admin.promotions.*','icon'=>'tag','label'=>'Promotions'],
    ['section'=>'Configuration'],
    ['route'=>'admin.settings.edit','match'=>'admin.settings.*','icon'=>'gear','label'=>'Paramètres'],
    ['route'=>'admin.logs.index','match'=>'admin.logs.*','icon'=>'terminal','label'=>'Logs'],
  ];
  $adminName = $currentAdmin->username ?? 'Admin';
  $navNotifs = \App\Models\Notification::orderByDesc('id')->limit(8)->get();
  $navNotifUnread = \App\Models\Notification::where('lu', false)->count();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>@yield('title', 'Admin') — WeeSaaS Admin</title>
  <link rel="stylesheet" href="{{ asset_v('assets/css/admin.css') }}">
  @stack('head')
</head>
<body>
<div class="admin-layout">

  <aside class="admin-sidebar">
    <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
      <div class="sidebar-logo-icon">
        <svg width="18" height="18" viewBox="0 0 20 20" fill="white"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
      </div>
      <span class="sidebar-logo-text">Wee<span>SaaS</span></span>
    </a>

    <nav class="sidebar-nav">
      @foreach ($nav as $item)
        @if (!empty($item['section']))
          <div class="sidebar-section-label">{{ $item['section'] }}</div>
        @elseif (Route::has($item['route']))
          <a href="{{ route($item['route']) }}" class="sidebar-item {{ request()->routeIs($item['match']) ? 'active' : '' }}">
            {!! $icons[$item['icon']] !!}
            <span>{{ $item['label'] }}</span>
          </a>
        @endif
      @endforeach
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-avatar">{{ mb_strtoupper(mb_substr($adminName, 0, 1)) }}</div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name">{{ $adminName }}</div>
          <div class="sidebar-user-role">Administrateur</div>
        </div>
        <form method="post" action="{{ route('admin.logout') }}" style="margin-inline-start:auto">@csrf
          <button class="btn-ghost btn-sm" title="Déconnexion" style="border:none;background:none;cursor:pointer;color:#999;padding:4px">
            <svg width="15" height="15" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/></svg>
          </button>
        </form>
      </div>
      <a href="{{ site_url() }}" target="_blank" style="display:flex;align-items:center;gap:6px;padding:6px 12px;font-size:12px;color:#999;text-decoration:none;margin-top:4px">
        <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/><path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/></svg>
        Voir la boutique
      </a>
    </div>
  </aside>

  <main class="admin-main">
    <header class="admin-header">
      <div class="admin-header-left">
        <h1 class="admin-header-title">@yield('title', 'Admin')</h1>
      </div>
      <div class="admin-header-right">
        <div class="notif-bell" id="notif-bell">
          <button type="button" class="notif-bell-btn" onclick="document.getElementById('notif-bell').classList.toggle('open')" aria-label="Notifications">
            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-2.83-2h5.66A3 3 0 0110 18z"/></svg>
            @if ($navNotifUnread > 0)<span class="notif-badge">{{ $navNotifUnread > 9 ? '9+' : $navNotifUnread }}</span>@endif
          </button>
          <div class="notif-dropdown">
            <div class="notif-dropdown-head">
              <span>Notifications @if ($navNotifUnread > 0)<span class="notif-head-count">{{ $navNotifUnread }}</span>@endif</span>
              @if ($navNotifUnread > 0)
              <form method="post" action="{{ route('admin.notifications.read-all') }}">@csrf<button class="notif-mark-all" type="submit">Tout marquer lu</button></form>
              @endif
            </div>
            <div class="notif-list">
              @forelse ($navNotifs as $n)
              <form method="post" action="{{ route('admin.notifications.read', $n->id) }}" class="notif-item {{ $n->lu ? '' : 'unread' }}">@csrf
                <button type="submit" class="notif-item-btn">
                  <div class="notif-item-title">{{ $n->titre }}</div>
                  @if ($n->message)<div class="notif-item-msg">{{ Str::limit($n->message, 72) }}</div>@endif
                  <div class="notif-item-time">{{ $n->created_at?->diffForHumans() }}</div>
                </button>
              </form>
              @empty
              <div class="notif-empty">Aucune notification</div>
              @endforelse
            </div>
            <a href="{{ route('admin.notifications.index') }}" class="notif-see-all">Voir toutes les notifications →</a>
          </div>
        </div>
        @if (Route::has('admin.products.create'))
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
          <svg viewBox="0 0 20 20" fill="currentColor" width="12" height="12"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
          Nouveau produit
        </a>
        @endif
      </div>
    </header>

    <div class="admin-content">
      @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
      @if (session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
      @yield('content')
    </div>
  </main>
</div>
<script>
// Ferme le menu notifications au clic à l'extérieur.
document.addEventListener('click', function (e) {
  var bell = document.getElementById('notif-bell');
  if (bell && bell.classList.contains('open') && !bell.contains(e.target)) {
    bell.classList.remove('open');
  }
});
</script>
@stack('scripts')
</body>
</html>
