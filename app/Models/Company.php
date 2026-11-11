<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'logo', 'address', 'city', 'state', 'district', 'pincode', 'phone',
        'mobile', 'email', 'website', 'favicon', 'gstin',
        'bank_account_number', 'bank_name', 'bank_branch', 'bank_address', 'bank_ifsc',
        'fy_start_month', 'fy_start_day', 'fy_end_month', 'fy_end_day',
        'currency_code', 'currency_symbol', 'currency_position', 'country',
        'max_users',
        'admin_assignable_permissions',
        'email_send_enabled',
        'whatsapp_send_enabled',
        'web_forms_enabled',
        'social_ads_enabled',
        'weekly_target_alert_enabled',
        'weekly_target_alert_morning',
        'weekly_target_alert_afternoon',
        'weekly_target_alert_evening',
        'expire_date',
    ];

    protected $casts = [
        'admin_assignable_permissions' => 'array',
        'max_users' => 'integer',
        'email_send_enabled' => 'boolean',
        'whatsapp_send_enabled' => 'boolean',
        'web_forms_enabled' => 'boolean',
        'social_ads_enabled' => 'boolean',
        'weekly_target_alert_enabled' => 'boolean',
        'expire_date' => 'date',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? new static();
    }

    public function getLogoUrlAttribute(): string
    {
        if (!empty($this->logo)) {
            if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
                return $this->logo;
            }
            $cleanPath = ltrim($this->logo, '/');
            if (file_exists(public_path('assets/company_image/' . $cleanPath))) {
                return asset('assets/company_image/' . $cleanPath);
            }
            if (file_exists(public_path($cleanPath))) {
                return asset($cleanPath);
            }
        }
        return asset('images/logoisarva-1.svg');
    }

    public function getFaviconUrlAttribute(): string
    {
        if (!empty($this->favicon)) {
            if (str_starts_with($this->favicon, 'http://') || str_starts_with($this->favicon, 'https://')) {
                return $this->favicon;
            }
            $cleanPath = ltrim($this->favicon, '/');
            if (file_exists(public_path('assets/company_image/' . $cleanPath))) {
                return asset('assets/company_image/' . $cleanPath);
            }
            if (file_exists(public_path($cleanPath))) {
                return asset($cleanPath);
            }
        }
        return asset('favicon.png');
    }


    /**
     * Null = unlimited seats.
     */
    public function maxUsersLimit(): ?int
    {
        $limit = $this->max_users;

        return $limit !== null && (int) $limit > 0 ? (int) $limit : null;
    }

    /**
     * Guards Admins may assign on user create/edit.
     * Empty array = Admin cannot assign module permissions until Super Admin configures them.
     */
    public function adminAssignableGuards(): array
    {
        $guards = $this->admin_assignable_permissions;

        if (!is_array($guards)) {
            return [];
        }

        $list = array_values(array_filter(array_map('strval', $guards)));

        if (!$this->isWebFormsEnabled()) {
            $list = array_values(array_filter($list, fn ($g) => !\App\Models\Permission::isWebFormsGuard($g)));
        }

        if (!$this->isSocialAdsEnabled()) {
            $list = array_values(array_filter($list, fn ($g) => !\App\Models\Permission::isSocialAdsGuard($g)));
        }

        return $list;
    }

    public function canCreateMoreUsers(int $currentCount): bool
    {
        $limit = $this->maxUsersLimit();
        if ($limit === null) {
            return true;
        }

        return $currentCount < $limit;
    }

    public function isEmailSendEnabled(): bool
    {
        // Default true when column missing / new company row
        return $this->email_send_enabled !== false;
    }

    public function isWhatsappSendEnabled(): bool
    {
        return $this->whatsapp_send_enabled !== false;
    }

    public function isWebFormsEnabled(): bool
    {
        return $this->web_forms_enabled !== false;
    }

    public function isSocialAdsEnabled(): bool
    {
        return $this->social_ads_enabled !== false;
    }

    public function isWeeklyTargetAlertEnabled(): bool
    {
        return $this->weekly_target_alert_enabled !== false;
    }

    /**
     * Configured morning / afternoon / evening alert times (HH:MM).
     *
     * @return array{morning: string, afternoon: string, evening: string}
     */
    public function weeklyTargetAlertTimes(): array
    {
        return [
            'morning' => $this->normalizeAlertTime($this->weekly_target_alert_morning, '09:00'),
            'afternoon' => $this->normalizeAlertTime($this->weekly_target_alert_afternoon, '14:00'),
            'evening' => $this->normalizeAlertTime($this->weekly_target_alert_evening, '18:00'),
        ];
    }

    protected function normalizeAlertTime(?string $value, string $fallback): string
    {
        $value = trim((string) $value);
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return $value;
        }

        return $fallback;
    }
}
