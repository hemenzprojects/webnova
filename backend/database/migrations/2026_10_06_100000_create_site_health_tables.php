<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per site per day
        Schema::create('site_stats', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->date('date');
            $table->unsignedBigInteger('requests')->default(0);
            $table->unsignedBigInteger('total_ms')->default(0);
            $table->unsignedBigInteger('slow_requests')->default(0);
            $table->unsignedBigInteger('errors')->default(0);
            $table->unique(['tenant_id', 'date']);
        });

        // Visits to the public site: one row per site, day and page
        Schema::create('site_page_views', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->date('date');
            $table->string('path');
            $table->unsignedBigInteger('views')->default(0);
            $table->unique(['tenant_id', 'date', 'path']);
        });

        Schema::create('site_slow_queries', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            // Without its bindings, so no site content is copied here
            $table->text('sql');
            $table->unsignedInteger('time_ms');
            $table->string('url', 500)->nullable();
            $table->timestamp('created_at');
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_slow_queries');
        Schema::dropIfExists('site_page_views');
        Schema::dropIfExists('site_stats');
    }
};
