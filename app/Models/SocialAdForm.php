<?php

namespace App\Models;

use App\Support\SocialAdFieldMapper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SocialAdForm extends Model
{
    protected $fillable = [
        'platform',
        'social_ad_account_id',
        'external_id',
        'token',
        'name',
        'status',
        'is_enabled',
        'questions',
        'field_mapping',
        'lead_source_id',
        'lead_status',
        'lead_priority',
        'owner_id',
        'default_category_ids',
        'leads_count',
        'last_lead_at',
        'last_synced_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'questions' => 'array',
        'field_mapping' => 'array',
        'last_lead_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public static function generateToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function account()
    {
        return $this->belongsTo(SocialAdAccount::class, 'social_ad_account_id');
    }

    public function leads()
    {
        return $this->hasMany(SocialAdLead::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    public function defaultCategoryIdList(): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) $this->default_category_ids))));
    }

    /**
     * @return array<string, string> question key => label
     */
    public function questionLabels(): array
    {
        $labels = [];
        foreach ($this->questions ?? [] as $q) {
            if (!empty($q['key'])) {
                $labels[$q['key']] = $q['label'] ?? $q['key'];
            }
        }

        return $labels;
    }

    /**
     * Add any answer keys not yet known as questions (so admins can map them).
     */
    public function rememberQuestions(array $answerKeys, array $labels = []): void
    {
        $questions = $this->questions ?? [];
        $known = array_column($questions, 'key');
        $changed = false;
        foreach ($answerKeys as $key) {
            if ($key !== '' && !in_array($key, $known, true)) {
                $questions[] = ['key' => $key, 'label' => $labels[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key))];
                $known[] = $key;
                $changed = true;
            }
        }
        if ($changed) {
            $this->questions = $questions;
            $this->save();
        }
    }

    public function effectiveMapping(): array
    {
        return SocialAdFieldMapper::effectiveMapping($this->questions ?? [], $this->field_mapping ?? []);
    }

    public function leadOptions(): array
    {
        return [
            'owner_id' => $this->owner_id,
            'lead_source_id' => $this->lead_source_id,
            'lead_status' => $this->lead_status,
            'lead_priority' => $this->lead_priority,
            'category_ids' => $this->defaultCategoryIdList(),
            'title_prefix' => SocialAdIntegration::platformLabel($this->platform) . ' - ' . Str::limit($this->name, 40, ''),
        ];
    }

    public function webhookUrl(): ?string
    {
        return $this->token ? route('social_ads.webhook.generic', $this->token) : null;
    }
}
