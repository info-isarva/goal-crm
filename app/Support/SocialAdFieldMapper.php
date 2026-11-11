<?php

namespace App\Support;

/**
 * Maps ad lead form answers (Meta, Google, LinkedIn, generic webhooks) onto CRM lead fields.
 */
class SocialAdFieldMapper
{
    public const IGNORE = '__ignore';

    /**
     * CRM fields an ad question can be mapped to.
     */
    public static function crmFields(): array
    {
        return [
            'full_name' => 'Full Name',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email',
            'mobile' => 'Mobile / Phone',
            'company' => 'Company Name',
            'designation' => 'Designation / Job Title',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
            'title' => 'Lead Title / Subject',
            'description' => 'Description / Message',
        ];
    }

    /**
     * Normalized question key => CRM field.
     */
    protected static function aliases(): array
    {
        return [
            'fullname' => 'full_name', 'name' => 'full_name', 'yourname' => 'full_name', 'contactname' => 'full_name',
            'firstname' => 'first_name', 'fname' => 'first_name', 'givenname' => 'first_name',
            'lastname' => 'last_name', 'lname' => 'last_name', 'surname' => 'last_name', 'familyname' => 'last_name',
            'email' => 'email', 'emailaddress' => 'email', 'workemail' => 'email', 'businessemail' => 'email', 'mail' => 'email',
            'phone' => 'mobile', 'phonenumber' => 'mobile', 'mobile' => 'mobile', 'mobilenumber' => 'mobile', 'mobileno' => 'mobile',
            'workphone' => 'mobile', 'workphonenumber' => 'mobile', 'contactnumber' => 'mobile', 'whatsapp' => 'mobile',
            'whatsappnumber' => 'mobile', 'telephone' => 'mobile', 'tel' => 'mobile', 'cell' => 'mobile',
            'company' => 'company', 'companyname' => 'company', 'organization' => 'company', 'organisation' => 'company',
            'organizationname' => 'company', 'businessname' => 'company', 'business' => 'company',
            'jobtitle' => 'designation', 'designation' => 'designation', 'position' => 'designation', 'role' => 'designation',
            'city' => 'city', 'town' => 'city',
            'state' => 'state', 'region' => 'state', 'province' => 'state',
            'country' => 'country',
            'subject' => 'title',
            'message' => 'description', 'comments' => 'description', 'comment' => 'description', 'description' => 'description',
            'enquiry' => 'description', 'inquiry' => 'description', 'requirement' => 'description', 'requirements' => 'description',
            'notes' => 'description', 'note' => 'description', 'details' => 'description',
        ];
    }

    public static function normalizeKey(string $key): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($key)) ?? '';
    }

    /**
     * Best-guess CRM field for a question key or label, or null when unknown.
     */
    public static function guess(string $key, ?string $label = null): ?string
    {
        $aliases = static::aliases();
        foreach ([$key, (string) $label] as $candidate) {
            $norm = static::normalizeKey($candidate);
            if ($norm !== '' && isset($aliases[$norm])) {
                return $aliases[$norm];
            }
        }

        return null;
    }

    /**
     * Resolve the effective mapping (question key => CRM field|IGNORE|null) for a set of questions.
     *
     * @param  array<int, array{key: string, label?: string}>  $questions
     * @param  array<string, string>  $overrides
     */
    public static function effectiveMapping(array $questions, array $overrides): array
    {
        $map = [];
        foreach ($questions as $q) {
            $key = (string) ($q['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $override = $overrides[$key] ?? '';
            $map[$key] = $override !== '' ? $override : static::guess($key, $q['label'] ?? null);
        }

        return $map;
    }

    /**
     * Convert raw answers into CRM data. Unmapped answers are appended to the description.
     *
     * @param  array<string, string>  $answers  question key => answer
     * @param  array<string, string>  $labels  question key => label (for unmapped answers)
     * @param  array<string, string>  $overrides  question key => CRM field|IGNORE
     */
    public static function map(array $answers, array $labels = [], array $overrides = []): array
    {
        $data = [];
        $extras = [];
        foreach ($answers as $key => $value) {
            $value = trim(is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value);
            if ($value === '') {
                continue;
            }
            $override = $overrides[$key] ?? '';
            $field = $override !== '' ? $override : static::guess((string) $key, $labels[$key] ?? null);
            if ($field === static::IGNORE) {
                continue;
            }
            if ($field && isset(static::crmFields()[$field])) {
                $data[$field] = isset($data[$field]) && $field === 'description'
                    ? $data[$field] . "\n" . $value
                    : ($data[$field] ?? $value);
                continue;
            }
            $extras[] = ($labels[$key] ?? ucwords(str_replace(['_', '-'], ' ', (string) $key))) . ': ' . $value;
        }

        if (!empty($extras)) {
            $data['description'] = trim(($data['description'] ?? '') . "\n" . implode("\n", $extras));
        }

        return $data;
    }
}
