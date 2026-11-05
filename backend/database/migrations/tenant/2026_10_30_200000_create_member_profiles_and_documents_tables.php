<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership plugin, member portal (phase 3): profiles members keep up to
 * date, their documents, files shared with all members, and consent records.
 * Files live on the private disk under {tenant_id}/membership/.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_account_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();          // Dr, Prof, Mr, Ms…
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('institution')->nullable();
            $table->string('institution_type')->nullable(); // MemberProfile::INSTITUTION_TYPES
            $table->string('position')->nullable();
            $table->json('crops')->nullable();             // from the site's list (Membership settings)
            $table->json('expertise')->nullable();
            $table->text('bio')->nullable();
            $table->string('orcid')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });

        Schema::create('member_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_account_id')->constrained()->cascadeOnDelete();
            $table->string('category');                   // MemberDocument::CATEGORIES
            $table->string('title');
            $table->string('file');
            $table->string('name');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('mime')->nullable();
            // member, application (copied from their application), admin
            $table->string('source')->default('member');
            $table->foreignId('membership_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Files the Association shares with members (minutes, reports…): private, active members only
        Schema::create('member_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('file');
            $table->string('name');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('mime')->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Agreement to the site's privacy notice (Membership settings), with the version agreed to
        Schema::table('member_accounts', function (Blueprint $table) {
            $table->timestamp('consented_at')->nullable()->after('invited_at');
            $table->unsignedInteger('consent_version')->nullable()->after('consented_at');
        });
        Schema::table('membership_registrations', function (Blueprint $table) {
            $table->timestamp('consented_at')->nullable()->after('decision_reason');
            $table->unsignedInteger('consent_version')->nullable()->after('consented_at');
        });
    }

    public function down(): void
    {
        Schema::table('membership_registrations', fn (Blueprint $table) => $table->dropColumn(['consented_at', 'consent_version']));
        Schema::table('member_accounts', fn (Blueprint $table) => $table->dropColumn(['consented_at', 'consent_version']));
        Schema::dropIfExists('member_resources');
        Schema::dropIfExists('member_documents');
        Schema::dropIfExists('member_profiles');
    }
};
