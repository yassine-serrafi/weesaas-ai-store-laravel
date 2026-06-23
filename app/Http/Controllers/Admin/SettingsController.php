<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /** Clés en clair éditables (texte / couleur / select). */
    private const PLAIN_KEYS = [
        'nom_boutique', 'description_boutique', 'footer_desc', 'adresse_boutique',
        'logo_url', 'favicon_url', 'couleur_principale',
        'langue_defaut', 'pays_defaut', 'devise_defaut',
        'frais_livraison_defaut', 'delai_livraison_defaut',
        'email_contact', 'email_notif_admin', 'tel_whatsapp',
        'facebook', 'instagram', 'tiktok',
        'promo_bar_text', 'ga_id', 'fb_pixel_id', 'tt_pixel_id',
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_from', 'smtp_from_name',
    ];

    /** Cases à cocher (toujours écrites : 1 ou 0). */
    private const TOGGLE_KEYS = ['livraison_gratuite_defaut'];

    /** Clés sensibles (chiffrées) : mises à jour seulement si une nouvelle valeur est fournie. */
    private const SECRET_KEYS = ['smtp_pass', 'gemini_api_key', 'openai_api_key'];

    public function edit(SettingsRepository $settings): View
    {
        return view('admin.settings.edit', ['shop' => $settings->all()]);
    }

    public function update(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $request->validate([
            'logo'    => ['nullable', 'image', 'max:2048'],                       // ≤ 2 Mo
            'favicon' => ['nullable', 'file', 'mimes:png,ico,svg,jpg,webp', 'max:1024'],
        ]);

        foreach (self::PLAIN_KEYS as $key) {
            if ($request->has($key)) {
                $settings->set($key, (string) $request->input($key), false);
            }
        }

        // Uploads logo / favicon : si un fichier est envoyé, il remplace l'URL.
        foreach (['logo' => 'logo_url', 'favicon' => 'favicon_url'] as $field => $urlKey) {
            if ($request->hasFile($field)) {
                $settings->set($urlKey, $this->storeUpload($request->file($field), $field), false);
            }
        }

        // Toggles : toujours enregistrés (présent = 1, absent = 0).
        foreach (self::TOGGLE_KEYS as $key) {
            $settings->set($key, $request->boolean($key) ? '1' : '0', false);
        }

        // Secrets : ne pas écraser si le champ est laissé vide.
        foreach (self::SECRET_KEYS as $key) {
            $val = (string) $request->input($key, '');
            if ($val !== '') {
                $settings->set($key, $val, true);
            }
        }

        $settings->flush();

        return back()->with('success', 'Paramètres enregistrés.');
    }

    /** Sauvegarde un fichier uploadé dans public/uploads/site et retourne son URL. */
    private function storeUpload(\Illuminate\Http\UploadedFile $file, string $prefix): string
    {
        $dir = public_path('uploads/site');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $name = $prefix . '_' . uniqid() . '.' . $ext;
        $file->move($dir, $name);

        return site_url('uploads/site/' . $name);
    }

    /** Réinitialise uniquement les clés API IA (Gemini + OpenAI). */
    public function resetApi(SettingsRepository $settings): RedirectResponse
    {
        foreach (['gemini_api_key', 'openai_api_key'] as $key) {
            $settings->set($key, '', false);
        }
        $settings->flush();

        return back()->with('success', 'Clés API réinitialisées. Saisissez de nouvelles clés ci-dessous.');
    }
}
