<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Accès centralisé aux paramètres boutique (table `settings`).
 *
 * Compatibilité legacy :
 *  - Les valeurs sensibles (clés API, SMTP) sont chiffrées en AES-256-CBC avec
 *    la clé historique (WEESAAS_LEGACY_ENCRYPTION_KEY) et le format
 *    base64(iv . '::' . base64(ciphertext)) — reproduit à l'identique pour
 *    rester lisible/écrivable sans ré-encoder les données importées.
 */
class SettingsRepository
{
    private const CACHE_KEY = 'weesaas.settings';
    private const CIPHER = 'AES-256-CBC';

    /** @var array<string,string>|null */
    private ?array $cache = null;

    /** Toutes les valeurs (déchiffrées) sous forme [cle => valeur]. */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $this->cache = Cache::remember(self::CACHE_KEY, 3600, function () {
            $out = [];
            foreach (Setting::all(['cle', 'valeur', 'chiffre']) as $row) {
                $out[$row->cle] = $row->chiffre ? $this->decrypt((string) $row->valeur) : (string) $row->valeur;
            }
            return $out;
        });

        return $this->cache;
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->all()[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /** Écrit (et chiffre si demandé) un paramètre puis invalide le cache. */
    public function set(string $key, string $value, bool $encrypted = false): void
    {
        Setting::updateOrCreate(
            ['cle' => $key],
            ['valeur' => $encrypted ? $this->encrypt($value) : $value, 'chiffre' => $encrypted]
        );
        $this->flush();
    }

    public function flush(): void
    {
        $this->cache = null;
        Cache::forget(self::CACHE_KEY);
    }

    /* ───────────────── Chiffrement compatible legacy ───────────────── */

    private function key(): string
    {
        return (string) config('weesaas.legacy_encryption_key');
    }

    public function encrypt(string $value): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($value, self::CIPHER, $this->key(), 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    public function decrypt(string $value): string
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false || ! str_contains($decoded, '::')) {
            return '';
        }
        [$iv, $encrypted] = explode('::', $decoded, 2);
        $plain = openssl_decrypt($encrypted, self::CIPHER, $this->key(), 0, $iv);
        return $plain === false ? '' : $plain;
    }
}
