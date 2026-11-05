<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership plugin, Renewals feature: yearly renewal payments, the notices
 * sent to members who have not paid, members' standing, and the seats a
 * corporate membership includes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // e.g. Corporate: 2 Ordinary seats, paid for by the corporate member's fee
        Schema::table('membership_types', function (Blueprint $table) {
            $table->unsignedTinyInteger('included_seats')->default(0)->after('price');
            $table->foreignId('seat_type_id')->nullable()->after('included_seats')->constrained('membership_types')->nullOnDelete();
        });

        Schema::table('member_accounts', function (Blueprint $table) {
            // A seat of a corporate member: covered by that member's payment
            $table->foreignId('sponsor_account_id')->nullable()->after('membership_type_id')->constrained('member_accounts')->nullOnDelete();
            // paid / due / overdue / suspended / forfeited / exempt, refreshed daily and on payment (for lists and filters)
            $table->string('standing')->nullable()->after('status');
        });

        // A renewal payment started from the member portal
        Schema::create('membership_renewals', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('member_account_id')->constrained('member_accounts')->cascadeOnDelete();
            $table->foreignId('membership_type_id')->nullable()->constrained('membership_types')->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_reference')->nullable();
            $table->string('payment_provider')->nullable();
            $table->string('gateway_order_id')->nullable();
            $table->json('gateway_orders')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::table('membership_payments', function (Blueprint $table) {
            $table->foreignId('membership_renewal_id')->nullable()->after('membership_registration_id')->constrained('membership_renewals')->nullOnDelete();
            $table->text('notes')->nullable()->after('email');
        });

        // Every renewal notice emailed to a member, once per member, year and kind: the communication trail
        Schema::create('membership_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_account_id')->constrained('member_accounts')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('kind');
            $table->string('email')->nullable();
            $table->boolean('sent')->default(true);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['member_account_id', 'year', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_notices');
        Schema::table('membership_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('membership_renewal_id');
            $table->dropColumn('notes');
        });
        Schema::dropIfExists('membership_renewals');
        Schema::table('member_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsor_account_id');
            $table->dropColumn('standing');
        });
        Schema::table('membership_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seat_type_id');
            $table->dropColumn('included_seats');
        });
    }
};
