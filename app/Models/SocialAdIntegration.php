<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SocialAdIntegration extends Model
{
    public const META = 'meta';
    public const GOOGLE = 'google';
    public const LINKEDIN = 'linkedin';
    public const GENERIC = 'generic';

    protected $fillable = [
        'platform',
        'is_enabled',
        'credentials',
        'settings',
        'webhook_key',
        'connected_name',
        'connected_external_id',
        'connected_at',
        'token_expires_at',
        'last_synced_at',
        'last_error',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'credentials' => 'encrypted:array',
        'settings' => 'array',
        'connected_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public static function platforms(): array
    {
        return [
            self::META => ['label' => 'Facebook & Instagram Lead Ads', 'short' => 'Meta', 'icon' => 'bi-facebook', 'color' => '#1877f2', 'source_hint' => 'facebook'],
            self::GOOGLE => ['label' => 'Google Ads Lead Forms', 'short' => 'Google Ads', 'icon' => 'bi-google', 'color' => '#ea4335', 'source_hint' => 'google'],
            self::LINKEDIN => ['label' => 'LinkedIn Lead Gen Forms', 'short' => 'LinkedIn', 'icon' => 'bi-linkedin', 'color' => '#0a66c2', 'source_hint' => 'linkedin'],
            self::GENERIC => ['label' => 'Generic Webhook', 'short' => 'Webhook', 'icon' => 'bi-plug', 'color' => '#7c3aed', 'source_hint' => null],
        ];
    }

    public static function platformLabel(string $platform): string
    {
        return static::platforms()[$platform]['short'] ?? ucfirst($platform);
    }

    public static function for(string $platform): self
    {
        return static::firstOrCreate(['platform' => $platform], [
            'is_enabled' => false,
            'credentials' => [],
            'settings' => static::initialSettings($platform),
            'webhook_key' => in_array($platform, [self::META, self::GOOGLE], true) ? Str::random(40) : null,
        ]);
    }

    protected static function initialSettings(string $platform): array
    {
        $hint = static::platforms()[$platform]['source_hint'] ?? null;
        $sourceId = $hint ? LeadSource::whereRaw('LOWER(name) LIKE ?', ['%' . $hint . '%'])->value('id') : null;

        return [
            'lead_source_id' => $sourceId,
            'lead_status' => \App\Services\LeadIntakeService::defaultStatus(),
            'lead_priority' => 'normal',
            'owner_id' => auth()->id() ?: \App\Services\LeadIntakeService::fallbackOwnerId(),
            'default_category_ids' => null,
        ];
    }

    public function credential(string $key, $default = null)
    {
        return ($this->credentials ?? [])[$key] ?? $default;
    }

    public function setCredentials(array $values): void
    {
        $this->credentials = array_merge($this->credentials ?? [], $values);
    }

    public function setting(string $key, $default = null)
    {
        return ($this->settings ?? [])[$key] ?? $default;
    }

    public function forms()
    {
        return $this->hasMany(SocialAdForm::class, 'platform', 'platform');
    }

    public function isConnected(): bool
    {
        return $this->connected_at !== null;
    }

    /**
     * Default lead settings copied onto newly discovered forms.
     */
    public function formDefaults(): array
    {
        return [
            'lead_source_id' => $this->setting('lead_source_id'),
            'lead_status' => $this->setting('lead_status'),
            'lead_priority' => $this->setting('lead_priority', 'normal'),
            'owner_id' => $this->setting('owner_id'),
            'default_category_ids' => $this->setting('default_category_ids'),
        ];
    }
}
