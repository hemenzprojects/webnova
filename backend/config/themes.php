<?php

/**
 * Theme registry. Each entry describes a theme available to tenants.
 * Frontend token JSON lives in frontend/themes/{slug}/tokens.json — kept
 * here as a mirror so the backend can serve it without a filesystem read
 * across the Nuxt/Laravel boundary.
 */

return [
    'themes' => [
        'default' => [
            'name' => 'WebNova Default',
            'description' => 'Clean navy + cyan look — the original WebNova theme.',
            'preview' => '/themes/default-preview.png',
            'tokens' => [
                'colors' => [
                    'primary' => '#0A1E3E',
                    'primaryLight' => '#1A3A5C',
                    'primaryDark' => '#050F1F',
                    'accent' => '#00D9FF',
                    'accentLight' => '#33E3FF',
                    'accentDark' => '#00A8CC',
                    'surface' => '#FFFFFF',
                    'surfaceMuted' => '#F3F4F6',
                    'text' => '#1F2937',
                    'textMuted' => '#6B7280',
                    'footerBg' => '#0A1E3E',
                    'footerText' => '#FFFFFF',
                ],
                'typography' => [
                    'headingFont' => 'Inter, system-ui, sans-serif',
                    'bodyFont' => 'Inter, system-ui, sans-serif',
                    'headingWeight' => '700',
                ],
                'radius' => [
                    'card' => '12px',
                    'button' => '8px',
                    'pill' => '9999px',
                ],
            ],
        ],

        'edubright' => [
            'name' => 'Edubright',
            'description' => 'Purple + lavender education theme with pill labels and rounded cards.',
            'preview' => '/themes/edubright-preview.png',
            'tokens' => [
                'colors' => [
                    'primary' => '#7B3FE4',
                    'primaryLight' => '#9A66EE',
                    'primaryDark' => '#5A2BB0',
                    'accent' => '#7B3FE4',
                    'accentLight' => '#B08AF0',
                    'accentDark' => '#4A1F94',
                    'surface' => '#FFFFFF',
                    'surfaceMuted' => '#F1EBFF',
                    'text' => '#1A1A2E',
                    'textMuted' => '#5C5C7A',
                    'footerBg' => '#1F1B4B',
                    'footerText' => '#FFFFFF',
                ],
                'typography' => [
                    'headingFont' => '"Poppins", system-ui, sans-serif',
                    'bodyFont' => '"Poppins", system-ui, sans-serif',
                    'headingWeight' => '700',
                ],
                'radius' => [
                    'card' => '16px',
                    'button' => '10px',
                    'pill' => '9999px',
                ],
            ],
        ],
    ],
];