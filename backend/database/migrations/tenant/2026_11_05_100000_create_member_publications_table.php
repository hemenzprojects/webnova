<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership plugin, Member updates feature: publications, milestones and
 * news that members submit in the portal, reviewed by the editorial team
 * before they appear on the website. Only a cover image and a link are
 * kept: the publication itself stays wherever it is hosted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_account_id')->nullable()->constrained('member_accounts')->nullOnDelete();
            $table->string('kind')->default('publication');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('link', 500)->nullable();
            $table->string('image')->nullable();
            // pending (waiting for review) / approved (on the website) / rejected (not published)
            $table->string('status')->default('pending');
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            // Shown with the item when the member's account is gone
            $table->string('author_name')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_publications');
    }
};
