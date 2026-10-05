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
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->onDelete('cascade');
            $table->string('label');
            $table->string('icon')->nullable();
            $table->enum('type', ['page', 'custom', 'category', 'news', 'events', 'services', 'members']);
            $table->string('url')->nullable();
            $table->foreignId('page_id')->nullable()->constrained('pages')->onDelete('cascade');
            $table->boolean('open_in_new_tab')->default(false);
            $table->string('css_class')->nullable();
            $table->string('target_id')->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            // Composite index for performance
            $table->index(['menu_id', 'parent_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
