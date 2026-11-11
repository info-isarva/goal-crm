<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Organization;
use App\Models\Person;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates a lead (plus company / contact when needed) from externally captured data
 * such as web forms and social ad lead forms.
 */
class LeadIntakeService
{
    /**
     * @param  array<string, string|null>  $data  Keys: first_name, last_name, full_name, email, mobile, company,
     *                                             designation, city, state, country, product_service, title, description
     * @param  array<string, mixed>  $options  Keys: owner_id, lead_source_id, lead_status, lead_priority,
     *                                         category_ids (int[]), title_prefix
     */
    public function create(array $data, array $options): Lead
    {
        $data = static::normalizeName($data);
        $ownerId = (int) ($options['owner_id'] ?? 0) ?: static::fallbackOwnerId();
        $now = now();

        return DB::transaction(function () use ($data, $options, $ownerId, $now) {
            $firstName = trim((string) ($data['first_name'] ?? ''));
            $lastName = trim((string) ($data['last_name'] ?? ''));
            $fullName = trim($firstName . ' ' . $lastName);

            $organizationId = null;
            $companyName = Str::limit(trim((string) ($data['company'] ?? '')), 255, '');
            if ($companyName !== '') {
                $org = Organization::whereRaw('LOWER(name) = ?', [strtolower($companyName)])->first();
                if (!$org) {
                    $org = Organization::create([
                        'name' => $companyName,
                        'industry_type' => 0,
                        'organization_type' => 0,
                        'website' => '',
                        'address' => '',
                        'city' => Str::limit((string) ($data['city'] ?? ''), 100, ''),
                        'state' => Str::limit((string) ($data['state'] ?? ''), 100, ''),
                        'pincode' => '',
                        'country' => Str::limit((string) ($data['country'] ?? ''), 100, ''),
                        'phone' => '',
                        'email' => '',
                        'user_owner_id' => $ownerId,
                        'created_by' => $ownerId,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'updated_by' => $ownerId,
                    ]);
                }
                $organizationId = $org->id;
            }

            // Contact: match by email first, then by name within the company
            $email = Str::limit(trim((string) ($data['email'] ?? '')), 150, '');
            $mobile = Str::limit(trim((string) ($data['mobile'] ?? '')), 20, '');
            $designation = Str::limit(trim((string) ($data['designation'] ?? '')), 100, '');
            $person = null;
            if ($email !== '') {
                $person = Person::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
            }
            if (!$person && $fullName !== '') {
                $person = Person::findByNameForOrganization($fullName, $organizationId);
            }
            if (!$person) {
                $person = Person::create([
                    'first_name' => $firstName !== '' ? Str::limit($firstName, 100, '') : ($email ?: $mobile ?: 'Unknown'),
                    'last_name' => Str::limit($lastName, 100, ''),
                    'organization_id' => $organizationId,
                    'email' => $email ?: null,
                    'phone' => '',
                    'mobile' => $mobile ?: null,
                    'job_title' => $designation,
                    'created_by' => $ownerId,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'updated_by' => $ownerId,
                    'user_owner_id' => $ownerId,
                ]);
            } else {
                $person->update(array_filter([
                    'email' => $person->email ?: ($email ?: null),
                    'mobile' => $person->mobile ?: ($mobile ?: null),
                    'job_title' => $person->job_title ?: ($designation ?: null),
                    'updated_at' => $now,
                ], fn ($v) => $v !== null && $v !== ''));
            }
            if ($organizationId) {
                $person->attachToOrganization((int) $organizationId);
            }

            $categoryIds = [];
            if (!empty($data['product_service'])
                && ProductCategory::where('status', 1)->whereKey((int) $data['product_service'])->exists()) {
                $categoryIds = [(int) $data['product_service']];
            }
            if (empty($categoryIds)) {
                $categoryIds = array_values(array_filter(array_map('intval', (array) ($options['category_ids'] ?? []))));
            }

            $title = trim((string) ($data['title'] ?? ''));
            if ($title === '') {
                $who = $companyName !== '' ? $companyName : ($fullName !== '' ? $fullName : ($email ?: $mobile));
                $title = trim(($options['title_prefix'] ?? 'Lead') . ' - ' . $who, ' -');
            }
            $description = trim((string) ($data['description'] ?? ''));

            return Lead::create([
                'title' => Str::limit($title, 100, ''),
                'organization_id' => $organizationId,
                'people_id' => $person->id,
                'status' => ($options['lead_status'] ?? null) ?: static::defaultStatus(),
                'label' => in_array($options['lead_priority'] ?? null, ['high', 'normal', 'low'], true) ? $options['lead_priority'] : 'normal',
                'lead_source' => ($options['lead_source_id'] ?? null) ?: null,
                'category' => !empty($categoryIds) ? implode(',', $categoryIds) : null,
                'description' => $description !== '' ? Str::limit($description, 250, '') : null,
                'user_owner_id' => $ownerId,
                'assigned_id' => $ownerId,
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    /**
     * Fill first/last name from a single full-name value when needed.
     */
    public static function normalizeName(array $data): array
    {
        $full = trim((string) ($data['full_name'] ?? ''));
        if ($full !== '' && trim((string) ($data['first_name'] ?? '')) === '') {
            $parts = preg_split('/\s+/', $full, 2);
            $data['first_name'] = $parts[0];
            if (trim((string) ($data['last_name'] ?? '')) === '') {
                $data['last_name'] = $parts[1] ?? '';
            }
        }
        unset($data['full_name']);

        return $data;
    }

    public static function defaultStatus(): string
    {
        return LeadStatus::whereRaw('LOWER(name) = ?', ['not contacted'])->value('name')
            ?? LeadStatus::orderBy('id')->value('name')
            ?? 'Not Contacted';
    }

    public static function fallbackOwnerId(): int
    {
        return (int) (User::where('crm_role_type', 0)->where('status', 1)->orderBy('id')->value('id')
            ?? User::orderBy('id')->value('id'));
    }
}
