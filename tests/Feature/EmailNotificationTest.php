<?php

namespace Tests\Feature;

use App\Mail\HostingReactivatedMail;
use App\Mail\HostingSuspendedMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\PaymentFailedMail;
use App\Mail\ProvisioningCompletedMail;
use App\Mail\RenewalReminderMail;
use App\Models\HostingAccount;
use App\Models\HostingPackage;
use App\Models\Order;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Database\Seeders\HostingPackageSeeder;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $order = Order::create([
            'user_id' => $user->id, 'order_number' => 'ORD-10001', 'status' => 'completed',
            'payment_status' => 'succeeded', 'currency' => 'GBP', 'subtotal' => 200, 'total' => 200, 'paid_at' => now(),
        ]);
        $order->items()->create([
            'item_type' => 'website_package', 'name' => 'Complete Bespoke Website', 'domain_name' => 'example.com',
            'quantity' => 1, 'unit_price' => 200, 'total' => 200,
        ]);

        return $order->load('items');
    }

    public function test_all_mailables_render_without_error(): void
    {
        $this->seed([RoleSeeder::class, ProductSeeder::class, HostingPackageSeeder::class]);
        $order = $this->order();
        $account = HostingAccount::create([
            'user_id' => $order->user_id, 'hosting_package_id' => HostingPackage::first()->id,
            'domain_name' => 'example.com', 'whm_username' => 'examp01', 'status' => 'active',
        ]);

        $this->assertStringContainsString('Thanks for your order', (new OrderConfirmationMail($order))->render());
        $this->assertStringContainsString('ready', (new ProvisioningCompletedMail($order, null, $account))->render());
        $this->assertStringContainsString('payment', strtolower((new PaymentFailedMail($order))->render()));
        $this->assertStringContainsString('renew', strtolower((new RenewalReminderMail('Jane', 'Starter Hosting', '1 Jun 2027', 49.00, 7))->render()));
        $this->assertStringContainsString('suspended', strtolower((new HostingSuspendedMail($account))->render()));
        $this->assertStringContainsString('active again', strtolower((new HostingReactivatedMail($account))->render()));
    }

    public function test_notification_service_logs_a_sent_email(): void
    {
        Mail::fake();
        $order = $this->order();

        $log = app(NotificationService::class)->send($order->user, new OrderConfirmationMail($order), 'order_confirmation');

        $this->assertSame('sent', $log->status);
        $this->assertDatabaseHas('notification_logs', [
            'type' => 'order_confirmation', 'status' => 'sent', 'recipient' => $order->user->email,
        ]);
        Mail::assertSent(OrderConfirmationMail::class);
    }

    public function test_order_confirmation_logs_failure_when_mail_throws(): void
    {
        // Force the transport to fail.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $order = $this->order();
        $log = app(NotificationService::class)->send($order->user, new OrderConfirmationMail($order), 'order_confirmation');

        $this->assertSame('failed', $log->status);
        $this->assertNotNull($log->error_message);
    }
}
