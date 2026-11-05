<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership plugin (app/Plugins/Membership). Created for every tenant;
 * only used while the plugin is active.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            // year, month, once
            $table->string('period')->default('year');
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // One registration form per site. Editing it bumps the version; each
        // registration keeps a copy of the form it was submitted with.
        Schema::create('membership_forms', function (Blueprint $table) {
            $table->id();
            $table->json('schema');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('membership_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('membership_type_id')->nullable()->constrained()->nullOnDelete();
            // Copied from the answers so the list can be searched and sorted
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->json('answers');
            $table->json('form_snapshot');
            $table->unsignedInteger('form_version');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3);
            // pending, approved, rejected
            $table->string('status')->default('pending')->index();
            // unpaid, paid, failed, not_required
            $table->string('payment_status')->default('unpaid')->index();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_registrations');
        Schema::dropIfExists('membership_forms');
        Schema::dropIfExists('membership_types');
    }
};
