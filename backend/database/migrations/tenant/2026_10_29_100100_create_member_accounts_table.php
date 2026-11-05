<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership plugin, "Member accounts" feature: members' logins for the
 * website's member portal. Separate from `users`, which are admin logins.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_accounts', function (Blueprint $table) {
            $table->id();
            // e.g. APBA-2026-0042, issued when the account is created
            $table->string('member_number')->nullable()->unique();
            $table->string('name');
            $table->string('email')->unique();
            // Null until the member sets one from the welcome email
            $table->string('password')->nullable();
            $table->foreignId('membership_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('membership_registration_id')->nullable()->constrained()->nullOnDelete();
            // active, suspended
            $table->string('status')->default('active')->index();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('member_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // Who can see a page: public, or members (signed-in active members and admins)
        Schema::table('pages', function (Blueprint $table) {
            $table->string('visibility')->default('public')->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
        Schema::dropIfExists('member_password_reset_tokens');
        Schema::dropIfExists('member_accounts');
    }
};
