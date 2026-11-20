<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GoalLoanPurpose extends Model
{
    use SoftDeletes;

    protected $table = 'goal_loan_purposes';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $name = trim((string) $model->name);
            $model->name = $name;
            if ($model->slug === null || $model->slug === '' || $model->isDirty('name')) {
                $model->slug = Str::slug($name) ?: Str::lower(Str::random(8));
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return list<string>
     */
    public static function activeNames(): array
    {
        return static::query()
            ->active()
            ->pluck('name')
            ->map(fn ($n) => (string) $n)
            ->values()
            ->all();
    }
}
