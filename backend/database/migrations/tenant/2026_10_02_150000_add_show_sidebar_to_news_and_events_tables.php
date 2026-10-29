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
            $table->boolean('show_sidebar')->default(true)->after('is_featured');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->boolean('show_sidebar')->default(true)->after('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn('show_sidebar');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('show_sidebar');
        });
    }
};
