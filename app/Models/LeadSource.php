<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadSource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Referral-type sources that require a referral name.
     */
    public static function referralSourceNames(): array
    {
        return [
            'client referral',
            'employee referral',
            'partner referral',
            'white-label referral',
            'white label referral',
            'whitelabel referral',
        ];
    }

    public static function normalizeName(?string $name): string
    {
        $name = strtolower(trim((string) $name));
        $name = str_replace(['_', '–', '—'], '-', $name);
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return $name;
    }

    public static function isReferralSourceName(?string $name): bool
    {
        return in_array(static::normalizeName($name), static::referralSourceNames(), true);
    }

    public static function isReferralSourceId($sourceId): bool
    {
        if ($sourceId === null || $sourceId === '') {
            return false;
        }

        $source = static::find($sourceId);

        return $source ? static::isReferralSourceName($source->name) : false;
    }

    public static function isChannelSourceName(?string $name): bool
    {
        $n = static::normalizeName($name);

        return $n === 'channel' || str_contains($n, 'channel');
    }

    /**
     * Active lead-source names for Goal Loan CRM dropdowns.
     *
     * @return list<string>
     */
    public static function activeNames(): array
    {
        return static::query()
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($n) => (string) $n)
            ->values()
            ->all();
    }

    /**
     * Resolve a stored/legacy value to a master name when possible.
     */
    public static function resolveCanonicalName(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $exact = static::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($value)])
            ->value('name');
        if ($exact) {
            return (string) $exact;
        }

        $aliases = [
            'vrm' => 'VRM',
            'vrm calling' => 'VRM',
            'channel' => 'Channel',
            'channel partner' => 'Channel',
            'manual' => 'Manual',
            'walkin' => 'Walk-in',
            'walk-in' => 'Walk-in',
            'referral' => 'Referral',
        ];

        $alias = $aliases[static::normalizeName($value)] ?? null;
        if ($alias) {
            $found = static::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($alias)])
                ->value('name');

            return $found ? (string) $found : $alias;
        }

        return $value;
    }
}
