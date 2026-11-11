<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\WebForm;

/**
 * Creates a lead from a web form submission.
 */
class WebFormLeadService
{
    public function __construct(protected LeadIntakeService $intake)
    {
    }

    /**
     * @param  array<string, string>  $data  Validated values keyed by catalog field key
     */
    public function createLead(WebForm $form, array $data): Lead
    {
        return $this->intake->create($data, [
            'owner_id' => $form->owner_id ?: $form->created_by,
            'lead_source_id' => $form->lead_source_id,
            'lead_status' => $form->lead_status,
            'lead_priority' => $form->lead_priority,
            'category_ids' => $form->defaultCategoryIdList(),
            'title_prefix' => $form->name,
        ]);
    }

    public static function defaultStatus(): string
    {
        return LeadIntakeService::defaultStatus();
    }
}
