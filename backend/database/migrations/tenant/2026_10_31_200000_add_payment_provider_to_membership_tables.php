<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership plugin: which online payment provider an application was sent
 * to (Paystack or Stanbic's N-Genius), and the provider's own order id, which
 * N-Genius needs to look the payment up.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_registrations', function (Blueprint $table) {
            $table->string('payment_provider')->nullable()->after('payment_reference');
            $table->string('gateway_order_id')->nullable()->after('payment_provider');
        });
        Schema::table('membership_payments', function (Blueprint $table) {
            $table->string('gateway_reference')->nullable()->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('membership_registrations', function (Blueprint $table) {
            $table->dropColumn(['payment_provider', 'gateway_order_id']);
        });
        Schema::table('membership_payments', function (Blueprint $table) {
            $table->dropColumn('gateway_reference');
        });
    }
};
