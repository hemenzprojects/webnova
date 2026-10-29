<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership plugin: every payment attempt's order id at the provider,
 * {attempt reference: order id}, so an earlier attempt that was paid after
 * all (another tab, a late bank answer) is still found.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_registrations', function (Blueprint $table) {
            $table->json('gateway_orders')->nullable()->after('gateway_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('membership_registrations', function (Blueprint $table) {
            $table->dropColumn('gateway_orders');
        });
    }
};
