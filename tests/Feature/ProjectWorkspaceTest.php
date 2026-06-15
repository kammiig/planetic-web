<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Enums\WebsiteProjectStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\WebsiteProject;
use App\Models\WebsiteProjectMeeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function project(User $user, array $attributes = []): WebsiteProject
    {
        $order = Order::create([
            'user_id' => $user->id, 'order_number' => 'ORD-40001',
            'status' => 'completed', 'payment_status' => 'succeeded', 'paid_at' => now(),
            'currency' => 'GBP', 'subtotal' => 200, 'discount_total' => 0, 'tax_total' => 0, 'total' => 200,
        ]);

        return WebsiteProject::create(array_merge([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'project_number' => 'PRJ-40001',
            'status' => WebsiteProjectStatus::DesignInProgress->value,
            'business_name' => 'Acme',
        ], $attributes));
    }

    public function test_customer_can_post_a_message_with_an_attachment(): void
    {
        $user = $this->createUser(RoleName::Customer);
        $project = $this->project($user);

        $this->actingAs($user)->post(route('customer.projects.messages.store', $project), [
            'body' => 'Here is my logo',
            'attachments' => [UploadedFile::fake()->image('logo.png')],
        ])->assertRedirect()->assertSessionHas('success');

        $message = $project->messages()->firstOrFail();
        $this->assertSame('Here is my logo', $message->body);
        $this->assertFalse($message->is_from_staff);
        $this->assertSame(1, $message->attachments()->count());
        Storage::disk('local')->assertExists($message->attachments()->first()->path);

        // Visible on the project page and downloadable by the owner.
        $attachment = $message->attachments()->first();
        $this->actingAs($user)->get(route('customer.projects.show', $project))
            ->assertOk()->assertSee('Here is my logo')->assertSee('logo.png');
        $this->actingAs($user)
            ->get(route('customer.projects.messages.download', [$project, $attachment]))
            ->assertOk()->assertDownload('logo.png');
    }

    public function test_message_attachments_are_private_to_the_owner(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $other = $this->createUser(RoleName::Customer);
        $project = $this->project($owner);

        $this->actingAs($owner)->post(route('customer.projects.messages.store', $project), [
            'body' => 'private', 'attachments' => [UploadedFile::fake()->image('x.png')],
        ]);
        $attachment = $project->messages()->first()->attachments()->first();

        $this->actingAs($other)
            ->get(route('customer.projects.messages.download', [$project, $attachment]))
            ->assertNotFound();
    }

    public function test_revision_can_be_requested_within_the_window_only(): void
    {
        $user = $this->createUser(RoleName::Customer);

        // Delivered today, 14-day window → allowed.
        $project = $this->project($user, [
            'status' => WebsiteProjectStatus::Delivered->value,
            'delivered_at' => now(),
            'revision_days' => 14,
        ]);

        $this->actingAs($user)->post(route('customer.projects.revision', $project), [
            'body' => 'Please change the header colour',
        ])->assertRedirect()->assertSessionHas('success');

        $project->refresh();
        $this->assertSame(WebsiteProjectStatus::RevisionsInProgress, $project->status);
        $this->assertSame(1, $project->revisions_used);
        $this->assertStringContainsString('Revision requested', $project->messages()->first()->body);
    }

    public function test_revision_is_blocked_after_the_window_ends(): void
    {
        $user = $this->createUser(RoleName::Customer);
        $project = $this->project($user, [
            'status' => WebsiteProjectStatus::Delivered->value,
            'delivered_at' => Carbon::now()->subDays(20),
            'revision_days' => 14,
        ]);

        $this->assertTrue($project->revisionWindowHasEnded());

        $this->actingAs($user)->post(route('customer.projects.revision', $project), [
            'body' => 'Too late?',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, $project->fresh()->revisions_used);
    }

    public function test_admin_reopening_revisions_allows_requests_again(): void
    {
        $user = $this->createUser(RoleName::Customer);
        $project = $this->project($user, [
            'status' => WebsiteProjectStatus::Delivered->value,
            'delivered_at' => Carbon::now()->subDays(20),
            'revision_days' => 14,
            'revisions_reopened_until' => Carbon::now()->addDays(5),
        ]);

        $this->assertTrue($project->canRequestRevision());
        $this->actingAs($user)->post(route('customer.projects.revision', $project), [
            'body' => 'Reopened change',
        ])->assertSessionHas('success');
    }

    public function test_customer_can_request_a_meeting_and_gets_an_ics_invite_on_confirm(): void
    {
        $user = $this->createUser(RoleName::Customer);
        $project = $this->project($user);

        $this->actingAs($user)->post(route('customer.projects.meeting', $project), [
            'proposed_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'duration_minutes' => 30,
            'topic' => 'Design walkthrough',
        ])->assertRedirect()->assertSessionHas('success');

        $meeting = $project->meetings()->firstOrFail();
        $this->assertSame('requested', $meeting->status);
        $this->assertDatabaseHas('notification_logs', ['user_id' => $user->id, 'type' => 'project_meeting']);

        // The .ics invite renders without error once confirmed.
        $meeting->update(['status' => 'confirmed', 'scheduled_at' => now()->addDays(2)]);
        $ics = \App\Support\IcsBuilder::event('uid@x', 'Meeting', $meeting->effectiveTime(), 30, 'notes', 'https://meet');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('SUMMARY:Meeting', $ics);
    }

    public function test_workspace_actions_are_owner_only(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $other = $this->createUser(RoleName::Customer);
        $project = $this->project($owner);

        $this->actingAs($other)->post(route('customer.projects.messages.store', $project), ['body' => 'x'])->assertNotFound();
        $this->actingAs($other)->post(route('customer.projects.meeting', $project), [
            'proposed_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ])->assertNotFound();
    }
}
