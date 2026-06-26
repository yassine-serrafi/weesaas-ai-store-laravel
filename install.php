<?php
/**
 * ============================================================================
 *  WeeSaaS — Assistant d'installation web (Laravel)
 * ============================================================================
 *  Fichier autonome à placer dans public/. Il :
 *    1. vérifie l'environnement (PHP, extensions, droits d'écriture, vendor)
 *    2. demande la base de données, l'URL du site et le compte admin
 *    3. écrit le .env, génère APP_KEY, lance les migrations, storage:link
 *    4. crée l'administrateur (/weeadmin) + les réglages de base
 *    5. se verrouille (storage/installed.lock) puis peut s'auto-supprimer
 *
 *  Sécurité : refuse de tourner si déjà installé ; à supprimer après usage.
 *  Détection auto de vendor/ : si absent, tentative de `composer install`.
 * ----------------------------------------------------------------------------
 */

declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '1');
@set_time_limit(0);

define('BASE_PATH',  dirname(__DIR__));
define('ENV_FILE',   BASE_PATH . '/.env');
define('ENV_SAMPLE', BASE_PATH . '/.env.example');
define('LOCK_FILE',  BASE_PATH . '/storage/installed.lock');
define('AUTOLOAD',   BASE_PATH . '/vendor/autoload.php');

/** URL de base de l'app (gère les sous-dossiers) : .../public */
function app_base_url(): string
{
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $dir === '' ? '' : $dir;
}

/* ───────────────────────── Garde-fou « déjà installé » ─────────────────── */
$lockExists = is_file(LOCK_FILE);
$keySet     = is_file(ENV_FILE) && preg_match('/^APP_KEY=base64:.+/m', (string) @file_get_contents(ENV_FILE));
if (($lockExists || $keySet) && (($_REQUEST['force'] ?? '') !== '1')) {
    render_locked();
    exit;
}

$action = $_POST['action'] ?? '';

/* ───────────────────────── Action : auto-suppression ───────────────────── */
if ($action === 'delete') {
    @touch(LOCK_FILE);
    $self    = __FILE__;
    $deleted = @unlink($self);
    $target  = app_base_url() . '/weeadmin';
    if ($deleted) {
        header('Location: ' . $target);
        exit;
    }
    render_page('Suppression manuelle requise', '
        <div class="alert err">Impossible de supprimer automatiquement <code>install.php</code>
        (droits insuffisants). <strong>Supprime-le manuellement</strong> via ton FTP/cPanel.</div>
        <a class="btn" href="' . htmlspecialchars($target) . '">Aller sur l\'administration →</a>');
    exit;
}

/* ───────────────────────── Action : installation ───────────────────────── */
if ($action === 'install') {
    handle_install();
    exit;
}

/* ───────────────────────── Vue par défaut : formulaire ─────────────────── */
render_form();
exit;


/* ════════════════════════════════════════════════════════════════════════
 *  LOGIQUE
 * ══════════════════════════════════════════════════════════════════════ */

