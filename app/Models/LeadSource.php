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
}
