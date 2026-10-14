<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $table->json('social_links')->nullable()->after('youtube_url');
        });

        // Carry the existing per-platform URLs over into the new list
        $platforms = [
            'facebook_url' => 'facebook',
            'twitter_url' => 'x',
            'linkedin_url' => 'linkedin',
            'instagram_url' => 'instagram',
            'youtube_url' => 'youtube',
        ];

        foreach (DB::table('brandings')->get() as $branding) {
            $links = [];

            foreach ($platforms as $column => $platform) {
                if (! empty($branding->{$column})) {
                    $links[] = ['platform' => $platform, 'url' => $branding->{$column}];
                }
            }

            DB::table('brandings')->where('id', $branding->id)->update(['social_links' => json_encode($links)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brandings', function (Blueprint $table) {
            $table->dropColumn('social_links');
        });
    }
};
