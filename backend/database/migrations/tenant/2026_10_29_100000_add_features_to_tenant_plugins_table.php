<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The parts of a plugin its site has switched on (Plugin::features()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_plugins', function (Blueprint $table) {
            $table->json('features')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_plugins', function (Blueprint $table) {
            $table->dropColumn('features');
        });
    }
};