/** Pré-vérifications système. Retourne [['label','ok','hint'], ...]. */
function requirements(): array
{
    $exts = ['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'gd', 'curl', 'zip', 'intl'];
    $checks = [];

    $checks[] = [
        'label' => 'PHP ≥ 8.2 (' . PHP_VERSION . ')',
        'ok'    => version_compare(PHP_VERSION, '8.2.0', '>='),
        'hint'  => 'Mets à jour PHP en 8.2 ou supérieur.',
    ];
    foreach ($exts as $e) {
        $checks[] = [
            'label' => 'Extension ' . $e,
            'ok'    => extension_loaded($e),
            'hint'  => "Active l'extension PHP « $e ».",
        ];
    }
    foreach (['.env (racine)' => BASE_PATH, 'storage/' => BASE_PATH . '/storage', 'bootstrap/cache/' => BASE_PATH . '/bootstrap/cache'] as $label => $path) {
        $checks[] = [
            'label' => "Inscriptible : $label",
            'ok'    => is_writable($path),
            'hint'  => "Donne les droits d'écriture (755/775) sur $path.",
        ];
    }
    $checks[] = [
        'label' => 'Dépendances (vendor/)',
        'ok'    => is_file(AUTOLOAD),
        'hint'  => "Lance « composer install » ou utilise une release avec vendor inclus.",
        'soft'  => true, // non bloquant : on tentera composer install
    ];

    return $checks;
}

/** True si toutes les vérifications bloquantes passent. */
function requirements_ok(array $checks): bool
{
    foreach ($checks as $c) {
        if (empty($c['ok']) && empty($c['soft'])) {
            return false;
        }
    }
    return true;
}

/** exec() est-il réellement disponible ? */
function exec_available(): bool
{
    if (!function_exists('exec')) {
        return false;
    }
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    return !in_array('exec', $disabled, true);
}

/** Tente d'installer vendor/ via Composer. Retourne [bool ok, string log]. */
function try_composer_install(): array
{
    if (is_file(AUTOLOAD)) {
        return [true, 'vendor/ déjà présent.'];
    }
    if (!exec_available()) {
        return [false, "exec() est désactivé : impossible de lancer Composer. Installe vendor/ manuellement ou utilise une release avec vendor inclus."];
    }

    $candidates = ['composer', 'php composer.phar', 'composer.phar'];
    $log = '';
    foreach ($candidates as $bin) {
        $cmd = sprintf('cd %s && %s install --no-dev --optimize-autoloader --no-interaction 2>&1', escapeshellarg(BASE_PATH), $bin);
        $out = [];
        @exec($cmd, $out, $code);
        $log .= "\$ $bin install\n" . implode("\n", $out) . "\n";
        if (is_file(AUTOLOAD)) {
            return [true, $log];
        }
    }
    return [false, $log ?: 'Composer introuvable.'];
}

/** Remplace/ajoute une clé dans le contenu .env (gère les lignes commentées). */
function env_set(string $content, string $key, string $value): string
{
    // Quote si espace ou caractère spécial.
    if ($value === '' || preg_match('/\s|#|"|\'|\$/', $value)) {
        $value = '"' . str_replace('"', '\"', $value) . '"';
    }
    $line = $key . '=' . $value;

    // Ligne active ou commentée (# KEY=...).
    $pattern = '/^[#\s]*' . preg_quote($key, '/') . '=.*$/m';
    if (preg_match($pattern, $content)) {
        return preg_replace($pattern, $line, $content, 1);
    }
    return rtrim($content) . "\n" . $line . "\n";
}

/** Traite la soumission du formulaire et exécute l'installation. */
function handle_install(): void
{
    $f = fn (string $k): string => trim((string) ($_POST[$k] ?? ''));

    $data = [
        'db_host' => $f('db_host') ?: '127.0.0.1',
        'db_port' => $f('db_port') ?: '3306',
        'db_name' => $f('db_name'),
        'db_user' => $f('db_user'),
        'db_pass' => (string) ($_POST['db_pass'] ?? ''),
        'app_url' => rtrim($f('app_url'), '/'),
        'shop'    => $f('shop_name') ?: 'Boutique',
        'lang'    => in_array($f('lang'), ['fr', 'ar', 'en'], true) ? $f('lang') : 'fr',
        'devise'  => $f('devise') ?: 'MAD',
        'gemini'  => $f('gemini_key'),
        'au'      => $f('admin_user'),
        'ae'      => $f('admin_email'),
        'ap'      => (string) ($_POST['admin_pass'] ?? ''),
        'ap2'     => (string) ($_POST['admin_pass2'] ?? ''),
    ];

    /* --- Validation --- */
    $errors = [];
    if ($data['db_name'] === '')          $errors[] = "Le nom de la base de données est requis.";
    if ($data['db_user'] === '')          $errors[] = "L'utilisateur de la base est requis.";
    if ($data['app_url'] === '' || !preg_match('#^https?://#', $data['app_url'])) $errors[] = "L'URL du site doit commencer par http:// ou https://.";
    if ($data['au'] === '')               $errors[] = "Le nom d'utilisateur admin est requis.";
    if (strlen($data['ap']) < 8)          $errors[] = "Le mot de passe admin doit faire au moins 8 caractères.";
    if ($data['ap'] !== $data['ap2'])     $errors[] = "Les deux mots de passe admin ne correspondent pas.";
    if ($data['ae'] !== '' && !filter_var($data['ae'], FILTER_VALIDATE_EMAIL)) $errors[] = "L'email admin n'est pas valide.";

    if ($errors) {
        render_form($errors, $data);
        return;
    }

    /* --- Étapes (chacune loggée pour l'écran de résultat) --- */
    $steps = [];
    $add = function (string $label, bool $ok, string $detail = '') use (&$steps): bool {
        $steps[] = compact('label', 'ok', 'detail');
        return $ok;
    };

    // 0) vendor/ (détection auto + composer si absent)
    if (!is_file(AUTOLOAD)) {
        [$ok, $log] = try_composer_install();
        if (!$add('Installation des dépendances (composer install)', $ok, $log)) {
            render_result($steps, false, $data);
            return;
        }
    } else {
        $add('Dépendances vendor/', true, 'Déjà présentes.');
    }

    // 1) Test connexion base de données (avant toute écriture)
    try {
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']}";
        new PDO($dsn, $data['db_user'], $data['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
        $add('Connexion à la base de données', true, "{$data['db_host']}:{$data['db_port']} / {$data['db_name']}");
    } catch (Throwable $e) {
        $add('Connexion à la base de données', false, $e->getMessage());
        render_result($steps, false, $data);
        return;
    }

    // 2) Écriture du .env
    try {
        $tpl = is_file(ENV_FILE) ? (string) file_get_contents(ENV_FILE) : (string) file_get_contents(ENV_SAMPLE);
        $tpl = env_set($tpl, 'APP_NAME', $data['shop']);
        $tpl = env_set($tpl, 'APP_ENV', 'production');
        $tpl = env_set($tpl, 'APP_DEBUG', 'false');
        $tpl = env_set($tpl, 'APP_URL', $data['app_url']);
        $tpl = env_set($tpl, 'APP_LOCALE', $data['lang']);
        $tpl = env_set($tpl, 'APP_FALLBACK_LOCALE', $data['lang']);
        $tpl = env_set($tpl, 'DB_CONNECTION', 'mysql');
        $tpl = env_set($tpl, 'DB_HOST', $data['db_host']);
        $tpl = env_set($tpl, 'DB_PORT', $data['db_port']);
        $tpl = env_set($tpl, 'DB_DATABASE', $data['db_name']);
        $tpl = env_set($tpl, 'DB_USERNAME', $data['db_user']);
        $tpl = env_set($tpl, 'DB_PASSWORD', $data['db_pass']);
        $tpl = env_set($tpl, 'WEESAAS_DEFAULT_LANG', $data['lang']);
        // Clé de chiffrement des réglages sensibles : générée ALÉATOIREMENT et de
        // façon UNIQUE à chaque installation (ne jamais garder le placeholder public).
        $tpl = env_set($tpl, 'WEESAAS_LEGACY_ENCRYPTION_KEY', bin2hex(random_bytes(16)));
        if ($data['gemini'] !== '') {
            $tpl = env_set($tpl, 'GEMINI_API_KEY', $data['gemini']);
        }
        if (file_put_contents(ENV_FILE, $tpl) === false) {
            throw new RuntimeException('Écriture impossible dans .env (droits ?).');
        }
        $add('Écriture du fichier .env', true);
    } catch (Throwable $e) {
        $add('Écriture du fichier .env', false, $e->getMessage());
        render_result($steps, false, $data);
        return;
    }

    // 3) Bootstrap Laravel + commandes artisan internes (sans shell)
    try {
        require AUTOLOAD;
        /** @var \Illuminate\Foundation\Application $app */
        $app    = require BASE_PATH . '/bootstrap/app.php';
        $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

        $run = function (string $cmd, array $args = []) use ($kernel): string {
            $kernel->call($cmd, $args);
            return trim($kernel->output());
        };

        $run('key:generate', ['--force' => true]);
        $add('Génération de la clé APP_KEY', true);

        $run('migrate', ['--force' => true]);
        $add('Migrations de la base de données', true);

        // storage:link (non bloquant : symlink parfois refusé sur mutualisé)
        try {
            $run('storage:link');
            $add('Lien symbolique storage (storage:link)', true);
        } catch (Throwable $e) {
            $add('Lien symbolique storage (storage:link)', true, 'Ignoré : ' . $e->getMessage());
        }

        // 4) Création de l'admin + réglages de base (via DB, après migrate)
        $now  = date('Y-m-d H:i:s');
        $hash = password_hash($data['ap'], PASSWORD_BCRYPT);
        \Illuminate\Support\Facades\DB::table('admins')->updateOrInsert(
            ['username' => $data['au']],
            ['password_hash' => $hash, 'nom' => $data['au'], 'email' => $data['ae'], 'actif' => 1, 'updated_at' => $now, 'created_at' => $now]
        );
        $add('Création du compte administrateur', true, "/weeadmin · utilisateur « {$data['au']} »");

        foreach (['nom_boutique' => $data['shop'], 'langue_defaut' => $data['lang'], 'devise_defaut' => $data['devise'], 'pays_defaut' => 'MA'] as $cle => $val) {
            \Illuminate\Support\Facades\DB::table('settings')->updateOrInsert(['cle' => $cle], ['valeur' => $val, 'chiffre' => 0]);
        }
        $add('Réglages de base de la boutique', true);

        // 5) Nettoyage des caches
        foreach (['config:clear', 'cache:clear', 'route:clear', 'view:clear'] as $c) {
            try { $run($c); } catch (Throwable $e) { /* best-effort */ }
        }
        $add('Nettoyage des caches', true);

        // 6) Verrou d'installation
        @file_put_contents(LOCK_FILE, 'installed ' . $now . "\n");
        $add('Verrouillage de l\'installation', true);

    } catch (Throwable $e) {
        $add('Installation Laravel', false, $e->getMessage());
        render_result($steps, false, $data);
        return;
    }

    render_result($steps, true, $data);
}


/* ════════════════════════════════════════════════════════════════════════
 *  RENDU (HTML)
 * ══════════════════════════════════════════════════════════════════════ */

function render_page(string $title, string $body): void
{
    $css = <<<CSS
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f1115;color:#e7e9ee;line-height:1.55;padding:32px 16px}
    .wrap{max-width:760px;margin:0 auto}
    .card{background:#171a21;border:1px solid #262b36;border-radius:16px;padding:28px;margin-bottom:20px;box-shadow:0 10px 40px rgba(0,0,0,.35)}
    .head{text-align:center;margin-bottom:24px}
    .logo{display:inline-flex;align-items:center;gap:10px;font-weight:800;font-size:22px;letter-spacing:-.3px}
    .logo .dot{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#FF6B00,#ff9500);display:grid;place-items:center;color:#fff;font-size:18px}
    .sub{color:#8b93a7;font-size:14px;margin-top:6px}
    h2{font-size:17px;margin-bottom:14px;color:#fff}
    h2 .n{display:inline-grid;place-items:center;width:24px;height:24px;border-radius:7px;background:#FF6B00;color:#fff;font-size:13px;margin-right:8px}
    label{display:block;font-size:13px;color:#aab2c5;margin:14px 0 6px}
    input,select{width:100%;padding:11px 13px;background:#0f1218;border:1px solid #2a3140;border-radius:10px;color:#fff;font-size:14px;outline:none}
    input:focus,select:focus{border-color:#FF6B00}
    .row{display:flex;gap:14px}.row>*{flex:1}
    .grid2{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}
    .btn{display:inline-block;background:linear-gradient(135deg,#FF6B00,#ff8c00);color:#fff;border:0;border-radius:11px;padding:13px 22px;font-size:15px;font-weight:700;cursor:pointer;text-decoration:none;margin-top:22px;width:100%;text-align:center}
    .btn.ghost{background:#222834;border:1px solid #333b4a}
    .btn:hover{filter:brightness(1.08)}
    ul.req{list-style:none}
    ul.req li{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid #20252f;font-size:14px}
    ul.req li:last-child{border:0}
    .badge{flex:0 0 auto;width:22px;height:22px;border-radius:6px;display:grid;place-items:center;font-size:13px;font-weight:700}
    .ok{background:rgba(34,197,94,.15);color:#39d98a}
    .ko{background:rgba(239,68,68,.15);color:#ff6b6b}
    .soft{background:rgba(234,179,8,.15);color:#f5c043}
    .hint{display:block;color:#828aa0;font-size:12px;margin-top:2px}
    .alert{padding:13px 15px;border-radius:11px;font-size:14px;margin-bottom:16px}
    .alert.err{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#ffb4b4}
    .alert.warn{background:rgba(234,179,8,.1);border:1px solid rgba(234,179,8,.3);color:#f5d98b}
    .alert.ok{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#9af3c4}
    code{background:#0b0d12;padding:2px 6px;border-radius:5px;font-size:13px;color:#ffb27a}
    .detail{color:#828aa0;font-size:12px;white-space:pre-wrap;word-break:break-word;margin-top:3px}
    .foot{text-align:center;color:#5b6276;font-size:12px;margin-top:8px}
    CSS;

    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="robots" content="noindex,nofollow">';
    echo '<title>' . htmlspecialchars($title) . ' · WeeSaaS</title><style>' . $css . '</style></head><body><div class="wrap">';
    echo '<div class="head"><div class="logo"><span class="dot">W</span> WeeSaaS</div><div class="sub">Assistant d\'installation</div></div>';
    echo $body;
    echo '<div class="foot">Supprime <code>install.php</code> après l\'installation.</div>';
    echo '</div></body></html>';
}

function render_locked(): void
{
    http_response_code(403);
    render_page('Déjà installé', '
        <div class="card">
            <div class="alert ok">✅ WeeSaaS est <strong>déjà installé</strong>.</div>
            <p style="color:#aab2c5;font-size:14px">Par sécurité, l\'assistant est désactivé. Supprime
            <code>public/install.php</code> de ton serveur. Pour réinstaller, supprime d\'abord
            <code>storage/installed.lock</code>.</p>
            <a class="btn" href="' . htmlspecialchars(app_base_url() . '/weeadmin') . '">Aller sur l\'administration →</a>
        </div>');
}

function render_form(array $errors = [], array $old = []): void
{
    $checks  = requirements();
    $canGo   = requirements_ok($checks);
    $v       = fn (string $k, string $d = ''): string => htmlspecialchars((string) ($old[$k] ?? $d));

    $reqHtml = '<div class="card"><h2><span class="n">1</span>Vérifications système</h2><ul class="req">';
    foreach ($checks as $c) {
        $cls = !empty($c['ok']) ? 'ok' : (!empty($c['soft']) ? 'soft' : 'ko');
        $ico = !empty($c['ok']) ? '✓' : (!empty($c['soft']) ? '!' : '✕');
        $reqHtml .= '<li><span class="badge ' . $cls . '">' . $ico . '</span><div>' . htmlspecialchars($c['label']);
        if (empty($c['ok'])) {
            $reqHtml .= '<span class="hint">' . htmlspecialchars($c['hint']) . '</span>';
        }
        $reqHtml .= '</div></li>';
    }
    $reqHtml .= '</ul>';
    if (!$canGo) {
        $reqHtml .= '<div class="alert err" style="margin-top:16px">Corrige les points en rouge avant de continuer.</div>';
    }
    $reqHtml .= '</div>';

    $errHtml = '';
    if ($errors) {
        $errHtml = '<div class="alert err"><strong>Erreurs :</strong><ul style="margin:6px 0 0 18px">';
        foreach ($errors as $e) {
            $errHtml .= '<li>' . htmlspecialchars($e) . '</li>';
        }
        $errHtml .= '</ul></div>';
    }

    $defUrl = $v('app_url', (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . app_base_url());

    $disabled = $canGo ? '' : 'disabled style="opacity:.5;cursor:not-allowed"';

    $form = '
    <form method="post" autocomplete="off">
      <input type="hidden" name="action" value="install">
      <div class="card">
        <h2><span class="n">2</span>Base de données (MySQL / MariaDB)</h2>
        <div class="row">
          <div><label>Hôte</label><input name="db_host" value="' . $v('db_host', '127.0.0.1') . '"></div>
          <div style="max-width:120px"><label>Port</label><input name="db_port" value="' . $v('db_port', '3306') . '"></div>
        </div>
        <label>Nom de la base</label><input name="db_name" value="' . $v('db_name') . '" required>
        <div class="row">
          <div><label>Utilisateur</label><input name="db_user" value="' . $v('db_user', 'root') . '" required></div>
          <div><label>Mot de passe</label><input type="password" name="db_pass" value=""></div>
        </div>
      </div>

      <div class="card">
        <h2><span class="n">3</span>Site & boutique</h2>
        <label>URL du site</label><input name="app_url" value="' . htmlspecialchars($defUrl) . '" required>
        <label>Nom de la boutique</label><input name="shop_name" value="' . $v('shop_name') . '" required>
        <div class="row">
          <div><label>Langue par défaut</label><select name="lang">
            <option value="fr"' . (($old['lang'] ?? 'fr') === 'fr' ? ' selected' : '') . '>Français</option>
            <option value="ar"' . (($old['lang'] ?? '') === 'ar' ? ' selected' : '') . '>العربية</option>
            <option value="en"' . (($old['lang'] ?? '') === 'en' ? ' selected' : '') . '>English</option>
          </select></div>
          <div><label>Devise</label><input name="devise" value="' . $v('devise', 'MAD') . '"></div>
        </div>
        <label>Clé API Gemini <span style="color:#6b7390">(optionnel)</span></label>
        <input name="gemini_key" value="' . $v('gemini') . '" placeholder="AIza...">
      </div>

      <div class="card">
        <h2><span class="n">4</span>Compte administrateur (/weeadmin)</h2>
        <div class="row">
          <div><label>Nom d\'utilisateur</label><input name="admin_user" value="' . $v('admin_user', 'admin') . '" required></div>
          <div><label>Email</label><input type="email" name="admin_email" value="' . $v('admin_email') . '"></div>
        </div>
        <div class="row">
          <div><label>Mot de passe</label><input type="password" name="admin_pass" required></div>
          <div><label>Confirmer</label><input type="password" name="admin_pass2" required></div>
        </div>
        <button class="btn" ' . $disabled . '>🚀 Lancer l\'installation</button>
      </div>
    </form>';

    render_page('Installation', $reqHtml . $errHtml . $form);
}

function render_result(array $steps, bool $success, array $data = []): void
{
    $list = '<ul class="req">';
    foreach ($steps as $s) {
        $cls = $s['ok'] ? 'ok' : 'ko';
        $ico = $s['ok'] ? '✓' : '✕';
        $list .= '<li><span class="badge ' . $cls . '">' . $ico . '</span><div>' . htmlspecialchars($s['label']);
        if (!empty($s['detail'])) {
            $list .= '<span class="detail">' . htmlspecialchars($s['detail']) . '</span>';
        }
        $list .= '</div></li>';
    }
    $list .= '</ul>';

    if ($success) {
        $body = '<div class="card"><div class="alert ok">🎉 Installation terminée avec succès !</div>' . $list . '</div>'
            . '<div class="card"><h2>Dernière étape — sécurité</h2>'
            . '<p style="color:#aab2c5;font-size:14px">Pour finir, <strong>supprime l\'installateur</strong>. '
            . 'Le bouton ci-dessous le supprime puis t\'emmène sur l\'administration.</p>'
            . '<form method="post"><input type="hidden" name="action" value="delete">'
            . '<button class="btn">🗑️ Supprimer l\'installateur & aller sur /weeadmin</button></form></div>';
    } else {
        $body = '<div class="card"><div class="alert err">❌ L\'installation a échoué. Corrige l\'erreur ci-dessous et réessaie.</div>' . $list . '</div>'
            . '<a class="btn ghost" href="?">← Revenir au formulaire</a>';
    }

    render_page($success ? 'Terminé' : 'Échec', $body);
}
