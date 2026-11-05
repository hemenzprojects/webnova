<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Footer background and text colours, set under Branding. Empty means the
 * footer uses the active theme's colours (footerBg / footerText).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $table->string('footer_bg_color')->nullable()->after('background_color');
            $table->string('footer_text_color')->nullable()->after('footer_bg_color');
        });
    }

    public function down(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $table->dropColumn(['footer_bg_color', 'footer_text_color']);
        });
    }
};
