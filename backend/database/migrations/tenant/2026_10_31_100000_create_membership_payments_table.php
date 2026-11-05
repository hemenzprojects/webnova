<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership plugin: every membership payment, with its receipt number and
 * the membership year it pays for (a payment any day in a year covers that
 * calendar year). Members see them as their payment history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->foreignId('member_account_id')->nullable()->constrained('member_accounts')->nullOnDelete();
            $table->foreignId('membership_registration_id')->nullable()->constrained('membership_registrations')->nullOnDelete();
            $table->foreignId('membership_type_id')->nullable()->constrained('membership_types')->nullOnDelete();
            $table->string('purpose')->default('joining');
            $table->unsignedSmallInteger('year');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('method')->default('paystack');
            $table->string('reference')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('paid_at');
            $table->timestamp('emailed_at')->nullable();
            $table->timestamps();

            $table->index(['member_account_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_payments');
    }
};
