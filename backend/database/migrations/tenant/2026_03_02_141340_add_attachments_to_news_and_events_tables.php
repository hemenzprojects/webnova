<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('featured_image');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('featured_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
