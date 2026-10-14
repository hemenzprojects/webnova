<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Shown on course-style cards (e.g. the Edubright theme): "Business", "Free"
            $table->string('category')->nullable()->after('icon');
            $table->string('price_label')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['category', 'price_label']);
        });
    }
};
