<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin roles per site (System Administration → Roles). Existing users become
 * Administrators so nobody loses access.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('password')->constrained()->nullOnDelete();
        });

        $now = now();
        $adminId = DB::table('roles')->insertGetId([
            'name' => 'Administrator',
            'description' => 'Full access to everything, including users and roles.',
            'is_admin' => true,
            'permissions' => json_encode([]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Starting examples; each site can change or delete them
        $content = ['pages', 'news', 'events', 'services', 'members', 'team-members', 'media'];
        DB::table('roles')->insert([
            [
                'name' => 'Content Editor',
                'description' => 'Writes and publishes content. Can look at, but not change, the site\'s appearance.',
                'is_admin' => false,
                'permissions' => json_encode(array_fill_keys($content, 'manage') + array_fill_keys(
                    ['theme-picker', 'brandings', 'menus', 'header-settings', 'sidebar-settings', 'footer-settings'], 'view')),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Viewer',
                'description' => 'Can look at content but not change anything.',
                'is_admin' => false,
                'permissions' => json_encode(array_fill_keys($content, 'view')),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('users')->whereNull('role_id')->update(['role_id' => $adminId]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
        Schema::dropIfExists('roles');
    }
};
