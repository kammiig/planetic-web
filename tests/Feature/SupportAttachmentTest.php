<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_customer_can_open_a_ticket_with_attachments(): void
    {
        $user = $this->createUser(RoleName::Customer);

        $response = $this->actingAs($user)->post(route('customer.support.store'), [
            'subject' => 'My site shows an error',
            'category' => 'hosting',
            'message' => 'Screenshot attached.',
            'attachments' => [
                UploadedFile::fake()->image('screenshot.png'),
                UploadedFile::fake()->create('notes.pdf', 200, 'application/pdf'),
            ],
        ]);

        $ticket = SupportTicket::firstOrFail();
        $response->assertRedirect(route('customer.support.show', $ticket));

        $this->assertSame(2, $ticket->attachments()->count());

        $attachment = $ticket->attachments()->firstOrFail();
        $this->assertSame('screenshot.png', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->path);

        // Attachments are listed on the ticket and downloadable by the owner.
        $this->actingAs($user)->get(route('customer.support.show', $ticket))
            ->assertOk()
            ->assertSee('screenshot.png')
            ->assertSee('notes.pdf');

        $this->actingAs($user)
            ->get(route('customer.support.attachments.download', [$ticket, $attachment]))
            ->assertOk()
            ->assertDownload('screenshot.png');
    }

    public function test_replies_can_carry_attachments_too(): void
    {
        $user = $this->createUser(RoleName::Customer);

        $this->actingAs($user)->post(route('customer.support.store'), [
            'subject' => 'Question',
            'message' => 'First message',
        ]);

        $ticket = SupportTicket::firstOrFail();

        $this->actingAs($user)->post(route('customer.support.reply', $ticket), [
            'message' => 'Here is the file you asked for.',
            'attachments' => [UploadedFile::fake()->create('logs.zip', 500, 'application/zip')],
        ])->assertRedirect();

        $this->assertSame(1, $ticket->attachments()->count());
        $this->assertSame('logs.zip', $ticket->attachments()->first()->original_name);
    }

    public function test_dangerous_and_oversized_files_are_rejected(): void
    {
        $user = $this->createUser(RoleName::Customer);

        // Executable / script uploads are refused outright.
        $this->actingAs($user)->from(route('customer.support.index'))->post(route('customer.support.store'), [
            'subject' => 'Sneaky',
            'message' => 'Trying a script',
            'attachments' => [UploadedFile::fake()->create('hack.php', 10, 'application/x-php')],
        ])->assertSessionHasErrors('attachments.0');

        // Oversized uploads are refused (limit 10 MB).
        $this->actingAs($user)->from(route('customer.support.index'))->post(route('customer.support.store'), [
            'subject' => 'Huge',
            'message' => 'Too big',
            'attachments' => [UploadedFile::fake()->create('big.pdf', 11_000, 'application/pdf')],
        ])->assertSessionHasErrors('attachments.0');

        $this->assertSame(0, SupportTicket::count());
        $this->assertSame(0, SupportTicketAttachment::count());
    }

    public function test_attachments_are_private_to_the_ticket_owner(): void
    {
        $owner = $this->createUser(RoleName::Customer);
        $other = $this->createUser(RoleName::Customer);

        $this->actingAs($owner)->post(route('customer.support.store'), [
            'subject' => 'Private',
            'message' => 'Mine only',
            'attachments' => [UploadedFile::fake()->image('private.png')],
        ]);

        $ticket = SupportTicket::firstOrFail();
        $attachment = $ticket->attachments()->firstOrFail();

        // Another customer gets a 404 (existence is never revealed).
        $this->actingAs($other)
            ->get(route('customer.support.attachments.download', [$ticket, $attachment]))
            ->assertNotFound();
    }
}
