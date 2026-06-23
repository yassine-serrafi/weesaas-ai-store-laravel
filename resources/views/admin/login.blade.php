<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Connexion Admin — WeeSaaS</title>
  <link rel="stylesheet" href="{{ site_url('assets/css/admin.css') }}">
  <style>
    body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#F7F7F5}
    .login-card{background:#fff;border:1px solid #ebebeb;border-radius:16px;padding:40px;width:100%;max-width:400px;box-shadow:0 4px 24px rgba(0,0,0,.06)}
    .login-logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;justify-content:center}
    .login-logo-icon{width:40px;height:40px;border-radius:10px;background:#FF6B00;display:flex;align-items:center;justify-content:center}
    .login-title{font-size:22px;font-weight:800;text-align:center;margin-bottom:4px}
    .login-sub{font-size:13px;color:#999;text-align:center;margin-bottom:28px}
    .login-form .form-group{margin-bottom:16px}
    .login-form .form-label{display:block;font-size:13px;font-weight:600;margin-bottom:6px}
    .login-form .form-control{width:100%;padding:11px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:14px;outline:none;font-family:inherit;background:#fff;transition:border-color .15s}
    .login-form .form-control:focus{border-color:#FF6B00;box-shadow:0 0 0 3px #FFF4EE}
    .login-form .btn-submit{width:100%;padding:13px;background:#FF6B00;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;margin-top:4px}
    .login-form .btn-submit:hover{background:#E05A00}
    .login-error{background:#FEF2F2;color:#DC2626;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:16px}
    .login-footer{text-align:center;font-size:11px;color:#bbb;margin-top:20px}
    .pw-wrap{position:relative}
    .pw-toggle{position:absolute;inset-block:0;inset-inline-end:12px;display:flex;align-items:center;cursor:pointer;color:#999}
    .pw-toggle:hover{color:#555}
    .pw-toggle svg{width:16px;height:16px}
  </style>
</head>
<body>
<div class="login-card">
  <div class="login-logo">
    <div class="login-logo-icon">
      <svg width="22" height="22" viewBox="0 0 20 20" fill="white"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
    </div>
    <span style="font-size:20px;font-weight:800">Wee<span style="color:#FF6B00">SaaS</span></span>
  </div>
  <h1 class="login-title">Connexion</h1>
  <p class="login-sub">Panneau d'administration</p>

  @if (!empty($error))
  <div class="login-error">⚠ {{ $error }}</div>
  @endif

  <form method="post" action="{{ route('admin.login.post') }}" class="login-form" autocomplete="on">
    @csrf
    <div class="form-group">
      <label class="form-label" for="login">Identifiant</label>
      <input type="text" id="login" name="login" class="form-control" value="{{ old('login') }}"
             autocomplete="username" required autofocus placeholder="admin@votreboutique.com">
    </div>
    <div class="form-group">
      <label class="form-label" for="password">Mot de passe</label>
      <div class="pw-wrap">
        <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required placeholder="••••••••">
        <span class="pw-toggle" onclick="var i=document.getElementById('password');i.type=i.type==='password'?'text':'password'">
          <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </span>
      </div>
    </div>
    <button type="submit" class="btn-submit">Se connecter →</button>
  </form>
  <div class="login-footer">WeeSaaS © {{ date('Y') }} — Accès sécurisé</div>
</div>
</body>
</html>
