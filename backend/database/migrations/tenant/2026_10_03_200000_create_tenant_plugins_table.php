<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_plugins', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->boolean('is_active')->default(false);
            // Encrypted: holds secrets such as payment gateway keys
            $table->text('settings')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plugins');
    }
};
