<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Envoi d'emails simples (texte) en utilisant la configuration SMTP stockée
 * dans la table `settings` (port de includes/mailer.php).
 *
 * Best-effort : un échec SMTP ne doit jamais bloquer le flux commande.
 */
class MailService
{
    public function __construct(private SettingsRepository $settings) {}

    public function sendRaw(string $to, string $subject, string $text): bool
    {
        if (empty($to)) {
            return false;
        }

        try {
            $host = $this->settings->get('smtp_host');
            $mailer = config('mail.default');

            if ($host) {
                $port = (int) ($this->settings->get('smtp_port', '587') ?: 587);
                config(['mail.mailers.weesaas_smtp' => [
                    'transport'  => 'smtp',
                    'host'       => $host,
                    'port'       => $port,
                    'username'   => $this->settings->get('smtp_user') ?: null,
                    'password'   => $this->settings->get('smtp_pass') ?: null,
                    'encryption' => $port === 465 ? 'ssl' : 'tls',
                    'timeout'    => 10,
                ]]);
                $mailer = 'weesaas_smtp';
            }

            $from = $this->settings->get('smtp_from') ?: config('mail.from.address');
            $fromName = $this->settings->get('smtp_from_name', 'WeeSaaS') ?: 'WeeSaaS';

            Mail::mailer($mailer)->raw($text, function ($m) use ($to, $subject, $from, $fromName) {
                $m->to($to)->subject($subject)->from($from, $fromName);
            });

            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    public function adminEmail(): string
    {
        return $this->settings->get('email_notif_admin');
    }
}
