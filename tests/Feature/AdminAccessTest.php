<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_customers_cannot_access_the_admin_panel(): void
    {
        $customer = $this->createUser(RoleName::Customer);

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_suspended_staff_cannot_access_the_admin_panel(): void
    {
        $staff = $this->createUser(RoleName::SupportStaff, ['status' => 'suspended']);

        $this->actingAs($staff)->get('/admin')->assertForbidden();
    }

    public function test_super_admin_can_access_the_admin_dashboard(): void
    {
        $this->seed(ProductSeeder::class);
        $admin = $this->createUser(RoleName::SuperAdmin);

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_staff_can_view_resources_their_role_allows(): void
    {
        $tech = $this->createUser(RoleName::TechnicalAdmin);

        // Technical admin can see domains/hosting...
        $this->actingAs($tech)->get('/admin/domains')->assertOk();
        $this->actingAs($tech)->get('/admin/hosting-accounts')->assertOk();
    }

    public function test_non_super_admin_cannot_view_audit_logs(): void
    {
        $support = $this->createUser(RoleName::SupportStaff);

        // AuditLogPolicy::viewAny is Super Admin only.
        $this->actingAs($support)->get('/admin/audit-logs')->assertForbidden();
    }
}
