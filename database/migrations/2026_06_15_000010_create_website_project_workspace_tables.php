<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Conversation between the customer and the assigned developer/admin.
        Schema::create('website_project_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Staff messages are flagged so the UI can style them and so an
            // internal note (admin-only) is never shown to the customer.
            $table->boolean('is_from_staff')->default(false);
            $table->boolean('is_internal_note')->default(false)->index();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('website_project_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_project_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('website_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path'); // private disk, served via authed route only
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();
        });

        // Meeting requests between customer and developer.
        Schema::create('website_project_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('requested')->index(); // requested|confirmed|rescheduled|cancelled|completed
            $table->string('topic')->nullable();
            $table->timestamp('proposed_at');          // time the requester asked for
            $table->timestamp('scheduled_at')->nullable(); // confirmed time (admin)
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->string('meeting_url')->nullable();  // Google Meet / Zoom link
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Project delivery / revision tracking.
        Schema::table('website_projects', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('launched_at');
            $table->unsignedSmallInteger('revision_days')->default(14)->after('delivered_at');
            $table->unsignedSmallInteger('revisions_used')->default(0)->after('revision_days');
            // When set (by an admin re-opening), revisions are allowed until this
            // time even if the standard window has elapsed.
            $table->timestamp('revisions_reopened_until')->nullable()->after('revisions_used');
        });
    }

    public function down(): void
    {
        Schema::table('website_projects', function (Blueprint $table) {
            $table->dropColumn(['delivered_at', 'revision_days', 'revisions_used', 'revisions_reopened_until']);
        });
        Schema::dropIfExists('website_project_meetings');
        Schema::dropIfExists('website_project_message_attachments');
        Schema::dropIfExists('website_project_messages');
    }
};
