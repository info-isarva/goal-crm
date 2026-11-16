<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalOrgUnit extends Model
{
    public const TYPE_REGION = 'region';
    public const TYPE_CLUSTER = 'cluster';
    public const TYPE_BRANCH = 'branch';

    protected $table = 'goal_org_units';

    protected $fillable = [
        'type',
        'parent_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'goal_org_unit_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Descendants of this unit (clusters under region, branches under cluster/region).
     */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $queue = [$this->id];

        while ($queue !== []) {
            $parentId = array_shift($queue);
            $childIds = self::query()->where('parent_id', $parentId)->pluck('id')->all();
            foreach ($childIds as $childId) {
                if (!in_array($childId, $ids, true)) {
                    $ids[] = $childId;
                    $queue[] = $childId;
                }
            }
        }

        return $ids;
    }
}
