<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    use HasFactory;

    protected $table = 'email_settings';

    protected $fillable = [
        'provider_type',
        'mail_host',
        'mail_port',
        'mail_encryption',
        'mail_username',
        'mail_password',
        'mail_from_address',
        'mail_from_name',
        'sendmail_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'mail_port' => 'integer',
    ];

    /**
     * Retrieve settings for a provider type or the active provider.
     */
    public static function getSettings(?string $providerType = null): static
    {
        if ($providerType) {
            $setting = static::where('provider_type', $providerType)->first();
        } else {
            $setting = static::where('is_active', true)->first() ?: static::first();
        }

        if (!$setting) {
            $type = $providerType ?: config('services.email.provider_type', 'smtp');
            $setting = new static([
                'provider_type'     => $type,
                'mail_host'         => config('services.email.host'),
                'mail_port'         => (int) config('services.email.port', 587),
                'mail_encryption'   => config('services.email.encryption', 'tls'),
                'mail_username'     => config('services.email.username'),
                'mail_password'     => config('services.email.password'),
                'mail_from_address' => config('services.email.from_address'),
                'mail_from_name'    => config('services.email.from_name'),
                'sendmail_path'     => config('services.email.sendmail_path'),
                'is_active'         => false,
            ]);
        }

        return $setting;
    }
}
