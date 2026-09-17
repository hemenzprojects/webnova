<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        News::create([
            'title' => 'WEBNOVA Expands High-Speed Internet Access to 15 New Universities',
            'slug' => 'webnova-expands-high-speed-internet',
            'content' => 'WEBNOVA is proud to announce the expansion of its high-speed internet infrastructure to 15 additional universities across Ghana. This expansion will provide students and faculty with faster, more reliable connectivity for research and learning.',
            'excerpt' => 'WEBNOVA expands connectivity to 15 new universities, enhancing research and learning capabilities.',
            'published_at' => now(),
            'is_published' => true,
        ]);

        News::create([
            'title' => 'New Eduroam Deployment Training Workshop Scheduled',
            'slug' => 'eduroam-training-workshop',
            'content' => 'Join us for a comprehensive Eduroam deployment training workshop on March 15th. IT administrators from member institutions will learn best practices for implementing and managing Eduroam services.',
            'excerpt' => 'Upcoming training workshop for Eduroam deployment and management.',
            'published_at' => now()->subDays(5),
            'is_published' => true,
        ]);

        News::create([
            'title' => 'WEBNOVA Hosts Annual Conference 2026',
            'slug' => 'webnova-annual-conference-2026',
            'content' => 'The WEBNOVA Annual Conference 2026 will bring together education technology leaders, researchers, and innovators to discuss the future of academic networking in Ghana. Register now for early bird pricing.',
            'excerpt' => 'Annual conference featuring education technology leaders and researchers.',
            'published_at' => now()->subDays(10),
            'is_published' => true,
        ]);
    }
}
