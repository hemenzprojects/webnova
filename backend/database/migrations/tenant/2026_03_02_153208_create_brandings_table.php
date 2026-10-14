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
        Schema::create('brandings', function (Blueprint $table) {
            $table->id();

            // Site Information
            $table->string('site_name')->default('WEBNOVA');
            $table->string('site_tagline')->nullable();
            $table->text('site_description')->nullable();

            // Logos
            $table->string('logo')->nullable();
            $table->string('logo_light')->nullable(); // For dark backgrounds
            $table->string('favicon')->nullable();

            // Primary Brand Colors
            $table->string('primary_color')->default('#0A1E3E'); // Deep navy blue
            $table->string('primary_light')->default('#1A3A5C');
            $table->string('primary_dark')->default('#050F1F');

            // Accent Colors
            $table->string('accent_color')->default('#00D9FF'); // Bright cyan
            $table->string('accent_light')->default('#33E3FF');
            $table->string('accent_dark')->default('#00A8CC');

            // Additional Colors
            $table->string('text_color')->default('#1F2937');
            $table->string('background_color')->default('#FFFFFF');

            // Contact Information
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('contact_address')->nullable();

            // Social Media
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();

            // SEO
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brandings');
    }
};
