<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GoalBank extends Model
{
    use SoftDeletes;

    protected $table = 'goal_banks';

    protected $fillable = [
        'name',
        'code',
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
            $model->name = trim((string) $model->name);
            $model->code = Str::upper(trim((string) $model->code));
            if ($model->slug === null || $model->slug === '' || $model->isDirty('name')) {
                $model->slug = Str::slug($model->name) ?: Str::lower(Str::random(8));
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function label(): string
    {
        $code = trim((string) $this->code);

        return $code !== '' ? $this->name.' ('.$code.')' : (string) $this->name;
    }

    /**
     * @return list<array{id:int,text:string,name:string,code:string}>
     */
    public static function searchResults(?string $term, int $limit = 20): array
    {
        $q = static::query()->active();
        $term = trim((string) $term);

        if ($term !== '') {
            $like = '%'.$term.'%';
            $q->where(function (Builder $inner) use ($like, $term) {
                $inner->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('code', 'like', '%'.Str::upper($term).'%');
            });
        }

        return $q->limit($limit)
            ->get(['id', 'name', 'code'])
            ->map(fn (self $bank) => [
                'id' => (int) $bank->id,
                'text' => $bank->label(),
                'name' => (string) $bank->name,
                'code' => (string) $bank->code,
            ])
            ->values()
            ->all();
    }
}
