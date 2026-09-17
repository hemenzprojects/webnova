<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create header menu
        $headerMenu = \App\Models\Menu::create([
            'name' => 'Main Navigation',
            'location' => 'header',
            'description' => 'Primary navigation menu displayed in the header',
            'is_active' => true,
        ]);

        // Create menu items
        $menuItems = [
            ['label' => 'Home', 'url' => '/', 'type' => 'custom', 'order' => 1],
            ['label' => 'About', 'url' => '/about', 'type' => 'custom', 'order' => 2],
            ['label' => 'Services', 'url' => '/services', 'type' => 'services', 'order' => 3],
            ['label' => 'News', 'url' => '/news', 'type' => 'news', 'order' => 4],
            ['label' => 'Members', 'url' => '/members', 'type' => 'members', 'order' => 5],
            ['label' => 'Contact', 'url' => '/contact', 'type' => 'custom', 'order' => 6],
        ];

        foreach ($menuItems as $item) {
            \App\Models\MenuItem::create([
                'menu_id' => $headerMenu->id,
                'label' => $item['label'],
                'url' => $item['url'],
                'type' => $item['type'],
                'order' => $item['order'],
                'is_published' => true,
                'open_in_new_tab' => false,
            ]);
        }
    }
}
