<?php

namespace App\Plugins\Membership;

use App\Plugins\Membership\Models\MembershipForm;
use App\Plugins\Membership\Models\MembershipType;
use App\Plugins\Plugin;

class MembershipPlugin extends Plugin
{
    public function key(): string
    {
        return 'membership';
    }

    public function name(): string
    {
        return 'Membership';
    }

    public function description(): string
    {
        return 'Online membership registration with your own form, membership types and fees, Paystack payments, and a dashboard of sign-ups.';
    }

    public function icon(): string
    {
        return 'heroicon-o-identification';
    }

    public function area(): ?array
    {
        return ['label' => 'Membership', 'icon' => 'heroicon-o-identification'];
    }

    public function blocks(): array
    {
        return ['membership_form'];
    }

    /**
     * First activation: a ready-to-use form and two example membership types.
     */
    public function activated(): void
    {
        MembershipForm::current();

        // A page with the form, so the site has something to link to straight away
        if (! \App\Models\Page::where('slug', 'membership')->exists()) {
            \App\Models\Page::create([
                'title' => 'Become a Member',
                'slug' => 'membership',
                'is_published' => true,
                'template_type' => 'builder',
                'blocks' => [
                    ['id' => 'section-membership-0', 'type' => '_section_meta', 'order' => 0,
                        'data' => ['columns' => [['id' => 'column-membership-0', 'width' => 100]], 'settings' => ['layout' => 'full', 'gap' => 'default', 'height' => 'default']]],
                    ['id' => 'widget-membership-0', 'type' => 'membership_form', 'order' => 1,
                        '_columnId' => 'column-membership-0', '_sectionId' => 'section-membership-0',
                        'data' => ['heading' => 'Become a Member', 'text' => 'Fill in the form below to join. Fields marked * are required.']],
                ],
            ]);
        }

        if (! MembershipType::exists()) {
            MembershipType::create(['name' => 'Student', 'slug' => 'student', 'price' => 50, 'period' => 'year', 'order' => 1,
                'description' => 'For students enrolled in a recognised institution.']);
            MembershipType::create(['name' => 'Professional', 'slug' => 'professional', 'price' => 100, 'period' => 'year', 'order' => 2,
                'description' => 'For working professionals.']);
        }
    }
}
