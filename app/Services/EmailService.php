<?php

namespace App\Services;

use App\Models\EmailSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    protected ?EmailSetting $setting;

    public function __construct(?EmailSetting $setting = null)
    {
        $this->setting = $setting ?: EmailSetting::getSettings();
    }

    /**
     * Apply active email settings to Laravel mail configuration.
     */
    public static function applyConfiguration(): void
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('email_settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $setting = EmailSetting::where('is_active', true)->first();
        if (!$setting) {
            return;
        }

        (new static($setting))->configureMailer();
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->setting->is_active ?? false);
    }

    public function configureMailer(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $provider = $this->setting->provider_type ?: 'smtp';

        if ($provider === 'sendmail') {
            Config::set('mail.default', 'sendmail');
            if (!empty($this->setting->sendmail_path)) {
                Config::set('mail.mailers.sendmail.path', $this->setting->sendmail_path);
            }
        } else {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $this->setting->mail_host);
            Config::set('mail.mailers.smtp.port', $this->setting->mail_port ?: 587);
            Config::set('mail.mailers.smtp.username', $this->setting->mail_username);
            Config::set('mail.mailers.smtp.password', $this->setting->mail_password);

            $encryption = strtolower((string) ($this->setting->mail_encryption ?? ''));
            if ($encryption === 'ssl') {
                Config::set('mail.mailers.smtp.scheme', 'smtps');
            } elseif ($encryption === 'tls') {
                Config::set('mail.mailers.smtp.scheme', 'smtp');
            } else {
                Config::set('mail.mailers.smtp.scheme', null);
            }
        }

        if (!empty($this->setting->mail_from_address)) {
            Config::set('mail.from.address', $this->setting->mail_from_address);
        }
        if (!empty($this->setting->mail_from_name)) {
            Config::set('mail.from.name', $this->setting->mail_from_name);
        }

        try {
            Mail::purge('smtp');
            Mail::purge('sendmail');
            Mail::purge(config('mail.default'));
        } catch (\Throwable $e) {
            // Mail manager may not be bootstrapped yet.
        }
    }

    /**
     * Send a test email using the current (saved) settings from the form payload or DB.
     *
     * @param  array<string, mixed>  $payload
     * @return array{success: bool, error?: string, disabled?: bool}
     */
    public function sendTestEmail(string $recipient, array $payload = []): array
    {
        $isActive = array_key_exists('is_active', $payload)
            ? (bool) $payload['is_active']
            : $this->isEnabled();

        if (!$isActive) {
            return [
                'success'  => false,
                'disabled' => true,
                'error'    => 'Email integration is disabled. Enable it and save settings first.',
            ];
        }

        $testSetting = $this->buildSettingFromPayload($payload);
        (new static($testSetting))->configureMailer();

        try {
            Mail::raw(
                "This is a test email from your CRM.\n\nIf you received this message, your email settings are configured correctly.\n\nSent at: " . now()->format('d M Y, h:i A'),
                function ($message) use ($recipient, $testSetting) {
                    $message->to($recipient)
                        ->subject('CRM Email Settings — Test Message');

                    if (!empty($testSetting->mail_from_address)) {
                        $message->from(
                            $testSetting->mail_from_address,
                            $testSetting->mail_from_name ?: config('app.name')
                        );
                    }
                }
            );

            return ['success' => true];
        } catch (\Throwable $e) {
            Log::error('Email test failed', [
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function buildSettingFromPayload(array $payload): EmailSetting
    {
        $setting = clone $this->setting;
        $setting->provider_type     = $payload['provider_type'] ?? $setting->provider_type ?? 'smtp';
        $setting->mail_host         = $payload['mail_host'] ?? $setting->mail_host;
        $setting->mail_port         = isset($payload['mail_port']) ? (int) $payload['mail_port'] : $setting->mail_port;
        $setting->mail_encryption   = $payload['mail_encryption'] ?? $setting->mail_encryption;
        $setting->mail_username     = $payload['mail_username'] ?? $setting->mail_username;
        $setting->mail_password     = $payload['mail_password'] ?? $setting->mail_password;
        $setting->mail_from_address = $payload['mail_from_address'] ?? $setting->mail_from_address;
        $setting->mail_from_name    = $payload['mail_from_name'] ?? $setting->mail_from_name;
        $setting->sendmail_path     = $payload['sendmail_path'] ?? $setting->sendmail_path;
        $setting->is_active         = array_key_exists('is_active', $payload)
            ? (bool) $payload['is_active']
            : (bool) $setting->is_active;

        return $setting;
    }
}
