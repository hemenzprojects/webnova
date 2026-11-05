<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Membership plugin: the reason given to the applicant with a rejection
 * (sent in the rejection email; admin_notes stay internal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_registrations', function (Blueprint $table) {
            $table->text('decision_reason')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('membership_registrations', function (Blueprint $table) {
            $table->dropColumn('decision_reason');
        });
    }
};
