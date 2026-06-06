<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hosting_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_developer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('project_number')->unique();
            $table->string('status')->index(); // WebsiteProjectStatus
            $table->string('business_name')->nullable();
            $table->text('business_description')->nullable();
            $table->string('industry')->nullable();
            $table->json('pages_required')->nullable();
            $table->string('brand_colours')->nullable();
            $table->json('reference_websites')->nullable();
            $table->text('special_requirements')->nullable();
            $table->text('internal_notes')->nullable();
            $table->boolean('content_received')->default(false);
            $table->boolean('logo_received')->default(false);
            $table->date('target_launch_date')->nullable();
            $table->timestamp('launched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('website_project_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_type'); // logo | image | content_document | brand_guideline | other
            $table->string('original_filename');
            $table->string('stored_path'); // private disk path, served via auth route only
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_project_assets');
        Schema::dropIfExists('website_projects');
    }
};
