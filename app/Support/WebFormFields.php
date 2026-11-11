<?php

namespace App\Support;

use App\Models\ProductCategory;

/**
 * Lead fields a web form can collect, and how they map into the CRM.
 */
class WebFormFields
{
    /**
     * @return array<string, array{label: string, type: string, max: int, locked?: bool, default_on?: bool, default_required?: bool}>
     */
    public static function catalog(): array
    {
        return [
            'first_name' => ['label' => 'First Name', 'type' => 'text', 'max' => 100, 'locked' => true, 'default_on' => true, 'default_required' => true],
            'last_name' => ['label' => 'Last Name', 'type' => 'text', 'max' => 100, 'default_on' => true],
            'email' => ['label' => 'Email', 'type' => 'email', 'max' => 150, 'default_on' => true, 'default_required' => true],
            'mobile' => ['label' => 'Mobile', 'type' => 'tel', 'max' => 20, 'default_on' => true, 'default_required' => true],
            'company' => ['label' => 'Company Name', 'type' => 'text', 'max' => 255, 'default_on' => true],
            'designation' => ['label' => 'Designation', 'type' => 'text', 'max' => 100],
            'city' => ['label' => 'City', 'type' => 'text', 'max' => 100],
            'state' => ['label' => 'State', 'type' => 'text', 'max' => 100],
            'country' => ['label' => 'Country', 'type' => 'text', 'max' => 100],
            'product_service' => ['label' => 'Product/Service', 'type' => 'select', 'max' => 20],
            'title' => ['label' => 'Subject', 'type' => 'text', 'max' => 100],
            'description' => ['label' => 'Message', 'type' => 'textarea', 'max' => 1000, 'default_on' => true],
        ];
    }

    /**
     * Default field configuration for a new form.
     */
    public static function defaults(): array
    {
        $fields = [];
        foreach (static::catalog() as $key => $def) {
            if (!empty($def['default_on'])) {
                $fields[] = [
                    'key' => $key,
                    'label' => $def['label'],
                    'required' => !empty($def['default_required']),
                    'placeholder' => '',
                ];
            }
        }

        return $fields;
    }

    /**
     * Admin editor rows: every catalog field, with saved settings merged in and saved order first.
     */
    public static function editorRows(?array $saved): array
    {
        $catalog = static::catalog();
        $saved = $saved ?? static::defaults();
        $rows = [];
        foreach ($saved as $field) {
            $key = $field['key'] ?? null;
            if (!$key || !isset($catalog[$key]) || isset($rows[$key])) {
                continue;
            }
            $rows[$key] = [
                'key' => $key,
                'catalog_label' => $catalog[$key]['label'],
                'label' => $field['label'] ?? $catalog[$key]['label'],
                'required' => !empty($field['required']) || !empty($catalog[$key]['locked']),
                'placeholder' => $field['placeholder'] ?? '',
                'enabled' => true,
                'locked' => !empty($catalog[$key]['locked']),
                'type' => $catalog[$key]['type'],
            ];
        }
        foreach ($catalog as $key => $def) {
            if (isset($rows[$key])) {
                continue;
            }
            $rows[$key] = [
                'key' => $key,
                'catalog_label' => $def['label'],
                'label' => $def['label'],
                'required' => !empty($def['default_required']),
                'placeholder' => '',
                'enabled' => !empty($def['locked']),
                'locked' => !empty($def['locked']),
                'type' => $def['type'],
            ];
        }

        return array_values($rows);
    }

    /**
     * Public field definitions sent to the embed script.
     */
    public static function publicConfig(array $fields): array
    {
        $catalog = static::catalog();
        $out = [];
        foreach ($fields as $field) {
            $key = $field['key'] ?? null;
            if (!$key || !isset($catalog[$key])) {
                continue;
            }
            $item = [
                'key' => $key,
                'label' => $field['label'] ?: $catalog[$key]['label'],
                'type' => $catalog[$key]['type'],
                'required' => !empty($field['required']),
                'placeholder' => (string) ($field['placeholder'] ?? ''),
                'max' => $catalog[$key]['max'],
            ];
            if ($key === 'product_service') {
                $item['options'] = ProductCategory::where('status', 1)
                    ->orderBy('category_name')
                    ->get(['id', 'category_name'])
                    ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->category_name])
                    ->values()
                    ->all();
            }
            $out[] = $item;
        }

        return $out;
    }
}
