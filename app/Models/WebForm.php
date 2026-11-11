<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WebForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'token',
        'heading',
        'intro_text',
        'fields',
        'submit_button_text',
        'success_message',
        'redirect_url',
        'theme_color',
        'lead_source_id',
        'lead_status',
        'lead_priority',
        'owner_id',
        'default_category_ids',
        'allowed_domains',
        'is_active',
        'submissions_count',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fields' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (WebForm $form) {
            if (empty($form->token)) {
                $form->token = static::generateToken();
            }
        });
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::lower(Str::random(32));
        } while (static::withTrashed()->where('token', $token)->exists());

        return $token;
    }

    public function submissions()
    {
        return $this->hasMany(WebFormSubmission::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
    }

    /**
     * @return array<int, int>
     */
    public function defaultCategoryIdList(): array
    {
        return array_values(array_filter(array_map('intval', explode(',', (string) $this->default_category_ids))));
    }

    /**
     * @return array<int, string>
     */
    public function allowedDomainList(): array
    {
        $parts = preg_split('/[\s,]+/', strtolower((string) $this->allowed_domains), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_map(function ($d) {
            $d = preg_replace('#^https?://#', '', $d) ?? $d;
            return trim(explode('/', $d)[0], '. ');
        }, $parts)));
    }

    /**
     * Whether a submission from this host is allowed (empty list = any site).
     */
    public function allowsHost(?string $host): bool
    {
        $domains = $this->allowedDomainList();
        if (empty($domains)) {
            return true;
        }
        $host = strtolower((string) $host);
        if ($host === '') {
            return false;
        }
        $appHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        if ($appHost !== '' && $host === $appHost) {
            return true;
        }
        foreach ($domains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    public function hostedUrl(): string
    {
        return route('webforms.public.show', $this->token);
    }

    public function embedScriptUrl(): string
    {
        return route('webforms.public.embed', $this->token);
    }

    public function submitUrl(): string
    {
        return route('webforms.public.submit', $this->token);
    }
}
