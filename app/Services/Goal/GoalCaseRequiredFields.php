<?php

namespace App\Services\Goal;

use App\Models\Company;

class GoalCaseRequiredFields
{
    /**
     * Configurable Loan Case Customer fields (Identity & Contact focus + key sections).
     *
     * @return array<string, array{label:string, group:string, default:bool}>
     */
    public static function catalog(): array
    {
        return [
            'applicant.name' => ['label' => 'Applicant — Name', 'group' => 'Applicant (Identity & Contact)', 'default' => true],
            'applicant.father_name' => ['label' => 'Applicant — Father Name', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.mother_name' => ['label' => 'Applicant — Mother Name', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.mobile' => ['label' => 'Applicant — Mobile Number', 'group' => 'Applicant (Identity & Contact)', 'default' => true],
            'applicant.age' => ['label' => 'Applicant — Age', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.marital_status' => ['label' => 'Applicant — Marital Status', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.relationship' => ['label' => 'Applicant — Relationship', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.email' => ['label' => 'Applicant — Personal Mail ID', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.official_email' => ['label' => 'Applicant — Official Mail ID', 'group' => 'Applicant (Identity & Contact)', 'default' => false],

            'co_applicant.name' => ['label' => 'Co-Applicant — Name', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.father_name' => ['label' => 'Co-Applicant — Father Name', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.mother_name' => ['label' => 'Co-Applicant — Mother Name', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.mobile' => ['label' => 'Co-Applicant — Mobile Number', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.age' => ['label' => 'Co-Applicant — Age', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.marital_status' => ['label' => 'Co-Applicant — Marital Status', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.relationship' => ['label' => 'Co-Applicant — Relationship', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.email' => ['label' => 'Co-Applicant — Personal Mail ID', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.official_email' => ['label' => 'Co-Applicant — Official Mail ID', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],

            'residence.present_address' => ['label' => 'Present Residence Address', 'group' => 'Residence', 'default' => false],
            'residence.present_pincode' => ['label' => 'Present Pincode', 'group' => 'Residence', 'default' => false],
            'residence.years_applicant' => ['label' => 'Years at Residence — Applicant', 'group' => 'Residence', 'default' => false],
            'residence.years_co' => ['label' => 'Years at Residence — Co-Applicant', 'group' => 'Residence', 'default' => false],
            'residence.permanent_address' => ['label' => 'Permanent Address', 'group' => 'Residence', 'default' => false],
            'residence.permanent_pincode' => ['label' => 'Permanent Pincode', 'group' => 'Residence', 'default' => false],

            'employment.type_applicant' => ['label' => 'Employment Type — Applicant', 'group' => 'Employment', 'default' => false],
            'employment.type_co' => ['label' => 'Employment Type — Co-Applicant', 'group' => 'Employment', 'default' => false],
            'employment.employer_applicant' => ['label' => 'Employer / Business — Applicant', 'group' => 'Employment', 'default' => false],
            'employment.employer_co' => ['label' => 'Employer / Business — Co-Applicant', 'group' => 'Employment', 'default' => false],
            'employment.income_applicant' => ['label' => 'Monthly / Net Income — Applicant', 'group' => 'Employment', 'default' => false],
            'employment.income_co' => ['label' => 'Monthly / Net Income — Co-Applicant', 'group' => 'Employment', 'default' => false],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        $out = [];
        foreach (static::catalog() as $key => $meta) {
            $out[$key] = (bool) ($meta['default'] ?? false);
        }

        return $out;
    }

    /**
     * Effective required map (defaults merged with company overrides).
     *
     * @return array<string, bool>
     */
    public static function map(?Company $company = null): array
    {
        $company ??= Company::query()->first();
        $stored = is_array($company?->goal_case_required_fields)
            ? $company->goal_case_required_fields
            : [];

        $map = static::defaults();
        foreach ($map as $key => $default) {
            if (array_key_exists($key, $stored)) {
                $map[$key] = (bool) $stored[$key];
            }
        }

        return $map;
    }

    public static function isRequired(string $key, ?Company $company = null): bool
    {
        return (bool) (static::map($company)[$key] ?? false);
    }

    /**
     * Validate customer_details payload against required flags.
     *
     * @param  array<string, mixed>  $customerDetails
     * @return array<string, string>  field => message
     */
    public static function validateCustomerDetails(array $customerDetails, ?Company $company = null): array
    {
        $errors = [];
        $map = static::map($company);
        $catalog = static::catalog();

        foreach ($map as $key => $required) {
            if (! $required) {
                continue;
            }
            $value = data_get($customerDetails, $key);
            $empty = $value === null || (is_string($value) && trim($value) === '');
            if ($empty) {
                $label = $catalog[$key]['label'] ?? $key;
                $errors['customer_details.'.$key] = $label.' is required.';
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $incoming  checkbox map from settings form
     * @return array<string, bool>
     */
    public static function normalizeFromRequest(array $incoming): array
    {
        $out = static::defaults();
        foreach (array_keys($out) as $key) {
            $out[$key] = ! empty($incoming[$key]);
        }

        return $out;
    }
}
