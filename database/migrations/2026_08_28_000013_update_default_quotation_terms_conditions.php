<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('quotation_settings')) {
            return;
        }

        $html = <<<'HTML'
<h3>Terms &amp; Conditions</h3>
<ol>
<li><strong>Pricing</strong><br>GST will be charged additionally as applicable.</li>
<li><strong>Scope &amp; Assumptions</strong><br>Integration is limited to the agreed Isarva HRMS attendance requirements.<br>Any additional features, customizations, devices, locations, or integrations outside the agreed scope will be quoted separately.</li>
<li><strong>Exclusions</strong><br>Internet/Wi-Fi connectivity and power supply at the attendance location will be provided by the client.<br>Any additional third-party software, API, subscription, or license charges not specifically included in this quotation will be charged separately.<br>Additional hardware/devices required beyond the quoted quantity will be charged separately.<br>AMC and ongoing technical support beyond the agreed scope will be charged separately, if applicable.</li>
<li><strong>Implementation Timeline</strong><br>The estimated implementation and setup timeline is 1 Week from the date of order confirmation, receipt of required information, and availability of the device/network.<br>Any delay in receiving required information, access, or approvals from the client may affect the implementation timeline.</li>
<li><strong>Client Responsibilities</strong><br>The client will provide the required employee details and information necessary for employee enrollment.<br>The client will provide suitable internet/Wi-Fi connectivity and power at the attendance device location.<br>The client will provide the necessary access and cooperation required for HRMS configuration and testing.</li>
<li><strong>Cancellation</strong><br>Once the order is confirmed and implementation/procurement has commenced, cancellation or refund will not be applicable for the completed work, purchased hardware, or third-party licenses/subscriptions.</li>
<li><strong>Validity</strong><br>This quotation is valid for 30 days from the date of issue.</li>
</ol>
<h4>8 Dedicated In Person Priority Support</h4>
<ul>
<li>Priority Ticket Support</li>
<li>Priority Email &amp; Phone Support</li>
<li>On-boarding Assistance</li>
<li>Custom Training Sessions</li>
</ul>
<p>This proposal focuses on delivering an end-to-end HR and workforce management solution for PRIME NMS PRIVATE LIMITED by implementing an Android Face Recognition Attendance Device alongside our native Face Recognition Attendance Application &amp; Setup.</p>
<p>Our objective is to provide a fully integrated solution powered by Isarva HRMS – Complete HR, Payroll &amp; Attendance Management. By handling everything from complete Employee Face Enrollment &amp; Configuration to full system setup and the One-Time Implementation, we eliminate third-party dependencies to streamline your entire employee lifecycle. Fully compliant with GST requirements, this solution will modernize your attendance tracking, automate leave and payroll processing, and deliver total operational accuracy across your organization.</p>
HTML;

        $row = DB::table('quotation_settings')->first();
        if ($row) {
            DB::table('quotation_settings')->where('id', $row->id)->update([
                'default_terms_conditions' => $html,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('quotation_settings')->insert([
            'default_terms_conditions' => $html,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // No rollback for content update.
    }
};
