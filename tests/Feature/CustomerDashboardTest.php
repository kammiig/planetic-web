<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\WebsiteProjectStatus;
use App\Models\Domain;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WebsiteProject;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_unverified_users_see_dashboard_with_verification_banner(): void
    {
        $user = $this->createUser(RoleName::Customer, ['email_verified_at' => null]);

        // Verification is encouraged, never a wall: the dashboard loads and a
        // persistent banner (with a resend button) asks them to verify.
        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('Please verify your email address.')
            ->assertSee('Resend verification email');
    }

    public function test_verified_customer_can_view_their_dashboard(): void
    {
        $user = $this->createUser(RoleName::Customer);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Dashboard');
    }

    public function test_customer_cannot_view_another_customers_domain(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $intruder = $this->createUser(RoleName::Customer);

        $domain = Domain::create([
            'user_id' => $owner->id, 'domain_name' => 'owner.com', 'sld' => 'owner', 'tld' => 'com', 'status' => 'active',
        ]);

        // Owner sees it; intruder gets a 404 (existence hidden).
        $this->actingAs($owner)->get(route('customer.domains.show', $domain))->assertOk();
        $this->actingAs($intruder)->get(route('customer.domains.show', $domain))->assertNotFound();
    }

    public function test_customer_cannot_view_another_customers_invoice(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $intruder = $this->createUser(RoleName::Customer);

        $invoice = Invoice::create([
            'user_id' => $owner->id, 'invoice_number' => 'INV-1', 'currency' => 'GBP',
            'subtotal' => 200, 'total' => 200, 'amount_paid' => 200, 'status' => 'paid',
        ]);

        $this->actingAs($intruder)->get(route('customer.invoices.show', $invoice))->assertNotFound();
    }

    public function test_customer_cannot_view_another_customers_hosting(): void
    {
        $this->seed([ProductSeeder::class, HostingPackageSeeder::class]);
        $owner = $this->createUser(RoleName::Customer);
        $intruder = $this->createUser(RoleName::Customer);

        $account = HostingAccount::create([
            'user_id' => $owner->id, 'hosting_package_id' => HostingPackage::first()->id,
            'domain_name' => 'owner.com', 'whm_username' => 'owner01', 'status' => 'active',
        ]);

        $this->actingAs($intruder)->get(route('customer.hosting.show', $account))->assertNotFound();
    }

    public function test_customer_cannot_view_another_customers_support_ticket(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $intruder = $this->createUser(RoleName::Customer);

        $ticket = SupportTicket::create([
            'user_id' => $owner->id, 'ticket_number' => 'TCK-1', 'subject' => 'Help', 'status' => 'open',
        ]);

        $this->actingAs($intruder)->get(route('customer.support.show', $ticket))->assertNotFound();
    }

    public function test_customer_can_open_a_support_ticket(): void
    {
        $user = $this->createUser(RoleName::Customer);

        $this->actingAs($user)->post(route('customer.support.store'), [
            'subject' => 'My domain is not loading',
            'category' => 'domain',
            'message' => 'It has been a few hours.',
        ])->assertRedirect();

        $this->assertDatabaseHas('support_tickets', ['user_id' => $user->id, 'subject' => 'My domain is not loading']);
        $this->assertDatabaseHas('support_ticket_messages', ['message' => 'It has been a few hours.', 'is_internal_note' => false]);
    }

    public function test_website_project_intake_accepts_details_and_safe_files(): void
    {
        Storage::fake('local');
        $user = $this->createUser(RoleName::Customer);
        $order = Order::create([
            'user_id' => $user->id, 'order_number' => 'ORD-1', 'status' => 'completed', 'payment_status' => 'succeeded',
            'currency' => 'GBP', 'subtotal' => 200, 'total' => 200, 'paid_at' => now(),
        ]);
        $project = WebsiteProject::create([
            'user_id' => $user->id, 'order_id' => $order->id, 'project_number' => 'PRJ-1',
            'status' => WebsiteProjectStatus::InformationRequired->value,
        ]);

        $this->actingAs($user)->post(route('customer.projects.intake', $project), [
            'business_name' => 'Example Cleaning',
            'business_description' => 'A local cleaning business.',
            'pages_required' => ['Home', 'Contact'],
            'logo' => UploadedFile::fake()->image('logo.png'),
            'files' => [UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf')],
        ])->assertRedirect(route('customer.projects.show', $project));

        $project->refresh();
        $this->assertSame(WebsiteProjectStatus::ContentReceived, $project->status);
        $this->assertTrue($project->content_received);
        $this->assertTrue($project->logo_received);
        $this->assertSame(2, $project->assets()->count());
    }

    public function test_website_project_intake_rejects_dangerous_files(): void
    {
        Storage::fake('local');
        $user = $this->createUser(RoleName::Customer);
        $order = Order::create([
            'user_id' => $user->id, 'order_number' => 'ORD-2', 'status' => 'completed', 'payment_status' => 'succeeded',
            'currency' => 'GBP', 'subtotal' => 200, 'total' => 200, 'paid_at' => now(),
        ]);
        $project = WebsiteProject::create([
            'user_id' => $user->id, 'order_id' => $order->id, 'project_number' => 'PRJ-2',
            'status' => WebsiteProjectStatus::InformationRequired->value,
        ]);

        $this->actingAs($user)->post(route('customer.projects.intake', $project), [
            'business_name' => 'Example',
            'files' => [UploadedFile::fake()->create('evil.php', 10, 'application/x-php')],
        ])->assertSessionHasErrors('files.0');

        $this->assertSame(0, $project->assets()->count());
    }
}
