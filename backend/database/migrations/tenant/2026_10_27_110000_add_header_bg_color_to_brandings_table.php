<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Header background colour, set under Branding. Empty means the header
 * uses the active theme's colour (headerBg).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $table->string('header_bg_color')->nullable()->after('background_color');
        });
    }

    public function down(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $table->dropColumn('header_bg_color');
        });
    }
};
