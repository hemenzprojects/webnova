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
                    'surfaceStrong' => '#E5E7EB',
                    'text' => '#1F2937',
                    'textMuted' => '#6B7280',
                    'headerBg' => '#FFFFFF',
                    'footerBg' => '#0A1E3E',
                    'footerText' => '#FFFFFF',
                ],
                'typography' => [
                    'headingFont' => 'Inter, system-ui, sans-serif',
                    'bodyFont' => 'Inter, system-ui, sans-serif',
                    'headingWeight' => '700',
                    'navTransform' => 'none',
                    'navLetterSpacing' => 'normal',
                    'fontsUrl' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
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
            'description' => 'Purple and lavender education theme with pill labels, rounded cards, testimonials and an FAQ.',
            'preview' => '/themes/edubright-preview.png',
            'tokens' => [
                'colors' => [
                    'primary' => '#6C3CE0',
                    'primaryLight' => '#9B74EE',
                    'primaryDark' => '#4F27B3',
                    'accent' => '#6C3CE0',
                    'accentLight' => '#9B74EE',
                    'accentDark' => '#4F27B3',
                    'surface' => '#FFFFFF',
                    'surfaceMuted' => '#F4F0FE',
                    'surfaceStrong' => '#E8DEFB',
                    'text' => '#1E1B4B',
                    'textMuted' => '#4B4870',
                    'headerBg' => '#F7F4FE',
                    'footerBg' => '#211D52',
                    'footerText' => '#FFFFFF',
                ],
                'typography' => [
                    'headingFont' => '"Montserrat", system-ui, sans-serif',
                    'bodyFont' => '"Lato", system-ui, sans-serif',
                    'headingWeight' => '700',
                    'navTransform' => 'uppercase',
                    'navLetterSpacing' => '0.08em',
                    'fontsUrl' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Lato:wght@400;700&display=swap',
                ],
                'radius' => [
                    'card' => '12px',
                    'button' => '8px',
                    'pill' => '9999px',
                ],
            ],
        ],
    ],
];