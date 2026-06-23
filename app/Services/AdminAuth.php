<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminSession;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Authentification admin custom (port de includes/auth.php).
 *
 * Reproduit fidèlement :
 *  - hachage password_verify (bcrypt) sur la colonne password_hash ;
 *  - sessions persistées en base (admin_sessions) avec token + binding IP + expiration ;
 *  - anti-bruteforce par IP (login_attempts, 5 essais / 15 min).
 *
 * La session HTTP Laravel ne stocke que des identifiants ; la source de vérité
 * reste la table admin_sessions (revocation côté serveur possible).
 */
class AdminAuth
{
    private const MAX_ATTEMPTS = 5;
    private const BLOCK_SECONDS = 900;      // 15 min
    private const LIFETIME_SECONDS = 28800; // 8 h

    /** Tente une connexion. Retourne ['success'=>bool, 'error'=>?string]. */
    public function attempt(string $username, string $password, Request $request): array
    {
        $ip = $request->ip() ?? '0.0.0.0';

        $attempts = LoginAttempt::where('ip', $ip)
            ->where('created_at', '>', Carbon::now()->subSeconds(self::BLOCK_SECONDS))
            ->count();

        if ($attempts >= self::MAX_ATTEMPTS) {
            return ['success' => false, 'error' => 'Trop de tentatives. Réessayez dans 15 minutes.'];
        }

        $admin = Admin::where('username', $username)->where('actif', 1)->first();

        if (! $admin || ! Hash::check($password, $admin->password_hash)) {
            LoginAttempt::create(['ip' => $ip, 'username' => $username]);
            $remaining = self::MAX_ATTEMPTS - $attempts - 1;
            return ['success' => false, 'error' => "Identifiants incorrects. $remaining tentative(s) restante(s)."];
        }

        $token = bin2hex(random_bytes(32));
        $expires = Carbon::now()->addSeconds(self::LIFETIME_SECONDS);

        // Purge des sessions de cet admin + expirées, puis création.
        AdminSession::where('admin_id', $admin->id)->orWhere('expires_at', '<', now())->delete();
        AdminSession::create([
            'admin_id'   => $admin->id,
            'token'      => $token,
            'ip_address' => $ip,
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'expires_at' => $expires,
            'created_at' => now(),
        ]);

        LoginAttempt::where('ip', $ip)->delete();

        $request->session()->regenerate();
        $request->session()->put('admin_id', $admin->id);
        $request->session()->put('admin_token', $token);
        $request->session()->put('admin_username', $admin->username);
        $request->session()->put('admin_ip', $ip);
        $request->session()->put('admin_expires', $expires->timestamp);

        return ['success' => true, 'admin' => $admin];
    }

    /** Vérifie la validité de la session admin (binding IP, expiration, DB). */
    public function check(Request $request): bool
    {
        $session = $request->session();
        if (! $session->get('admin_id') || ! $session->get('admin_token')) {
            return false;
        }

        $ip = $request->ip() ?? '0.0.0.0';
        if ($session->get('admin_ip') !== $ip) {
            $this->logout($request);
            return false;
        }

        if ((int) $session->get('admin_expires') < time()) {
            $this->logout($request);
            return false;
        }

        $row = AdminSession::where('admin_id', $session->get('admin_id'))
            ->where('token', $session->get('admin_token'))
            ->where('ip_address', $ip)
            ->where('expires_at', '>', now())
            ->first();

        if (! $row) {
            $this->logout($request);
            return false;
        }

        $row->update(['last_activity' => now()]);
        return true;
    }

    public function logout(Request $request): void
    {
        $session = $request->session();
        if ($session->get('admin_id') && $session->get('admin_token')) {
            AdminSession::where('admin_id', $session->get('admin_id'))
                ->where('token', $session->get('admin_token'))
                ->delete();
        }
        $session->forget(['admin_id', 'admin_token', 'admin_username', 'admin_ip', 'admin_expires']);
        $session->invalidate();
    }

    public function user(Request $request): ?Admin
    {
        $id = $request->session()->get('admin_id');
        return $id ? Admin::find($id) : null;
    }

    public function check_loggedIn(Request $request): bool
    {
        return (bool) $request->session()->get('admin_id');
    }
}
