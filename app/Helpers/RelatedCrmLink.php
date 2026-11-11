<?php

namespace App\Helpers;

use App\Models\Deal;
use App\Models\Lead;

class RelatedCrmLink
{
    /**
     * Resolve related lead/deal display name + absolute URL for emails.
     *
     * @return array{type: string, label: string, name: string, url: string}|null
     */
    public static function resolve(?string $relatedType, $relatedId): ?array
    {
        $type = strtolower(trim((string) $relatedType));
        $id = (int) $relatedId;

        if ($id <= 0 || !in_array($type, ['lead', 'deal'], true)) {
            return null;
        }

        if ($type === 'lead') {
            $item = Lead::query()->find($id);
            if (!$item) {
                return null;
            }

            $name = trim((string) ($item->title ?? ''));
            if ($name === '') {
                $name = 'Lead #' . $id;
            }

            return [
                'type' => 'lead',
                'label' => 'Lead',
                'name' => $name,
                'url' => route('leads.show', $id, true),
            ];
        }

        $item = Deal::query()->find($id);
        if (!$item) {
            return null;
        }

        $name = trim((string) ($item->title ?? ''));
        if ($name === '') {
            $name = 'Deal #' . $id;
        }

        return [
            'type' => 'deal',
            'label' => 'Deal',
            'name' => $name,
            'url' => route('deals.show', $id, true),
        ];
    }

    /**
     * Resolve from a Meeting or Task model (or array-like object).
     *
     * @param  object|array  $entity
     * @return array{type: string, label: string, name: string, url: string}|null
     */
    public static function fromEntity($entity): ?array
    {
        if (is_array($entity)) {
            return self::resolve($entity['related_type'] ?? null, $entity['related_id'] ?? null);
        }

        return self::resolve(
            $entity->related_type ?? null,
            $entity->related_id ?? null
        );
    }
}
