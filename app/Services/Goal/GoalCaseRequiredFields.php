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
            'applicant.dob' => ['label' => 'Applicant — Date of Birth', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.pan' => ['label' => 'Applicant — PAN', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.marital_status' => ['label' => 'Applicant — Marital Status', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.marriage_date' => ['label' => 'Applicant — Marriage Date', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.email' => ['label' => 'Applicant — Personal Mail ID', 'group' => 'Applicant (Identity & Contact)', 'default' => false],
            'applicant.official_email' => ['label' => 'Applicant — Official Mail ID', 'group' => 'Applicant (Identity & Contact)', 'default' => false],

            'co_applicant.name' => ['label' => 'Co-Applicant — Name', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.father_name' => ['label' => 'Co-Applicant — Father Name', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.mother_name' => ['label' => 'Co-Applicant — Mother Name', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.mobile' => ['label' => 'Co-Applicant — Mobile Number', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.age' => ['label' => 'Co-Applicant — Age', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.dob' => ['label' => 'Co-Applicant — Date of Birth', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.pan' => ['label' => 'Co-Applicant — PAN', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.marital_status' => ['label' => 'Co-Applicant — Marital Status', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.marriage_date' => ['label' => 'Co-Applicant — Marriage Date', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.relationship' => ['label' => 'Co-Applicant — Relationship', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.email' => ['label' => 'Co-Applicant — Personal Mail ID', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],
            'co_applicant.official_email' => ['label' => 'Co-Applicant — Official Mail ID', 'group' => 'Co-Applicant (Identity & Contact)', 'default' => false],

            'residence.present_address' => ['label' => 'Present Residence Address — Applicant', 'group' => 'Residence', 'default' => false],
            'residence.present_address_co' => ['label' => 'Present Residence Address — Co-Applicant', 'group' => 'Residence', 'default' => false],
            'residence.present_pincode' => ['label' => 'Present Pincode — Applicant', 'group' => 'Residence', 'default' => false],
            'residence.present_pincode_co' => ['label' => 'Present Pincode — Co-Applicant', 'group' => 'Residence', 'default' => false],
            'residence.years_applicant' => ['label' => 'Years at Residence — Applicant', 'group' => 'Residence', 'default' => false],
            'residence.years_co' => ['label' => 'Years at Residence — Co-Applicant', 'group' => 'Residence', 'default' => false],
            'residence.permanent_address' => ['label' => 'Permanent Address — Applicant', 'group' => 'Residence', 'default' => false],
            'residence.permanent_address_co' => ['label' => 'Permanent Address — Co-Applicant', 'group' => 'Residence', 'default' => false],
            'residence.permanent_pincode' => ['label' => 'Permanent Pincode — Applicant', 'group' => 'Residence', 'default' => false],
            'residence.permanent_pincode_co' => ['label' => 'Permanent Pincode — Co-Applicant', 'group' => 'Residence', 'default' => false],

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
     * Co-applicant / *_co fields are only enforced when that party section
     * has any entered value — otherwise applicant-only cases can still save.
     *
     * @param  array<string, mixed>  $customerDetails
     * @return array<string, string>  field => message
     */
    public static function validateCustomerDetails(array $customerDetails, ?Company $company = null): array
    {
        $errors = [];
        $map = static::map($company);
        $catalog = static::catalog();
        $coInUse = static::coApplicantSectionInUse($customerDetails);

        foreach ($map as $key => $required) {
            if (! $required) {
                continue;
            }
            if (! $coInUse && static::isCoApplicantField($key)) {
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
     * @param  array<string, mixed>  $customerDetails
     */
    public static function coApplicantSectionInUse(array $customerDetails): bool
    {
        if (static::hasAnyFilledValue(data_get($customerDetails, 'co_applicant', []))) {
            return true;
        }

        foreach ([
            'residence.present_address_co',
            'residence.present_pincode_co',
            'residence.years_co',
            'residence.permanent_address_co',
            'residence.permanent_pincode_co',
            'employment.type_co',
            'employment.employer_co',
            'employment.income_co',
            'employment.total_exp_co',
            'employment.present_exp_co',
            'employment.designation_co',
            'employment.industry_co',
            'employment.office_address_co',
        ] as $path) {
            $v = data_get($customerDetails, $path);
            if ($v !== null && trim((string) $v) !== '') {
                return true;
            }
        }

        return false;
    }

    public static function isCoApplicantField(string $key): bool
    {
        return str_starts_with($key, 'co_applicant.')
            || str_ends_with($key, '_co')
            || str_contains($key, '.years_co')
            || str_contains($key, '.type_co')
            || str_contains($key, '.employer_co')
            || str_contains($key, '.income_co');
    }

    protected static function hasAnyFilledValue(mixed $value): bool
    {
        if (! is_array($value)) {
            return $value !== null && trim((string) $value) !== '';
        }

        foreach ($value as $item) {
            if (static::hasAnyFilledValue($item)) {
                return true;
            }
        }

        return false;
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
