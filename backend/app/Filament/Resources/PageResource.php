<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Page Content')
                    ->tabs([
                        // Basic Information Tab
                        Forms\Components\Tabs\Tab::make('Basic Info')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                                Forms\Components\TextInput::make('slug')
                                    ->required(),
                                Forms\Components\Textarea::make('excerpt')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\FileUpload::make('featured_image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('pages/featured')
                                    ->imageEditor()
                                    ->maxSize(5120),
                                Forms\Components\Toggle::make('is_published')
                                    ->required()
                                    ->default(false),
                                Forms\Components\TextInput::make('order')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        // Legacy Content Tab
                        Forms\Components\Tabs\Tab::make('Legacy Content')
                            ->schema([
                                Forms\Components\RichEditor::make('content')
                                    ->columnSpanFull()
                                    ->required(fn (Get $get) => $get('template_type') === 'content')
                                    ->helperText('Use this for simple text-based pages (required for legacy content)')
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'link',
                                        'bulletList',
                                        'orderedList',
                                        'h2',
                                        'h3',
                                    ]),
                            ])
                            ->visible(fn (Get $get) => $get('template_type') === 'content'),

                        // Page Builder Tab
                        Forms\Components\Tabs\Tab::make('Page Builder')
                            ->schema([
                                Forms\Components\Select::make('template_type')
                                    ->label('Template Type')
                                    ->options([
                                        'content' => 'Legacy Content',
                                        'builder' => 'Page Builder',
                                    ])
                                    ->default('builder')
                                    ->required()
                                    ->live(),

                                Forms\Components\Builder::make('blocks')
                                    ->blocks([
                                        // Basic Blocks
                                        Block::make('text_block')
                                            ->label('Text Block')
                                            ->icon('heroicon-o-document-text')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Heading (Optional)'),
                                                Forms\Components\RichEditor::make('content')
                                                    ->required()
                                                    ->columnSpanFull()
                                                    ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList', 'h2', 'h3']),
                                                Forms\Components\Select::make('alignment')
                                                    ->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
                                                    ->default('left'),
                                                Forms\Components\Select::make('containerWidth')
                                                    ->label('Container Width')
                                                    ->options([
                                                        'container' => 'Default Container',
                                                        'narrow' => 'Narrow (Prose)',
                                                        'wide' => 'Wide',
                                                        'full' => 'Full Width',
                                                    ])
                                                    ->default('container'),
                                            ]),

                                        Block::make('heading')
                                            ->label('Heading')
                                            ->icon('heroicon-o-bars-3-bottom-left')
                                            ->schema([
                                                Forms\Components\TextInput::make('text')
                                                    ->label('Heading Text')
                                                    ->required(),
                                                Forms\Components\Select::make('level')
                                                    ->label('Heading Level')
                                                    ->options([
                                                        'h1' => 'H1 (Main Title)',
                                                        'h2' => 'H2 (Section)',
                                                        'h3' => 'H3 (Subsection)',
                                                        'h4' => 'H4 (Minor)',
                                                    ])
                                                    ->default('h2')
                                                    ->required(),
                                                Forms\Components\TextInput::make('subtitle')
                                                    ->label('Subtitle (Optional)'),
                                                Forms\Components\Select::make('alignment')
                                                    ->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
                                                    ->default('center'),
                                                Forms\Components\ColorPicker::make('color')
                                                    ->label('Text Color (Optional)'),
                                            ]),

                                        Block::make('image')
                                            ->label('Image')
                                            ->icon('heroicon-o-photo')
                                            ->schema([
                                                Forms\Components\FileUpload::make('image')
                                                    ->label('Image')
                                                    ->image()
                                                    ->required()
                                                    ->disk('public')
                                                    ->directory('pages/images')
                                                    ->imageEditor()
                                                    ->maxSize(5120),
                                                Forms\Components\TextInput::make('alt')
                                                    ->label('Alt Text')
                                                    ->required()
                                                    ->helperText('Describe the image for accessibility'),
                                                Forms\Components\TextInput::make('caption')
                                                    ->label('Caption (Optional)'),
                                                Forms\Components\Select::make('size')
                                                    ->options([
                                                        'small' => 'Small',
                                                        'medium' => 'Medium',
                                                        'large' => 'Large',
                                                        'full' => 'Full Width',
                                                    ])
                                                    ->default('medium'),
                                                Forms\Components\Select::make('alignment')
                                                    ->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
                                                    ->default('center'),
                                                Forms\Components\Toggle::make('rounded')
                                                    ->label('Rounded Corners')
                                                    ->default(true),
                                            ]),

                                        Block::make('button')
                                            ->label('Button')
                                            ->icon('heroicon-o-cursor-arrow-rays')
                                            ->schema([
                                                Forms\Components\TextInput::make('text')
                                                    ->label('Button Text')
                                                    ->required(),
                                                Forms\Components\TextInput::make('url')
                                                    ->label('Button URL')
                                                    ->required()
                                                    ->url(),
                                                Forms\Components\Select::make('style')
                                                    ->options([
                                                        'primary' => 'Primary (Blue)',
                                                        'secondary' => 'Secondary (Gray)',
                                                        'accent' => 'Accent (Cyan)',
                                                        'outline' => 'Outline',
                                                    ])
                                                    ->default('primary'),
                                                Forms\Components\Select::make('size')
                                                    ->options([
                                                        'sm' => 'Small',
                                                        'md' => 'Medium',
                                                        'lg' => 'Large',
                                                    ])
                                                    ->default('md'),
                                                Forms\Components\Select::make('alignment')
                                                    ->options(['left' => 'Left', 'center' => 'Center', 'right' => 'Right'])
                                                    ->default('center'),
                                                Forms\Components\Toggle::make('openInNewTab')
                                                    ->label('Open in New Tab')
                                                    ->default(false),
                                            ]),

                                        Block::make('spacer')
                                            ->label('Spacer')
                                            ->icon('heroicon-o-arrows-up-down')
                                            ->schema([
                                                Forms\Components\Select::make('height')
                                                    ->options([
                                                        'xs' => 'Extra Small (1rem)',
                                                        'sm' => 'Small (2rem)',
                                                        'md' => 'Medium (3rem)',
                                                        'lg' => 'Large (5rem)',
                                                        'xl' => 'Extra Large (8rem)',
                                                    ])
                                                    ->default('md'),
                                            ]),

                                        Block::make('divider')
                                            ->label('Divider')
                                            ->icon('heroicon-o-minus')
                                            ->schema([
                                                Forms\Components\Select::make('style')
                                                    ->options([
                                                        'solid' => 'Solid Line',
                                                        'dashed' => 'Dashed Line',
                                                        'dotted' => 'Dotted Line',
                                                        'gradient' => 'Gradient',
                                                    ])
                                                    ->default('solid'),
                                                Forms\Components\Select::make('width')
                                                    ->options([
                                                        'narrow' => 'Narrow',
                                                        'medium' => 'Medium',
                                                        'wide' => 'Wide',
                                                        'full' => 'Full Width',
                                                    ])
                                                    ->default('medium'),
                                            ]),

                                        Block::make('carousel')
                                            ->label('Image Carousel')
                                            ->icon('heroicon-o-photo')
                                            ->schema([
                                                Forms\Components\Repeater::make('slides')
                                                    ->label('Carousel Slides')
                                                    ->schema([
                                                        Forms\Components\FileUpload::make('image')
                                                            ->label('Slide Image')
                                                            ->image()
                                                            ->disk('public')
                                                            ->directory('pages/carousel')
                                                            ->imageEditor()
                                                            ->maxSize(5120)
                                                            ->required(),
                                                        Forms\Components\TextInput::make('title')
                                                            ->label('Slide Title (Optional)'),
                                                        Forms\Components\Textarea::make('caption')
                                                            ->label('Slide Caption (Optional)')
                                                            ->rows(2),
                                                        Forms\Components\TextInput::make('link')
                                                            ->label('Link (Optional)')
                                                            ->url(),
                                                        Forms\Components\Toggle::make('openInNewTab')
                                                            ->label('Open Link in New Tab')
                                                            ->default(false),
                                                    ])
                                                    ->minItems(2)
                                                    ->defaultItems(3)
                                                    ->columnSpanFull()
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Slide'),
                                                Forms\Components\Toggle::make('autoplay')
                                                    ->label('Auto-play Carousel')
                                                    ->default(true),
                                                Forms\Components\TextInput::make('interval')
                                                    ->label('Auto-play Interval (seconds)')
                                                    ->numeric()
                                                    ->default(5)
                                                    ->minValue(2)
                                                    ->maxValue(20)
                                                    ->visible(fn ($get) => $get('autoplay')),
                                                Forms\Components\Toggle::make('showIndicators')
                                                    ->label('Show Slide Indicators')
                                                    ->default(true),
                                                Forms\Components\Toggle::make('showArrows')
                                                    ->label('Show Navigation Arrows')
                                                    ->default(true),
                                                Forms\Components\Select::make('height')
                                                    ->label('Carousel Height')
                                                    ->options([
                                                        'small' => 'Small (400px)',
                                                        'medium' => 'Medium (500px)',
                                                        'large' => 'Large (600px)',
                                                        'xlarge' => 'Extra Large (700px)',
                                                    ])
                                                    ->default('medium'),
                                                Forms\Components\Select::make('transition')
                                                    ->label('Transition Effect')
                                                    ->options([
                                                        'slide' => 'Slide',
                                                        'fade' => 'Fade',
                                                    ])
                                                    ->default('slide'),
                                            ]),

                                        Block::make('hero_slider')
                                            ->label('Hero Slider / Banner')
                                            ->icon('heroicon-o-rectangle-stack')
                                            ->schema([
                                                Forms\Components\Repeater::make('slides')
                                                    ->label('Hero Slides')
                                                    ->schema([
                                                        Forms\Components\FileUpload::make('backgroundImage')
                                                            ->label('Background Image')
                                                            ->image()
                                                            ->disk('public')
                                                            ->directory('pages/hero-slider')
                                                            ->imageEditor()
                                                            ->maxSize(5120)
                                                            ->required(),
                                                        Forms\Components\Select::make('overlay')
                                                            ->label('Background Overlay')
                                                            ->options([
                                                                'none' => 'No Overlay',
                                                                'dark' => 'Dark Overlay (50%)',
                                                                'darker' => 'Dark Overlay (70%)',
                                                                'gradient' => 'Gradient Overlay',
                                                                'primary' => 'Primary Color Overlay',
                                                            ])
                                                            ->default('gradient'),
                                                        Forms\Components\Fieldset::make('Content')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('heading')
                                                                    ->label('Main Heading')
                                                                    ->required()
                                                                    ->maxLength(150),
                                                                Forms\Components\ColorPicker::make('headingColor')
                                                                    ->label('Heading Color')
                                                                    ->default('#ffffff'),
                                                                Forms\Components\Textarea::make('subheading')
                                                                    ->label('Subheading / Description')
                                                                    ->rows(3)
                                                                    ->maxLength(500),
                                                                Forms\Components\ColorPicker::make('subheadingColor')
                                                                    ->label('Subheading Color')
                                                                    ->default('#ffffff'),
                                                            ]),
                                                        Forms\Components\Fieldset::make('Call to Action Buttons')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('buttonText')
                                                                    ->label('Primary Button Text'),
                                                                Forms\Components\TextInput::make('buttonLink')
                                                                    ->label('Primary Button Link')
                                                                    ->url(),
                                                                Forms\Components\Select::make('buttonStyle')
                                                                    ->label('Primary Button Style')
                                                                    ->options([
                                                                        'primary' => 'Primary (Solid)',
                                                                        'outline' => 'Outline',
                                                                        'accent' => 'Accent',
                                                                    ])
                                                                    ->default('primary'),
                                                                Forms\Components\TextInput::make('secondaryButtonText')
                                                                    ->label('Secondary Button Text'),
                                                                Forms\Components\TextInput::make('secondaryButtonLink')
                                                                    ->label('Secondary Button Link')
                                                                    ->url(),
                                                            ]),
                                                        Forms\Components\Fieldset::make('Foreground Element (Optional)')
                                                            ->schema([
                                                                Forms\Components\FileUpload::make('foregroundImage')
                                                                    ->label('Foreground Image/Icon')
                                                                    ->image()
                                                                    ->disk('public')
                                                                    ->directory('pages/hero-slider')
                                                                    ->imageEditor(),
                                                                Forms\Components\Select::make('foregroundPosition')
                                                                    ->label('Foreground Position')
                                                                    ->options([
                                                                        'right' => 'Right Side',
                                                                        'left' => 'Left Side',
                                                                        'center' => 'Center',
                                                                    ])
                                                                    ->default('right'),
                                                            ]),
                                                        Forms\Components\Select::make('contentAlign')
                                                            ->label('Content Alignment')
                                                            ->options([
                                                                'left' => 'Left',
                                                                'center' => 'Center',
                                                                'right' => 'Right',
                                                            ])
                                                            ->default('left'),
                                                        Forms\Components\Select::make('contentVerticalAlign')
                                                            ->label('Content Vertical Position')
                                                            ->options([
                                                                'top' => 'Top',
                                                                'center' => 'Center',
                                                                'bottom' => 'Bottom',
                                                            ])
                                                            ->default('center'),
                                                    ])
                                                    ->minItems(1)
                                                    ->defaultItems(1)
                                                    ->columnSpanFull()
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['heading'] ?? 'Slide'),
                                                Forms\Components\Fieldset::make('Slider Settings')
                                                    ->schema([
                                                        Forms\Components\Toggle::make('autoplay')
                                                            ->label('Auto-play Slider')
                                                            ->default(true),
                                                        Forms\Components\TextInput::make('interval')
                                                            ->label('Auto-play Interval (seconds)')
                                                            ->numeric()
                                                            ->default(6)
                                                            ->minValue(3)
                                                            ->maxValue(20)
                                                            ->visible(fn ($get) => $get('autoplay')),
                                                        Forms\Components\Toggle::make('showIndicators')
                                                            ->label('Show Slide Indicators (Dots)')
                                                            ->default(true),
                                                        Forms\Components\Toggle::make('showArrows')
                                                            ->label('Show Navigation Arrows')
                                                            ->default(true),
                                                        Forms\Components\Toggle::make('showParticles')
                                                            ->label('Show Animated Particles')
                                                            ->default(true),
                                                        Forms\Components\Select::make('height')
                                                            ->label('Slider Height')
                                                            ->options([
                                                                'medium' => 'Medium (600px)',
                                                                'large' => 'Large (700px)',
                                                                'xlarge' => 'Extra Large (800px)',
                                                                'full' => 'Full Screen',
                                                            ])
                                                            ->default('large'),
                                                        Forms\Components\Select::make('transition')
                                                            ->label('Transition Effect')
                                                            ->options([
                                                                'slide' => 'Slide',
                                                                'fade' => 'Fade',
                                                                'zoom' => 'Zoom',
                                                            ])
                                                            ->default('fade'),
                                                        Forms\Components\TextInput::make('transitionSpeed')
                                                            ->label('Transition Speed (milliseconds)')
                                                            ->numeric()
                                                            ->default(700)
                                                            ->minValue(300)
                                                            ->maxValue(2000),
                                                    ]),
                                            ]),

                                        // Section Blocks
                                        Block::make('hero')
                                            ->label('Hero Section')
                                            ->icon('heroicon-o-star')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Main Heading')
                                                    ->required(),
                                                Forms\Components\Textarea::make('subheading')
                                                    ->label('Subheading')
                                                    ->rows(2),
                                                Forms\Components\FileUpload::make('backgroundImage')
                                                    ->label('Background Image')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('pages/hero')
                                                    ->imageEditor(),
                                                Forms\Components\FileUpload::make('foregroundImage')
                                                    ->label('Foreground Image (Optional)')
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('pages/hero')
                                                    ->imageEditor(),
                                                Forms\Components\Select::make('overlay')
                                                    ->options([
                                                        'none' => 'No Overlay',
                                                        'dark' => 'Dark Overlay',
                                                        'gradient' => 'Gradient Overlay',
                                                        'primary' => 'Primary Color Overlay',
                                                    ])
                                                    ->default('gradient'),
                                                Forms\Components\TextInput::make('ctaText')
                                                    ->label('Call-to-Action Text'),
                                                Forms\Components\TextInput::make('ctaLink')
                                                    ->label('Call-to-Action Link'),
                                                Forms\Components\Select::make('height')
                                                    ->options([
                                                        'medium' => 'Medium (600px)',
                                                        'large' => 'Large (700px)',
                                                        'full' => 'Full Screen',
                                                    ])
                                                    ->default('large'),
                                                Forms\Components\Toggle::make('showParticles')
                                                    ->label('Show Animated Particles')
                                                    ->default(true),
                                            ]),

                                        Block::make('hero_split')
                                            ->label('Hero Split Section')
                                            ->icon('heroicon-o-rectangle-group')
                                            ->schema([
                                                Forms\Components\Fieldset::make('Heading Configuration')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('headingLine1')
                                                            ->label('Heading Line 1')
                                                            ->placeholder('Empowering Ghana\'s'),
                                                        Forms\Components\Select::make('headingLine1Color')
                                                            ->label('Line 1 Color')
                                                            ->options([
                                                                'primary' => 'Primary (Navy Blue)',
                                                                'purple' => 'Purple',
                                                                'accent' => 'Accent (Cyan)',
                                                                'secondary' => 'Secondary (Green)',
                                                                'gray' => 'Gray',
                                                            ])
                                                            ->default('primary'),
                                                        Forms\Components\TextInput::make('headingLine2')
                                                            ->label('Heading Line 2')
                                                            ->placeholder('Intellectual'),
                                                        Forms\Components\Select::make('headingLine2Color')
                                                            ->label('Line 2 Color')
                                                            ->options([
                                                                'primary' => 'Primary (Navy Blue)',
                                                                'purple' => 'Purple',
                                                                'accent' => 'Accent (Cyan)',
                                                                'secondary' => 'Secondary (Green)',
                                                                'gray' => 'Gray',
                                                            ])
                                                            ->default('purple'),
                                                        Forms\Components\TextInput::make('headingLine3')
                                                            ->label('Heading Line 3')
                                                            ->placeholder('Backbone'),
                                                        Forms\Components\Select::make('headingLine3Color')
                                                            ->label('Line 3 Color')
                                                            ->options([
                                                                'primary' => 'Primary (Navy Blue)',
                                                                'purple' => 'Purple',
                                                                'accent' => 'Accent (Cyan)',
                                                                'secondary' => 'Secondary (Green)',
                                                                'gray' => 'Gray',
                                                            ])
                                                            ->default('primary'),
                                                    ])
                                                    ->columns(2),
                                                Forms\Components\Textarea::make('subheading')
                                                    ->label('Subheading')
                                                    ->rows(3)
                                                    ->placeholder('Join a prestigious ecosystem...'),
                                                Forms\Components\Fieldset::make('Call to Action Buttons')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('primaryCtaText')
                                                            ->label('Primary Button Text')
                                                            ->placeholder('Apply for Membership'),
                                                        Forms\Components\TextInput::make('primaryCtaLink')
                                                            ->label('Primary Button Link')
                                                            ->placeholder('/membership/apply'),
                                                        Forms\Components\TextInput::make('secondaryCtaText')
                                                            ->label('Secondary Button Text')
                                                            ->placeholder('View Benefits'),
                                                        Forms\Components\TextInput::make('secondaryCtaLink')
                                                            ->label('Secondary Button Link')
                                                            ->placeholder('/membership/benefits'),
                                                    ])
                                                    ->columns(2),
                                                Forms\Components\Fieldset::make('Feature Box (Right Side)')
                                                    ->schema([
                                                        Forms\Components\Toggle::make('showFeatureBox')
                                                            ->label('Show Feature Box')
                                                            ->default(true)
                                                            ->live(),
                                                        Forms\Components\TextInput::make('featureBoxTopText')
                                                            ->label('Top Text (Small)')
                                                            ->placeholder('MADEMIIC')
                                                            ->visible(fn (Get $get) => $get('showFeatureBox') !== false),
                                                        Forms\Components\TextInput::make('featureBoxMainText')
                                                            ->label('Main Text (Large)')
                                                            ->placeholder('NAIDMIIC RESEARCH')
                                                            ->visible(fn (Get $get) => $get('showFeatureBox') !== false),
                                                        Forms\Components\TextInput::make('featureBoxBottomText')
                                                            ->label('Bottom Text (Medium)')
                                                            ->placeholder('SAFE WORK')
                                                            ->visible(fn (Get $get) => $get('showFeatureBox') !== false),
                                                        Forms\Components\Select::make('featureBoxColor')
                                                            ->label('Feature Box Color')
                                                            ->options([
                                                                'teal' => 'Teal',
                                                                'primary' => 'Primary (Navy Blue)',
                                                                'purple' => 'Purple',
                                                                'accent' => 'Accent (Cyan)',
                                                                'secondary' => 'Secondary (Green)',
                                                            ])
                                                            ->default('teal')
                                                            ->visible(fn (Get $get) => $get('showFeatureBox') !== false),
                                                        Forms\Components\Select::make('featureBoxShape')
                                                            ->label('Feature Box Shape')
                                                            ->options([
                                                                'rounded' => 'Rounded Square',
                                                                'circle' => 'Circle',
                                                            ])
                                                            ->default('rounded')
                                                            ->visible(fn (Get $get) => $get('showFeatureBox') !== false),
                                                    ]),
                                                Forms\Components\Fieldset::make('Layout Options')
                                                    ->schema([
                                                        Forms\Components\Select::make('height')
                                                            ->options([
                                                                'medium' => 'Medium (600px)',
                                                                'large' => 'Large (700px)',
                                                                'full' => 'Full Screen',
                                                                'custom' => 'Custom Height',
                                                            ])
                                                            ->default('large')
                                                            ->live(),
                                                        Forms\Components\TextInput::make('customHeight')
                                                            ->label('Custom Height (px)')
                                                            ->numeric()
                                                            ->minValue(300)
                                                            ->maxValue(1000)
                                                            ->default(700)
                                                            ->helperText('Set custom height between 300px and 1000px')
                                                            ->visible(fn (Get $get) => $get('height') === 'custom'),
                                                        Forms\Components\Select::make('width')
                                                            ->label('Content Width')
                                                            ->options([
                                                                'small' => 'Small (1024px)',
                                                                'medium' => 'Medium (1280px)',
                                                                'large' => 'Large (1536px)',
                                                                'full' => 'Full Width',
                                                                'custom' => 'Custom Width',
                                                            ])
                                                            ->default('full')
                                                            ->live(),
                                                        Forms\Components\TextInput::make('customWidth')
                                                            ->label('Custom Width (px)')
                                                            ->numeric()
                                                            ->minValue(800)
                                                            ->maxValue(1920)
                                                            ->default(1280)
                                                            ->helperText('Set custom width between 800px and 1920px')
                                                            ->visible(fn (Get $get) => $get('width') === 'custom'),
                                                        Forms\Components\Toggle::make('showDecorations')
                                                            ->label('Show Background Decorations')
                                                            ->default(true),
                                                        Forms\Components\FileUpload::make('backgroundImage')
                                                            ->label('Background Image (Optional)')
                                                            ->image()
                                                            ->directory('page-builder')
                                                            ->visibility('public')
                                                            ->helperText('Background image with white overlay'),
                                                    ])
                                                    ->columns(2),
                                            ]),

                                        Block::make('hero_dynamic')
                                            ->label('Hero Dynamic Section')
                                            ->icon('heroicon-o-sparkles')
                                            ->schema([
                                                Forms\Components\Fieldset::make('Top Badge')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('topBadge')
                                                            ->label('Badge Text')
                                                            ->placeholder('NEW FEATURE'),
                                                        Forms\Components\ColorPicker::make('topBadgeColor')
                                                            ->label('Text Color')
                                                            ->default('#14b8a6'),
                                                        Forms\Components\ColorPicker::make('topBadgeBgColor')
                                                            ->label('Background Color')
                                                            ->default('rgba(20, 184, 166, 0.1)'),
                                                    ])
                                                    ->columns(3),

                                                Forms\Components\Fieldset::make('Heading Configuration')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('headingLine1')
                                                            ->label('Heading Line 1')
                                                            ->placeholder('Empowering Ghana\'s'),
                                                        Forms\Components\Select::make('headingLine1Color')
                                                            ->label('Line 1 Color')
                                                            ->options([
                                                                'primary' => 'Primary (Navy Blue)',
                                                                'purple' => 'Purple',
                                                                'accent' => 'Accent (Cyan)',
                                                                'secondary' => 'Secondary (Green)',
                                                                'gray' => 'Gray',
                                                            ])
                                                            ->default('primary'),
                                                        Forms\Components\TextInput::make('headingLine2')
                                                            ->label('Heading Line 2')
                                                            ->placeholder('Intellectual'),
                                                        Forms\Components\Select::make('headingLine2Color')
                                                            ->label('Line 2 Color')
                                                            ->options([
                                                                'primary' => 'Primary (Navy Blue)',
                                                                'purple' => 'Purple',
                                                                'accent' => 'Accent (Cyan)',
                                                                'secondary' => 'Secondary (Green)',
                                                                'gray' => 'Gray',
                                                            ])
                                                            ->default('purple'),
                                                        Forms\Components\TextInput::make('headingLine3')
                                                            ->label('Heading Line 3')
                                                            ->placeholder('Backbone'),
                                                        Forms\Components\Select::make('headingLine3Color')
                                                            ->label('Line 3 Color')
                                                            ->options([
                                                                'primary' => 'Primary (Navy Blue)',
                                                                'purple' => 'Purple',
                                                                'accent' => 'Accent (Cyan)',
                                                                'secondary' => 'Secondary (Green)',
                                                                'gray' => 'Gray',
                                                            ])
                                                            ->default('primary'),
                                                    ])
                                                    ->columns(2),

                                                Forms\Components\Textarea::make('subheading')
                                                    ->label('Subheading')
                                                    ->rows(3)
                                                    ->placeholder('Join a prestigious ecosystem...'),

                                                Forms\Components\Fieldset::make('Call to Action Buttons')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('primaryCtaText')
                                                            ->label('Primary Button Text')
                                                            ->placeholder('Get Started'),
                                                        Forms\Components\TextInput::make('primaryCtaLink')
                                                            ->label('Primary Button Link')
                                                            ->placeholder('/get-started'),
                                                        Forms\Components\Select::make('primaryCtaStyle')
                                                            ->label('Primary Button Style')
                                                            ->options([
                                                                'filled' => 'Filled',
                                                                'outline' => 'Outline',
                                                            ])
                                                            ->default('filled'),
                                                        Forms\Components\ColorPicker::make('primaryCtaColor')
                                                            ->label('Primary Text Color')
                                                            ->default('#ffffff'),
                                                        Forms\Components\ColorPicker::make('primaryCtaBgColor')
                                                            ->label('Primary Background Color')
                                                            ->default('#14b8a6'),
                                                        Forms\Components\TextInput::make('secondaryCtaText')
                                                            ->label('Secondary Button Text')
                                                            ->placeholder('Learn More'),
                                                        Forms\Components\TextInput::make('secondaryCtaLink')
                                                            ->label('Secondary Button Link')
                                                            ->placeholder('/about'),
                                                        Forms\Components\Select::make('secondaryCtaStyle')
                                                            ->label('Secondary Button Style')
                                                            ->options([
                                                                'filled' => 'Filled',
                                                                'outline' => 'Outline',
                                                            ])
                                                            ->default('outline'),
                                                        Forms\Components\ColorPicker::make('secondaryCtaColor')
                                                            ->label('Secondary Text Color')
                                                            ->default('#ffffff'),
                                                        Forms\Components\ColorPicker::make('secondaryCtaBgColor')
                                                            ->label('Secondary Background Color')
                                                            ->default('rgba(255, 255, 255, 0.1)'),
                                                    ])
                                                    ->columns(2),

                                                Forms\Components\Fieldset::make('Bottom Badges')
                                                    ->schema([
                                                        Forms\Components\Repeater::make('bottomBadges')
                                                            ->label('Badge Items')
                                                            ->simple(
                                                                Forms\Components\TextInput::make('badge')
                                                                    ->label('Badge Text')
                                                                    ->placeholder('Feature 1')
                                                            )
                                                            ->minItems(0)
                                                            ->maxItems(5)
                                                            ->helperText('Add badges that will appear below the CTA buttons'),
                                                        Forms\Components\ColorPicker::make('bottomBadgeTextColor')
                                                            ->label('Badge Text Color')
                                                            ->default('#ffffff'),
                                                        Forms\Components\ColorPicker::make('bottomBadgeBgColor')
                                                            ->label('Badge Background Color')
                                                            ->default('rgba(255, 255, 255, 0.1)'),
                                                    ])
                                                    ->columns(2),

                                                Forms\Components\Fieldset::make('Right Content')
                                                    ->schema([
                                                        Forms\Components\Select::make('rightContentType')
                                                            ->label('Content Type')
                                                            ->options([
                                                                'text' => 'Feature Box (Text)',
                                                                'image' => 'Circular Image',
                                                                'cards' => 'Info Cards',
                                                            ])
                                                            ->default('text')
                                                            ->live(),

                                                        // Feature Box Options (Text Content)
                                                        Forms\Components\Fieldset::make('Feature Box Settings')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('featureBoxTopText')
                                                                    ->label('Top Text (Small)')
                                                                    ->placeholder('NAIDMIIC'),
                                                                Forms\Components\TextInput::make('featureBoxMainText')
                                                                    ->label('Main Text (Large)')
                                                                    ->placeholder('RESEARCH'),
                                                                Forms\Components\TextInput::make('featureBoxBottomText')
                                                                    ->label('Bottom Text (Medium)')
                                                                    ->placeholder('INNOVATION'),
                                                                Forms\Components\Select::make('featureBoxColor')
                                                                    ->label('Feature Box Color')
                                                                    ->options([
                                                                        'teal' => 'Teal',
                                                                        'primary' => 'Primary (Navy Blue)',
                                                                        'purple' => 'Purple',
                                                                        'accent' => 'Accent (Cyan)',
                                                                        'secondary' => 'Secondary (Green)',
                                                                    ])
                                                                    ->default('teal'),
                                                            ])
                                                            ->columns(2)
                                                            ->visible(fn (Get $get) => $get('rightContentType') === 'text'),

                                                        // Circular Image Options
                                                        Forms\Components\Fieldset::make('Circular Image Settings')
                                                            ->schema([
                                                                Forms\Components\FileUpload::make('circleImage')
                                                                    ->label('Circle Image')
                                                                    ->image()
                                                                    ->directory('page-builder')
                                                                    ->visibility('public'),
                                                                Forms\Components\Select::make('circleImageSize')
                                                                    ->label('Image Size')
                                                                    ->options([
                                                                        'small' => 'Small (250px)',
                                                                        'medium' => 'Medium (350px)',
                                                                        'large' => 'Large (450px)',
                                                                        'xlarge' => 'Extra Large (550px)',
                                                                    ])
                                                                    ->default('medium'),
                                                                Forms\Components\Toggle::make('showHoverOverlay')
                                                                    ->label('Show Hover Overlay')
                                                                    ->default(false)
                                                                    ->live(),
                                                                Forms\Components\ColorPicker::make('hoverOverlayColor')
                                                                    ->label('Hover Overlay Color')
                                                                    ->default('rgba(147, 51, 234, 0.85)')
                                                                    ->visible(fn (Get $get) => $get('showHoverOverlay') === true),
                                                                Forms\Components\TextInput::make('hoverText')
                                                                    ->label('Hover Text')
                                                                    ->placeholder('Learn More')
                                                                    ->visible(fn (Get $get) => $get('showHoverOverlay') === true),
                                                            ])
                                                            ->columns(2)
                                                            ->visible(fn (Get $get) => $get('rightContentType') === 'image'),

                                                        // Info Cards Options
                                                        Forms\Components\Fieldset::make('Info Cards Settings')
                                                            ->schema([
                                                                Forms\Components\Repeater::make('infoCards')
                                                                    ->label('Cards')
                                                                    ->schema([
                                                                        Forms\Components\TextInput::make('header')
                                                                            ->label('Header (Small Text)')
                                                                            ->placeholder('UPCOMING EVENT'),
                                                                        Forms\Components\TextInput::make('title')
                                                                            ->label('Title (Large Text)')
                                                                            ->required()
                                                                            ->placeholder('May 15-17'),
                                                                        Forms\Components\Textarea::make('description')
                                                                            ->label('Description')
                                                                            ->rows(2)
                                                                            ->placeholder('Annual conference...'),
                                                                        Forms\Components\TextInput::make('progress')
                                                                            ->label('Progress (0-100)')
                                                                            ->numeric()
                                                                            ->minValue(0)
                                                                            ->maxValue(100)
                                                                            ->placeholder('75'),
                                                                        Forms\Components\TextInput::make('progressLabel')
                                                                            ->label('Progress Label')
                                                                            ->placeholder('Progress'),
                                                                        Forms\Components\TextInput::make('progressValue')
                                                                            ->label('Progress Value Display')
                                                                            ->placeholder('75'),
                                                                        Forms\Components\ColorPicker::make('progressColor')
                                                                            ->label('Progress Bar Color')
                                                                            ->default('#14b8a6'),
                                                                        Forms\Components\Section::make('Card Colors')
                                                                            ->schema([
                                                                                Forms\Components\ColorPicker::make('cardBackgroundColor')
                                                                                    ->label('Card Background Color')
                                                                                    ->default('rgba(255, 255, 255, 0.05)'),
                                                                                Forms\Components\ColorPicker::make('cardBorderColor')
                                                                                    ->label('Card Border Color')
                                                                                    ->default('rgba(255, 255, 255, 0.1)'),
                                                                                Forms\Components\ColorPicker::make('headerTextColor')
                                                                                    ->label('Header Text Color')
                                                                                    ->default('#9ca3af'),
                                                                                Forms\Components\ColorPicker::make('titleTextColor')
                                                                                    ->label('Title Text Color')
                                                                                    ->default('#ffffff'),
                                                                                Forms\Components\ColorPicker::make('descriptionTextColor')
                                                                                    ->label('Description Text Color')
                                                                                    ->default('#d1d5db'),
                                                                            ])
                                                                            ->columns(2)
                                                                            ->collapsible()
                                                                            ->compact(),
                                                                    ])
                                                                    ->minItems(1)
                                                                    ->maxItems(3)
                                                                    ->collapsible()
                                                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                                                            ])
                                                            ->visible(fn (Get $get) => $get('rightContentType') === 'cards'),
                                                    ]),

                                                Forms\Components\Fieldset::make('Layout Options')
                                                    ->schema([
                                                        Forms\Components\FileUpload::make('backgroundImage')
                                                            ->label('Background Image (Optional)')
                                                            ->image()
                                                            ->directory('page-builder')
                                                            ->visibility('public')
                                                            ->helperText('Background image with overlay'),
                                                        Forms\Components\ColorPicker::make('overlayColor')
                                                            ->label('Background Overlay Color')
                                                            ->default('#000000')
                                                            ->helperText('Color of the overlay on top of the background image'),
                                                        Forms\Components\TextInput::make('overlayOpacity')
                                                            ->label('Overlay Opacity (0-1)')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->maxValue(1)
                                                            ->step(0.05)
                                                            ->default(0.5)
                                                            ->helperText('0 = transparent, 1 = fully opaque'),
                                                        Forms\Components\Select::make('height')
                                                            ->options([
                                                                'medium' => 'Medium (500-600px)',
                                                                'large' => 'Large (600-700px)',
                                                                'full' => 'Full Screen (700px+)',
                                                                'custom' => 'Custom Height',
                                                            ])
                                                            ->default('large')
                                                            ->live(),
                                                        Forms\Components\TextInput::make('customHeight')
                                                            ->label('Custom Height (px)')
                                                            ->numeric()
                                                            ->minValue(300)
                                                            ->maxValue(1000)
                                                            ->default(600)
                                                            ->helperText('Set custom height between 300px and 1000px')
                                                            ->visible(fn (Get $get) => $get('height') === 'custom'),
                                                        Forms\Components\Select::make('width')
                                                            ->label('Content Width')
                                                            ->options([
                                                                'small' => 'Small (1024px)',
                                                                'medium' => 'Medium (1280px)',
                                                                'large' => 'Large (1536px)',
                                                                'full' => 'Full Width',
                                                                'custom' => 'Custom Width',
                                                            ])
                                                            ->default('full')
                                                            ->live(),
                                                        Forms\Components\TextInput::make('customWidth')
                                                            ->label('Custom Width (px)')
                                                            ->numeric()
                                                            ->minValue(800)
                                                            ->maxValue(1920)
                                                            ->default(1280)
                                                            ->helperText('Set custom width between 800px and 1920px')
                                                            ->visible(fn (Get $get) => $get('width') === 'custom'),
                                                        Forms\Components\Toggle::make('showDecorations')
                                                            ->label('Show Background Decorations')
                                                            ->default(true)
                                                            ->helperText('Animated gradient circles and decorative elements'),
                                                    ])
                                                    ->columns(2),
                                            ]),

                                        Block::make('stats')
                                            ->label('Statistics Section')
                                            ->icon('heroicon-o-chart-bar')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading (Optional)'),
                                                Forms\Components\Repeater::make('items')
                                                    ->label('Statistics')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('value')
                                                            ->label('Stat Value')
                                                            ->required()
                                                            ->placeholder('25'),
                                                        Forms\Components\TextInput::make('suffix')
                                                            ->label('Suffix')
                                                            ->placeholder('+ or % or Gbps'),
                                                        Forms\Components\TextInput::make('label')
                                                            ->label('Label')
                                                            ->required()
                                                            ->placeholder('Member Institutions'),
                                                    ])
                                                    ->minItems(2)
                                                    ->maxItems(6)
                                                    ->defaultItems(4)
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),
                                                Forms\Components\Select::make('columns')
                                                    ->options([
                                                        '2' => '2 Columns',
                                                        '3' => '3 Columns',
                                                        '4' => '4 Columns',
                                                    ])
                                                    ->default('4'),
                                                Forms\Components\Toggle::make('animate')
                                                    ->label('Animate Counters')
                                                    ->default(true),
                                            ]),

                                        Block::make('two_column')
                                            ->label('Two Column Layout')
                                            ->icon('heroicon-o-view-columns')
                                            ->schema([
                                                Forms\Components\RichEditor::make('leftContent')
                                                    ->label('Left Column')
                                                    ->columnSpanFull()
                                                    ->required(),
                                                Forms\Components\RichEditor::make('rightContent')
                                                    ->label('Right Column')
                                                    ->columnSpanFull()
                                                    ->required(),
                                                Forms\Components\Select::make('ratio')
                                                    ->label('Column Ratio')
                                                    ->options([
                                                        '50-50' => '50% / 50%',
                                                        '60-40' => '60% / 40%',
                                                        '40-60' => '40% / 60%',
                                                        '70-30' => '70% / 30%',
                                                        '30-70' => '30% / 70%',
                                                    ])
                                                    ->default('50-50'),
                                                Forms\Components\Toggle::make('reverseOnMobile')
                                                    ->label('Reverse Order on Mobile')
                                                    ->default(false),
                                            ]),

                                        Block::make('card_grid')
                                            ->label('Card Grid')
                                            ->icon('heroicon-o-squares-2x2')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading (Optional)'),
                                                Forms\Components\Repeater::make('cards')
                                                    ->schema([
                                                        Forms\Components\FileUpload::make('image')
                                                            ->label('Card Image')
                                                            ->image()
                                                            ->disk('public')
                                                            ->directory('pages/cards')
                                                            ->imageEditor(),
                                                        Forms\Components\TextInput::make('title')
                                                            ->label('Card Title')
                                                            ->required(),
                                                        Forms\Components\Textarea::make('description')
                                                            ->label('Card Description')
                                                            ->rows(3),
                                                        Forms\Components\TextInput::make('link')
                                                            ->label('Link URL (Optional)'),
                                                    ])
                                                    ->minItems(1)
                                                    ->maxItems(12)
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                                                Forms\Components\Select::make('columns')
                                                    ->options([
                                                        '2' => '2 Columns',
                                                        '3' => '3 Columns',
                                                        '4' => '4 Columns',
                                                    ])
                                                    ->default('3'),
                                            ]),

                                        Block::make('icon_cards')
                                            ->label('Icon Cards Section')
                                            ->icon('heroicon-o-rectangle-stack')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading (Optional)'),
                                                Forms\Components\Textarea::make('subheading')
                                                    ->label('Section Subheading (Optional)')
                                                    ->rows(2),
                                                Forms\Components\Repeater::make('cards')
                                                    ->label('Cards')
                                                    ->schema([
                                                        Forms\Components\Select::make('icon')
                                                            ->label('Icon')
                                                            ->options([
                                                                'rocket' => 'Rocket',
                                                                'eye' => 'Eye',
                                                                'star' => 'Star',
                                                                'heart' => 'Heart',
                                                                'lightbulb' => 'Lightbulb',
                                                                'target' => 'Target',
                                                                'shield' => 'Shield',
                                                                'users' => 'Users',
                                                            ])
                                                            ->default('star'),
                                                        Forms\Components\ColorPicker::make('iconColor')
                                                            ->label('Icon Color')
                                                            ->default('#3B82F6'),
                                                        Forms\Components\ColorPicker::make('borderColor')
                                                            ->label('Top Border Color')
                                                            ->default('#3B82F6'),
                                                        Forms\Components\TextInput::make('title')
                                                            ->label('Card Title')
                                                            ->required(),
                                                        Forms\Components\Textarea::make('description')
                                                            ->label('Card Description')
                                                            ->rows(4)
                                                            ->required(),
                                                        Forms\Components\TextInput::make('link')
                                                            ->label('Link URL (Optional)'),
                                                        Forms\Components\TextInput::make('linkText')
                                                            ->label('Link Text (Optional)')
                                                            ->placeholder('Learn more'),
                                                    ])
                                                    ->minItems(1)
                                                    ->maxItems(4)
                                                    ->defaultItems(2)
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                                                Forms\Components\Select::make('columns')
                                                    ->label('Columns')
                                                    ->options([
                                                        '1' => '1 Column',
                                                        '2' => '2 Columns',
                                                        '3' => '3 Columns',
                                                        '4' => '4 Columns',
                                                    ])
                                                    ->default('2'),
                                            ]),

                                        Block::make('who_we_are')
                                            ->label('Who We Are / About Section')
                                            ->icon('heroicon-o-information-circle')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading')
                                                    ->placeholder('Who We Are'),
                                                Forms\Components\ColorPicker::make('headingColor')
                                                    ->label('Heading Color')
                                                    ->default('#1E40AF'),
                                                Forms\Components\Textarea::make('description')
                                                    ->label('Description')
                                                    ->rows(4)
                                                    ->placeholder('Main description text...'),
                                                Forms\Components\ColorPicker::make('featureTitleColor')
                                                    ->label('Feature Title Color')
                                                    ->default('#1E40AF'),
                                                Forms\Components\Repeater::make('features')
                                                    ->label('Feature Cards')
                                                    ->schema([
                                                        Forms\Components\Select::make('icon')
                                                            ->label('Icon')
                                                            ->options([
                                                                'users' => 'Users / Team',
                                                                'shield' => 'Shield / Security',
                                                                'star' => 'Star / Quality',
                                                                'heart' => 'Heart / Care',
                                                                'globe' => 'Globe / Global',
                                                                'lightbulb' => 'Lightbulb / Ideas',
                                                                'target' => 'Target / Goals',
                                                            ])
                                                            ->default('users'),
                                                        Forms\Components\ColorPicker::make('iconColor')
                                                            ->label('Icon Color')
                                                            ->default('#1E40AF'),
                                                        Forms\Components\ColorPicker::make('iconBgColor')
                                                            ->label('Icon Background Color')
                                                            ->default('#EBF5FF'),
                                                        Forms\Components\TextInput::make('title')
                                                            ->label('Title')
                                                            ->required()
                                                            ->placeholder('Member-Driven'),
                                                        Forms\Components\Textarea::make('description')
                                                            ->label('Description')
                                                            ->rows(3)
                                                            ->required()
                                                            ->placeholder('Feature description...'),
                                                    ])
                                                    ->minItems(1)
                                                    ->maxItems(4)
                                                    ->defaultItems(2)
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                                                Forms\Components\Repeater::make('statCards')
                                                    ->label('Stat/Info Cards (Right Side)')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('label')
                                                            ->label('Label/Tag (Optional)')
                                                            ->placeholder('Research'),
                                                        Forms\Components\TextInput::make('tagBox')
                                                            ->label('Tag Box Text (Optional)')
                                                            ->placeholder('RESEARCH/EDUCATION/COLLABORATION'),
                                                        Forms\Components\TextInput::make('mainText')
                                                            ->label('Main Text')
                                                            ->placeholder('10Gbps'),
                                                        Forms\Components\TextInput::make('subtitle')
                                                            ->label('Subtitle')
                                                            ->placeholder('BACKBONE SPEED'),
                                                        Forms\Components\Textarea::make('description')
                                                            ->label('Description (Optional)')
                                                            ->rows(2),
                                                        Forms\Components\ColorPicker::make('backgroundColor')
                                                            ->label('Background Color')
                                                            ->default('#1E40AF'),
                                                        Forms\Components\FileUpload::make('backgroundImage')
                                                            ->label('Background Image (Optional)')
                                                            ->image()
                                                            ->directory('page-builder')
                                                            ->visibility('public'),
                                                        Forms\Components\Toggle::make('showDecorations')
                                                            ->label('Show Network Decorations')
                                                            ->default(false)
                                                            ->helperText('Shows network node graphics (for Research card)'),
                                                        Forms\Components\Select::make('textEffect')
                                                            ->label('Text Effect')
                                                            ->options([
                                                                'normal' => 'Normal',
                                                                'glitch' => 'Glitch/Shadow Effect',
                                                            ])
                                                            ->default('normal'),
                                                        Forms\Components\Select::make('size')
                                                            ->label('Card Size')
                                                            ->options([
                                                                'normal' => 'Normal (1 unit)',
                                                                'large' => 'Large (2 columns wide)',
                                                                'tall' => 'Tall (2 rows high)',
                                                                'large-tall' => 'Large & Tall (2x2)',
                                                            ])
                                                            ->default('normal'),
                                                    ])
                                                    ->minItems(3)
                                                    ->maxItems(6)
                                                    ->defaultItems(4)
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['mainText'] ?? null),
                                            ]),

                                        Block::make('timeline')
                                            ->label('Timeline / History')
                                            ->icon('heroicon-o-clock')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading')
                                                    ->placeholder('Our History'),
                                                Forms\Components\ColorPicker::make('headingColor')
                                                    ->label('Heading Color')
                                                    ->default('#1E40AF'),
                                                Forms\Components\Repeater::make('items')
                                                    ->label('Timeline Items')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('year')
                                                            ->label('Year / Date')
                                                            ->placeholder('2006'),
                                                        Forms\Components\ColorPicker::make('yearColor')
                                                            ->label('Year Text Color')
                                                            ->default('#1E40AF'),
                                                        Forms\Components\TextInput::make('title')
                                                            ->label('Title')
                                                            ->required()
                                                            ->placeholder('The Foundation'),
                                                        Forms\Components\Textarea::make('description')
                                                            ->label('Description')
                                                            ->rows(4)
                                                            ->required(),
                                                        Forms\Components\ColorPicker::make('backgroundColor')
                                                            ->label('Background Color')
                                                            ->default('#E5E7EB'),
                                                        Forms\Components\Select::make('size')
                                                            ->label('Card Size')
                                                            ->options([
                                                                'normal' => 'Normal',
                                                                'large' => 'Large',
                                                                'full' => 'Full Width',
                                                            ])
                                                            ->default('normal'),
                                                        Forms\Components\Select::make('icon')
                                                            ->label('Icon (Optional)')
                                                            ->options([
                                                                'none' => 'No Icon',
                                                                'globe' => 'Globe',
                                                                'star' => 'Star',
                                                                'trophy' => 'Trophy',
                                                                'rocket' => 'Rocket',
                                                                'users' => 'Users',
                                                                'lightbulb' => 'Lightbulb',
                                                            ])
                                                            ->default('none'),
                                                        Forms\Components\Toggle::make('showIcon')
                                                            ->label('Show Background Year/Number')
                                                            ->default(true),
                                                    ])
                                                    ->minItems(1)
                                                    ->maxItems(8)
                                                    ->defaultItems(3)
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                                            ]),

                                        // Form Blocks
                                        Block::make('contact_form')
                                            ->label('Contact Form')
                                            ->icon('heroicon-o-envelope')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Form Heading')
                                                    ->default('Contact Us'),
                                                Forms\Components\Textarea::make('description')
                                                    ->label('Form Description')
                                                    ->rows(2),
                                                Forms\Components\TextInput::make('submitText')
                                                    ->label('Submit Button Text')
                                                    ->default('Send Message'),
                                                Forms\Components\TextInput::make('emailTo')
                                                    ->label('Send Submissions To')
                                                    ->email()
                                                    ->required()
                                                    ->helperText('Email address to receive form submissions'),
                                                Forms\Components\Toggle::make('showPhone')
                                                    ->label('Include Phone Field')
                                                    ->default(false),
                                                Forms\Components\Toggle::make('showCompany')
                                                    ->label('Include Organization Field')
                                                    ->default(true),
                                            ]),

                                        Block::make('search_box')
                                            ->label('Search Box')
                                            ->icon('heroicon-o-magnifying-glass')
                                            ->schema([
                                                Forms\Components\TextInput::make('placeholder')
                                                    ->label('Placeholder Text')
                                                    ->default('Search...'),
                                                Forms\Components\Select::make('searchScope')
                                                    ->label('Search In')
                                                    ->options([
                                                        'pages' => 'Pages Only',
                                                        'news' => 'News Only',
                                                        'events' => 'Events Only',
                                                        'all' => 'All Content',
                                                    ])
                                                    ->default('all'),
                                                Forms\Components\Select::make('size')
                                                    ->options([
                                                        'sm' => 'Small',
                                                        'md' => 'Medium',
                                                        'lg' => 'Large',
                                                    ])
                                                    ->default('md'),
                                            ]),

                                        // Dynamic Content Blocks
                                        Block::make('dynamic_news')
                                            ->label('Latest News (Dynamic)')
                                            ->icon('heroicon-o-newspaper')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading')
                                                    ->default('Latest News'),
                                                Forms\Components\Textarea::make('subheading')
                                                    ->label('Subheading (Optional)')
                                                    ->rows(2),
                                                Forms\Components\TextInput::make('limit')
                                                    ->label('Number of Items')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->maxValue(12)
                                                    ->default(3),
                                                Forms\Components\Select::make('layout')
                                                    ->options([
                                                        'grid' => 'Grid Layout',
                                                        'list' => 'List Layout',
                                                        'carousel' => 'Carousel',
                                                    ])
                                                    ->default('grid')
                                                    ->live(),
                                                Forms\Components\Select::make('columns')
                                                    ->label('Grid Columns')
                                                    ->options([
                                                        '2' => '2 Columns',
                                                        '3' => '3 Columns',
                                                        '4' => '4 Columns',
                                                    ])
                                                    ->default('3')
                                                    ->visible(fn (Get $get) => $get('layout') === 'grid'),
                                                Forms\Components\Toggle::make('showImage')
                                                    ->label('Show Featured Images')
                                                    ->default(true),
                                                Forms\Components\Toggle::make('showExcerpt')
                                                    ->label('Show Excerpt')
                                                    ->default(true),
                                                Forms\Components\Toggle::make('showDate')
                                                    ->label('Show Publication Date')
                                                    ->default(true),
                                                Forms\Components\Toggle::make('showReadMore')
                                                    ->label('Show Read More Link')
                                                    ->default(true),
                                            ]),

                                        Block::make('dynamic_events')
                                            ->label('Upcoming Events (Dynamic)')
                                            ->icon('heroicon-o-calendar-days')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading')
                                                    ->default('Upcoming Events'),
                                                Forms\Components\TextInput::make('limit')
                                                    ->label('Number of Events')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->maxValue(12)
                                                    ->default(4),
                                                Forms\Components\Select::make('layout')
                                                    ->options([
                                                        'grid' => 'Grid Layout',
                                                        'list' => 'List Layout',
                                                        'timeline' => 'Timeline',
                                                    ])
                                                    ->default('grid'),
                                                Forms\Components\Select::make('filter')
                                                    ->label('Show Events')
                                                    ->options([
                                                        'upcoming' => 'Upcoming Only',
                                                        'past' => 'Past Only',
                                                        'all' => 'All Events',
                                                    ])
                                                    ->default('upcoming'),
                                                Forms\Components\Toggle::make('showLocation')
                                                    ->label('Show Event Location')
                                                    ->default(true),
                                            ]),

                                        Block::make('dynamic_services')
                                            ->label('Services (Dynamic)')
                                            ->icon('heroicon-o-cpu-chip')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading')
                                                    ->default('Our Services'),
                                                Forms\Components\Textarea::make('subheading')
                                                    ->label('Subheading (Optional)')
                                                    ->rows(2),
                                                Forms\Components\Select::make('limit')
                                                    ->label('Number of Services')
                                                    ->options([
                                                        '3' => '3 Services',
                                                        '6' => '6 Services',
                                                        '9' => '9 Services',
                                                        'all' => 'All Services',
                                                    ])
                                                    ->default('6'),
                                                Forms\Components\Select::make('layout')
                                                    ->options([
                                                        'grid' => 'Grid Layout',
                                                        'list' => 'List Layout',
                                                    ])
                                                    ->default('grid'),
                                                Forms\Components\Toggle::make('showIcons')
                                                    ->label('Show Service Icons')
                                                    ->default(true),
                                                Forms\Components\Toggle::make('showDescription')
                                                    ->label('Show Descriptions')
                                                    ->default(true),
                                            ]),

                                        Block::make('dynamic_members')
                                            ->label('Member Institutions (Dynamic)')
                                            ->icon('heroicon-o-building-library')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading')
                                                    ->default('Our Members'),
                                                Forms\Components\Select::make('display')
                                                    ->options([
                                                        'grid' => 'Grid with Logos',
                                                        'logos' => 'Logos Only',
                                                        'list' => 'Detailed List',
                                                    ])
                                                    ->default('grid'),
                                                Forms\Components\Select::make('limit')
                                                    ->label('Number to Show')
                                                    ->options([
                                                        '6' => '6 Members',
                                                        '12' => '12 Members',
                                                        '24' => '24 Members',
                                                        'all' => 'All Members',
                                                    ])
                                                    ->default('12'),
                                            ]),

                                        Block::make('dynamic_team_members')
                                            ->label('Team Members (Dynamic)')
                                            ->icon('heroicon-o-user-group')
                                            ->schema([
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading')
                                                    ->default('Meet our professional and expert team members'),
                                                Forms\Components\Textarea::make('subheading')
                                                    ->label('Subheading (Optional)')
                                                    ->rows(2)
                                                    ->default('There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form'),
                                                Forms\Components\Select::make('limit')
                                                    ->label('Number of Team Members')
                                                    ->options([
                                                        '3' => '3 Members',
                                                        '4' => '4 Members',
                                                        '6' => '6 Members',
                                                        '8' => '8 Members',
                                                        'all' => 'All Members',
                                                    ])
                                                    ->default('4'),
                                                Forms\Components\Select::make('layout')
                                                    ->options([
                                                        'grid' => 'Grid Layout',
                                                        'list' => 'List Layout',
                                                    ])
                                                    ->default('grid'),
                                                Forms\Components\Select::make('columns')
                                                    ->label('Grid Columns')
                                                    ->options([
                                                        '2' => '2 Columns',
                                                        '3' => '3 Columns',
                                                        '4' => '4 Columns',
                                                    ])
                                                    ->default('4')
                                                    ->visible(fn (Get $get) => $get('layout') === 'grid'),
                                                Forms\Components\Toggle::make('showPhoto')
                                                    ->label('Show Photos')
                                                    ->default(true),
                                                Forms\Components\Toggle::make('showBio')
                                                    ->label('Show Biography')
                                                    ->default(false),
                                                Forms\Components\Toggle::make('showEmail')
                                                    ->label('Show Email')
                                                    ->default(false),
                                                Forms\Components\Toggle::make('showSocialLinks')
                                                    ->label('Show Social Media Links')
                                                    ->default(false),
                                            ]),

                                        Block::make('dynamic_carousel')
                                            ->label('Carousel (Dynamic)')
                                            ->icon('heroicon-o-rectangle-stack')
                                            ->schema([
                                                Forms\Components\Select::make('contentType')
                                                    ->label('Content Type')
                                                    ->options([
                                                        'members' => 'Members',
                                                        'services' => 'Services',
                                                        'news' => 'News/Posts',
                                                        'events' => 'Events',
                                                        'team_members' => 'Team Members',
                                                    ])
                                                    ->default('members')
                                                    ->required()
                                                    ->live(),
                                                Forms\Components\TextInput::make('heading')
                                                    ->label('Section Heading')
                                                    ->default('Featured Content'),
                                                Forms\Components\Textarea::make('subheading')
                                                    ->label('Subheading (Optional)')
                                                    ->rows(2),
                                                Forms\Components\Select::make('limit')
                                                    ->label('Number of Items')
                                                    ->options([
                                                        '6' => '6 Items',
                                                        '9' => '9 Items',
                                                        '12' => '12 Items',
                                                        '20' => '20 Items',
                                                        '40' => '40 Items',
                                                        'all' => 'All Items',
                                                    ])
                                                    ->default('12'),
                                                Forms\Components\Select::make('itemsPerView')
                                                    ->label('Items Per View (Desktop)')
                                                    ->options([
                                                        '2' => '2 Items',
                                                        '3' => '3 Items',
                                                        '4' => '4 Items',
                                                        '5' => '5 Items',
                                                        '6' => '6 Items',
                                                    ])
                                                    ->default('4'),
                                                Forms\Components\Toggle::make('autoplay')
                                                    ->label('Enable Autoplay')
                                                    ->default(true)
                                                    ->live(),
                                                Forms\Components\TextInput::make('autoplayDelay')
                                                    ->label('Autoplay Delay (ms)')
                                                    ->numeric()
                                                    ->default(3000)
                                                    ->minValue(1000)
                                                    ->maxValue(10000)
                                                    ->step(500)
                                                    ->visible(fn (Get $get) => $get('autoplay') === true),
                                                Forms\Components\Toggle::make('showNavigation')
                                                    ->label('Show Navigation Arrows')
                                                    ->default(true),
                                                Forms\Components\Toggle::make('showPagination')
                                                    ->label('Show Pagination Dots')
                                                    ->default(true),
                                                Forms\Components\Section::make('Carousel Colors')
                                                    ->schema([
                                                        Forms\Components\ColorPicker::make('navigationColor')
                                                            ->label('Arrow Color')
                                                            ->default('#0ea5e9'),
                                                        Forms\Components\ColorPicker::make('navigationBgColor')
                                                            ->label('Arrow Background')
                                                            ->default('#ffffff'),
                                                        Forms\Components\ColorPicker::make('navigationHoverColor')
                                                            ->label('Arrow Hover Background')
                                                            ->default('#0ea5e9'),
                                                        Forms\Components\ColorPicker::make('paginationColor')
                                                            ->label('Dot Color (Inactive)')
                                                            ->default('#cbd5e1'),
                                                        Forms\Components\ColorPicker::make('paginationActiveColor')
                                                            ->label('Dot Color (Active)')
                                                            ->default('#0ea5e9'),
                                                    ])
                                                    ->columns(2)
                                                    ->collapsible()
                                                    ->compact(),
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->columnSpanFull()
                                    ->minItems(0)
                                    ->addActionLabel('Add Block')
                                    ->blockNumbers(false),
                            ])
                            ->visible(fn (Get $get) => $get('template_type') === 'builder'),

                        // SEO Tab
                        Forms\Components\Tabs\Tab::make('SEO')
                            ->schema([
                                Forms\Components\TextInput::make('meta_title')
                                    ->label('Meta Title')
                                    ->helperText('Leave empty to use page title'),
                                Forms\Components\Textarea::make('meta_description')
                                    ->label('Meta Description')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->helperText('Leave empty to use excerpt'),
                            ]),
                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('featured_image'),
                Tables\Columns\BadgeColumn::make('template_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'builder',
                        'secondary' => 'content',
                    ])
                    ->icons([
                        'heroicon-o-cube' => 'builder',
                        'heroicon-o-document-text' => 'content',
                    ]),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('visual_editor')
                    ->label('Page Builder')
                    ->icon('heroicon-o-cube')
                    ->color('primary')
                    ->url(fn (Page $record): string => config('app.frontend_url', 'http://localhost:3000') . "/elementor/{$record->id}")
                    ->openUrlInNewTab()
                    ->visible(fn (Page $record): bool => $record->template_type === 'builder'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
