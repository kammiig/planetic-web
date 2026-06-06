<?php

namespace Tests\Feature;

use App\Jobs\Renewals\SendRenewalReminderJob;
use App\Jobs\Renewals\SuspendOverdueHostingJob;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use App\Models\User;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RenewalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);
        config()->set('billing.renewal_reminder_days_before', [30, 14, 7, 3, 1]);
        config()->set('billing.grace_period_days', 7);
    }

    private function hostingAccount(array $attrs = []): HostingAccount
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        return HostingAccount::create(array_merge([
            'user_id' => $user->id,
            'hosting_package_id' => HostingPackage::first()->id,
            'domain_name' => 'example.com',
            'whm_username' => 'examp01',
            'status' => 'active',
        ], $attrs));
    }

    public function test_renewals_check_queues_reminders_for_services_due_in_a_window(): void
    {
        Queue::fake();
        $this->hostingAccount(['renewal_date' => today()->addDays(7)->toDateString()]);

        $this->artisan('renewals:check')->assertSuccessful();

        Queue::assertPushed(SendRenewalReminderJob::class);
    }

    public function test_renewals_check_queues_suspension_for_overdue_hosting(): void
    {
        Queue::fake();
        // Renewal date 10 days ago, grace is 7 → overdue past grace.
        $this->hostingAccount(['renewal_date' => today()->subDays(10)->toDateString()]);

        $this->artisan('renewals:check')->assertSuccessful();

        Queue::assertPushed(SuspendOverdueHostingJob::class);
    }

    public function test_reminder_job_sends_once_and_is_deduplicated(): void
    {
        Mail::fake();
        $account = $this->hostingAccount(['renewal_date' => today()->addDays(7)->toDateString()]);
        $key = 'hosting:'.$account->id.':7:'.$account->renewal_date->toDateString();

        $args = [$account->user_id, 'Hosting for example.com', $account->renewal_date->toDateString(), null, 7, $key];

        (new SendRenewalReminderJob(...$args))->handle(app(\App\Services\Notifications\NotificationService::class));
        (new SendRenewalReminderJob(...$args))->handle(app(\App\Services\Notifications\NotificationService::class));

        $this->assertSame(1, \App\Models\NotificationLog::where('type', 'renewal_reminder')->count());
        Mail::assertSent(\App\Mail\RenewalReminderMail::class, 1);
    }

    public function test_suspend_overdue_hosting_job_suspends_and_emails(): void
    {
        Mail::fake();
        config()->set('whm.host', 'whm.test');
        config()->set('whm.username', 'root');
        config()->set('whm.token', 'tok');
        Http::fake(['whm.test*' => Http::response(['metadata' => ['result' => 1, 'reason' => 'OK']])]);

        $account = $this->hostingAccount(['renewal_date' => today()->subDays(10)->toDateString()]);

        (new SuspendOverdueHostingJob($account->id))->handle(
            app(\App\Services\Renewals\SuspensionService::class),
            app(\App\Services\Notifications\NotificationService::class),
            app(\App\Services\Audit\AuditLogger::class),
        );

        $this->assertSame('suspended', $account->fresh()->status->value);
        Mail::assertSent(\App\Mail\HostingSuspendedMail::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'hosting.suspend.auto']);
    }
}
