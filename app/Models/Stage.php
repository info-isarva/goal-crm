<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Stage extends Model
{
    use HasFactory;

    protected $table = 'stages';

    protected $fillable = [
        'name',
        'description',
        'order_by',
        'probability',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public $timestamps = false;

    public static function idByName(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $map = static::nameIdMap();

        return $map[strtolower($name)] ?? null;
    }

    public static function nameById($id): ?string
    {
        if ($id === null || $id === '') {
            return null;
        }

        $id = (int) $id;
        foreach (static::nameIdMap() as $name => $stageId) {
            if ((int) $stageId === $id) {
                // map keys are lowercase; fetch real name
                return static::query()->where('id', $id)->value('name');
            }
        }

        return static::query()->where('id', $id)->value('name');
    }

    /**
     * Resolve request input that may be a stage id or stage name.
     */
    public static function resolveId($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $id = (int) $value;

            return static::query()->where('id', $id)->exists() ? $id : null;
        }

        return static::idByName((string) $value);
    }

    public static function closedWonId(): ?int
    {
        return static::idByName('Closed Won');
    }

    public static function closedLostId(): ?int
    {
        return static::idByName('Closed Lost');
    }

    public static function qualificationId(): ?int
    {
        return static::idByName('Qualification');
    }

    public static function proposalPriceQuoteId(): ?int
    {
        return static::idByName('Proposal/Price Quote');
    }

    /** @return array<string, int> lowercase name => id */
    public static function nameIdMap(): array
    {
        return Cache::remember('crm_stages_name_id_map', 60, function () {
            $map = [];
            foreach (static::query()->get(['id', 'name']) as $stage) {
                $map[strtolower(trim((string) $stage->name))] = (int) $stage->id;
            }

            return $map;
        });
    }

    public static function clearNameIdMapCache(): void
    {
        Cache::forget('crm_stages_name_id_map');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class, 'stage_id');
    }
}
