<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Connexion / déconnexion admin (port de weeadmin/index.php + logout.php).
 */
class AuthController extends Controller
{
    public function __construct(private AdminAuth $auth) {}

    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($this->auth->check_loggedIn($request)) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login', ['error' => session('error', '')]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->auth->attempt(trim($data['login']), trim($data['password']), $request);

        if (! empty($result['success'])) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()
            ->withInput($request->only('login'))
            ->with('error', $result['error'] ?? 'Identifiants incorrects.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auth->logout($request);
        return redirect()->route('admin.login');
    }
}
