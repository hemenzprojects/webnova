<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Donations plugin (app/Plugins/Donations). Created for every tenant;
 * only used while the plugin is active.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->text('comments')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->index();
            // online (Paystack), or recorded by an admin: cash, bank_transfer, mobile_money, cheque, other
            $table->string('method')->default('online');
            // unpaid, paid, failed
            $table->string('payment_status')->default('unpaid')->index();
            $table->string('payment_reference')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
