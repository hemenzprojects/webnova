<?php

namespace App\Plugins\Donations;

use App\Models\Page;
use App\Plugins\Plugin;

class DonationsPlugin extends Plugin
{
    public function key(): string
    {
        return 'donations';
    }

    public function name(): string
    {
        return 'Donations';
    }

    public function description(): string
    {
        return 'Accept donations online with a donation form and Paystack payments, record cash and bank gifts, and follow every donation and donor from a dashboard.';
    }

    public function icon(): string
    {
        return 'heroicon-o-gift';
    }

    public function area(): ?array
    {
        return ['label' => 'Donations', 'icon' => 'heroicon-o-gift'];
    }

    public function blocks(): array
    {
        return ['donation_form'];
    }

    /**
     * First activation: a page with the form, so the site has something to link to straight away.
     */
    public function activated(): void
    {
        if (Page::where('slug', 'donate')->exists()) {
            return;
        }

        Page::create([
            'title' => 'Make a Donation',
            'slug' => 'donate',
            'is_published' => true,
            'template_type' => 'builder',
            'blocks' => [
                ['id' => 'section-donations-0', 'type' => '_section_meta', 'order' => 0,
                    'data' => ['columns' => [['id' => 'column-donations-0', 'width' => 100]], 'settings' => ['layout' => 'full', 'gap' => 'default', 'height' => 'default']]],
                ['id' => 'widget-donations-0', 'type' => 'donation_form', 'order' => 1,
                    '_columnId' => 'column-donations-0', '_sectionId' => 'section-donations-0',
                    'data' => ['heading' => 'Make a Donation', 'text' => 'Provide your personal details in the fields provided. All fields marked ( * ) are required.']],
            ],
        ]);
    }
}
