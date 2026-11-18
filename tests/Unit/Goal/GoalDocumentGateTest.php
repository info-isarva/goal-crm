<?php

namespace Tests\Unit\Goal;

use App\Services\Goal\GoalDocumentCatalog;
use App\Services\Goal\GoalWorkflow;
use PHPUnit\Framework\TestCase;

class GoalDocumentGateTest extends TestCase
{
    public function test_catalog_seeds_mandatory_docs_for_home_loan(): void
    {
        $docs = GoalDocumentCatalog::forProduct('Home Loan', true);
        $this->assertNotEmpty($docs);
        $mandatory = array_values(array_filter($docs, fn ($d) => ! empty($d['mandatory'])));
        $this->assertNotEmpty($mandatory);
        $this->assertSame('Pending', $docs[0]['status']);
        $keys = array_column($docs, 'key');
        $this->assertContains('property_docs', $keys);
    }

    public function test_ready_for_bank_login_requires_verified_docs(): void
    {
        $gate = GoalWorkflow::validateLead('Documents Pending', 'Ready for Bank Login', [
            'docsVerified' => false,
        ]);
        $this->assertFalse($gate['ok']);

        $ok = GoalWorkflow::validateLead('Documents Pending', 'Ready for Bank Login', [
            'docsVerified' => true,
        ]);
        $this->assertTrue($ok['ok']);
    }

    public function test_documents_pending_requires_product(): void
    {
        $gate = GoalWorkflow::validateLead('Qualified', 'Documents Pending', [
            'consent' => true,
        ]);
        $this->assertFalse($gate['ok']);

        $ok = GoalWorkflow::validateLead('Qualified', 'Documents Pending', [
            'consent' => true,
            'product' => 'Home Loan',
        ]);
        $this->assertTrue($ok['ok']);
    }
}
