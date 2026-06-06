<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\Provisioning\ProvisionOrderJob;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, ProductSeeder::class]);
        config()->set('stripe.webhook_secret', $this->secret);
        config()->set('stripe.secret_key', 'sk_test_dummy');
    }

    private function pendingOrder(): Order
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-10001',
            'status' => OrderStatus::Pending->value,
            'payment_status' => PaymentStatus::Pending->value,
            'currency' => 'GBP',
            'subtotal' => 200,
            'total' => 200,
            'stripe_checkout_session_id' => 'cs_test_123',
        ]);

        $order->items()->create([
            'item_type' => 'website_package',
            'name' => 'Complete Bespoke Website',
            'domain_name' => 'example.com',
            'quantity' => 1,
            'unit_price' => 200,
            'total' => 200,
        ]);

        return $order;
    }

    private function checkoutCompletedPayload(Order $order): string
    {
        return json_encode([
            'id' => 'evt_test_'.uniqid(),
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_123',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_123',
                'customer' => 'cus_test_123',
                'metadata' => ['order_id' => (string) $order->id],
            ]],
        ], JSON_THROW_ON_ERROR);
    }

    private function signature(string $payload, ?int $timestamp = null): string
    {
        $timestamp ??= time();
        $signed = $timestamp.'.'.$payload;

        return 't='.$timestamp.',v1='.hash_hmac('sha256', $signed, $this->secret);
    }

    private function postWebhook(string $payload, string $signature)
    {
        return $this->call('POST', '/webhooks/stripe', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    public function test_valid_webhook_marks_order_paid_and_starts_provisioning(): void
    {
        Queue::fake();
        $order = $this->pendingOrder();
        $payload = $this->checkoutCompletedPayload($order);

        $this->postWebhook($payload, $this->signature($payload))->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Provisioning, $order->status);
        $this->assertSame(PaymentStatus::Succeeded, $order->payment_status);
        $this->assertNotNull($order->paid_at);

        $this->assertDatabaseHas('invoices', ['order_id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('website_projects', ['order_id' => $order->id]);

        Queue::assertPushed(ProvisionOrderJob::class);
    }

    public function test_invalid_signature_is_rejected_and_nothing_is_provisioned(): void
    {
        Queue::fake();
        $order = $this->pendingOrder();
        $payload = $this->checkoutCompletedPayload($order);

        $this->postWebhook($payload, 't='.time().',v1=deadbeef')->assertStatus(400);

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        Queue::assertNotPushed(ProvisionOrderJob::class);
    }

    public function test_duplicate_webhook_does_not_provision_twice(): void
    {
        Queue::fake();
        $order = $this->pendingOrder();

        $payload = json_encode([
            'id' => 'evt_duplicate_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_123',
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_123',
                'customer' => 'cus_test_123',
                'metadata' => ['order_id' => (string) $order->id],
            ]],
        ], JSON_THROW_ON_ERROR);
        $signature = $this->signature($payload);

        $this->postWebhook($payload, $signature)->assertOk();
        $this->postWebhook($payload, $signature)->assertOk(); // same event id again

        Queue::assertPushed(ProvisionOrderJob::class, 1);
        $this->assertSame(1, \App\Models\Invoice::where('order_id', $order->id)->count());
        $this->assertSame(1, \App\Models\WebsiteProject::where('order_id', $order->id)->count());
    }

    public function test_unpaid_session_does_not_provision(): void
    {
        Queue::fake();
        $order = $this->pendingOrder();

        $payload = json_encode([
            'id' => 'evt_unpaid_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_123',
                'payment_status' => 'unpaid',
                'metadata' => ['order_id' => (string) $order->id],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->postWebhook($payload, $this->signature($payload))->assertOk();

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        Queue::assertNotPushed(ProvisionOrderJob::class);
    }
}
