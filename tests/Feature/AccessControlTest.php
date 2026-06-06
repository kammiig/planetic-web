<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Row-level ownership (Security & Access §7) and staff role gating (§5),
 * verified at the policy/gate layer — the guard every customer query must pass.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_their_own_records(): void
    {
        $customer = $this->createUser(RoleName::Customer);
        $domain = new Domain(['user_id' => $customer->id]);

        $this->assertTrue($customer->can('view', $domain));
    }

    public function test_customer_cannot_view_another_customers_domain(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $intruder = $this->createUser(RoleName::Customer);
        $domain = new Domain(['user_id' => $owner->id]);

        $this->assertFalse($intruder->can('view', $domain));
    }

    public function test_customer_cannot_view_another_customers_invoice(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $intruder = $this->createUser(RoleName::Customer);
        $invoice = new Invoice(['user_id' => $owner->id]);

        $this->assertFalse($intruder->can('view', $invoice));
    }

    public function test_customer_cannot_view_another_customers_support_ticket(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $intruder = $this->createUser(RoleName::Customer);
        $ticket = new SupportTicket(['user_id' => $owner->id]);

        $this->assertFalse($intruder->can('view', $ticket));
    }

    public function test_super_admin_can_view_everything(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $superAdmin = $this->createUser(RoleName::SuperAdmin);
        $domain = new Domain(['user_id' => $owner->id]);
        $invoice = new Invoice(['user_id' => $owner->id]);

        $this->assertTrue($superAdmin->can('view', $domain));
        $this->assertTrue($superAdmin->can('view', $invoice));
    }

    public function test_technical_admin_can_view_domains_but_not_billing(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $tech = $this->createUser(RoleName::TechnicalAdmin);

        $this->assertTrue($tech->can('view', new Domain(['user_id' => $owner->id])));
        // Technical admin should not have billing oversight of payments.
        $this->assertFalse($tech->can('viewAny', Payment::class));
    }

    public function test_billing_manager_can_view_invoices_but_not_manage_dns(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $billing = $this->createUser(RoleName::BillingManager);

        $this->assertTrue($billing->can('view', new Invoice(['user_id' => $owner->id])));
        $this->assertFalse($billing->can('viewAny', \App\Models\DnsRecord::class));
    }

    public function test_support_staff_cannot_mark_invoices_paid(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $support = $this->createUser(RoleName::SupportStaff);

        $this->assertFalse($support->can('update', new Invoice(['user_id' => $owner->id])));
    }
}
