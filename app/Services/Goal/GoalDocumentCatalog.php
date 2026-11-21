<?php

namespace App\Services\Goal;

class GoalDocumentCatalog
{
    public static function catalog(): array
    {
        return [
            ['key' => 'pan', 'label' => 'PAN Card', 'products' => ['*'], 'mandatory' => true],
            ['key' => 'aadhaar', 'label' => 'Aadhaar', 'products' => ['*'], 'mandatory' => true],
            ['key' => 'photo', 'label' => 'Photograph', 'products' => ['*'], 'mandatory' => true],
            ['key' => 'address_proof', 'label' => 'Address Proof', 'products' => ['*'], 'mandatory' => true],
            ['key' => 'income_proof', 'label' => 'Income Proof', 'products' => ['personal', 'business', 'home'], 'mandatory' => true],
            ['key' => 'bank_statement', 'label' => 'Bank Statement (6 months)', 'products' => ['personal', 'business', 'home'], 'mandatory' => true],
            ['key' => 'salary_slip', 'label' => 'Salary Slips (3 months)', 'products' => ['personal', 'home'], 'mandatory' => false],
            ['key' => 'property_docs', 'label' => 'Property Documents', 'products' => ['home'], 'mandatory' => true],
            ['key' => 'business_proof', 'label' => 'Business Registration / ITR', 'products' => ['business'], 'mandatory' => true],
        ];
    }

    public static function productKey(?string $name): string
    {
        $p = strtolower((string) $name);
        if (str_contains($p, 'home')) {
            return 'home';
        }
        if (str_contains($p, 'personal')) {
            return 'personal';
        }
        if (str_contains($p, 'business') || str_contains($p, 'sme')) {
            return 'business';
        }

        return 'other';
    }

    /**
     * @return array<int, array{key:string,label:string,mandatory:bool,status?:string,party?:string}>
     */
    public static function forProduct(?string $productName, bool $seedStatus = false): array
    {
        $key = self::productKey($productName);
        $out = [];
        foreach (self::catalog() as $doc) {
            $products = $doc['products'] ?? ['*'];
            if (in_array('*', $products, true) || ($key && in_array($key, $products, true))) {
                $row = [
                    'key' => $doc['key'],
                    'label' => $doc['label'],
                    'mandatory' => (bool) ($doc['mandatory'] ?? false),
                    'party' => 'Applicant',
                ];
                if ($seedStatus) {
                    $row['status'] = 'Pending';
                }
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * Document was recorded (Mark Received) and/or has an optional file / final review status.
     */
    public static function isReceived(array $doc): bool
    {
        if (filled($doc['uploaded_at'] ?? null) || filled($doc['received_at'] ?? null)) {
            return true;
        }
        if (filled($doc['path'] ?? ($doc['storage_path'] ?? null))) {
            return true;
        }

        return in_array((string) ($doc['status'] ?? ''), ['Verified', 'Rejected'], true);
    }

    public static function normalizeStatus(array $doc): string
    {
        $st = (string) ($doc['status'] ?? 'Pending');
        if ($st === 'Uploaded') {
            $st = 'Pending';
        }
        if (! in_array($st, ['Pending', 'Verified', 'Rejected'], true)) {
            $st = 'Pending';
        }

        return $st;
    }

    /**
     * Checklist-facing status label (no file required).
     */
    public static function displayStatus(array $doc): string
    {
        $st = self::normalizeStatus($doc);
        if ($st === 'Verified') {
            return 'Verified';
        }
        if ($st === 'Rejected') {
            return 'Rejected';
        }
        if (self::isReceived($doc)) {
            return 'Pending Verification';
        }

        return 'Not recorded';
    }
}
