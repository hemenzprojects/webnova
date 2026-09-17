# Elementor-Style Page Builder Guide

## Overview
The Elementor-style page builder provides a professional drag-and-drop interface for building pages with sections, columns, and widgets.

## Features

### 🎨 Section-Based Layout
- **Add Sections**: Create rows/sections with custom column layouts
- **Column Layouts**: Choose from 9 preset layouts:
  - 1 column (100%)
  - 2 columns (50/50, 33/67, 67/33, 25/75, 75/25)
  - 3 columns (33/33/34, 25/50/25)
  - 4 columns (25/25/25/25)
- **Section Controls**: Move up/down, duplicate, delete sections
- **Section Styling**: Background colors, images, padding, margin, layout options

### 📚 Widget Library
Left sidebar with categorized widgets:

**Basic Widgets:**
- Heading - Customizable text headings (H1-H6)
- Text Editor - Rich text content
- Image - Image display with alignment
- Button - Call-to-action buttons
- Spacer - Vertical spacing control
- Divider - Horizontal dividers

**Section Widgets:**
- Hero Section - Full-width hero banners
- Counter/Stats - Animated statistics
- Card Grid - Grid of cards

**Form Widgets:**
- Contact Form - Contact forms
- Search Box - Search functionality

**Dynamic Content:**
- Posts - News/blog posts feed
- Events - Events listing
- Services - Services grid
- Members - Member institutions

### ⚙️ Settings Panel
Right sidebar for editing selected elements:

**Widget Settings:**
- **Content Tab**: Edit text, URLs, images, widget-specific content
- **Style Tab**: Colors, typography, alignment, spacing
- **Advanced Tab**: Custom CSS classes (coming soon)

**Section Settings:**
- **Content Tab**: Layout (boxed/full-width), column gap, height
- **Style Tab**: Background color/image, padding, margin

### 🖱️ Drag & Drop
- **Drag widgets** from sidebar into columns
- **Drag widgets** between columns to reorganize
- **Visual drop zones** with animated indicators
- **Live preview** as you build

### 💾 Save & Publish
- **Auto-save detection**: Shows "Unsaved Changes" in toolbar
- **Save Draft**: Save without publishing (Cmd/Ctrl + S)
- **Publish**: Make page live on website
- **Data persistence**: Multi-column sections are preserved

## How to Use

### 1. Access the Editor

**From Filament Admin:**
1. Go to `/admin/pages`
2. Click on a page (or create new one)
3. Set template type to "Page Builder"
4. Save the page
5. The editor is accessible at: `/elementor/{page-id}`

**Direct Link:**
Navigate to `/elementor/{page-id}` in your browser

### 2. Building Your Page

#### Add a Section
1. Click **"Add Section"** button in toolbar (creates single column)
2. OR click **"Structure"** button to choose column layout
3. Select from 9 preset layouts in the modal

#### Add Widgets
1. **From Sidebar**: Click or drag a widget from left sidebar into a column
2. **Quick Add**: Clicking a widget creates a new 1-column section automatically

#### Edit Content
1. **Click any widget** to select it
2. Settings panel appears on the right
3. Edit in **Content** tab (text, URLs, etc.)
4. Style in **Style** tab (colors, fonts, etc.)
5. Changes apply **immediately** (live preview)

#### Reorganize
- **Move sections**: Use ↑ ↓ buttons in section header
- **Move widgets**: Drag widgets between columns
- **Duplicate**: Click duplicate icon in section/widget controls
- **Delete**: Click trash icon

### 3. Section Styling

**Select a section** (click outside widgets):
1. Settings panel shows section options
2. **Content Tab**:
   - Layout: Boxed (contained) or Full Width
   - Column Gap: No gap, Narrow, Default, Wide
   - Height: Default, Min Height, Fit to Screen
3. **Style Tab**:
   - Background Color: Color picker
   - Background Image: Image URL
   - Padding: Top, Right, Bottom, Left (px)
   - Margin: Top, Bottom (px)

### 4. Preview & Publish

**Device Preview:**
- Click Desktop/Tablet/Mobile icons in toolbar
- Canvas resizes to show device view

**Save & Publish:**
1. Click **"Save Draft"** to save without publishing
2. Click **"Publish"** to make page live
3. Unsaved changes indicator shows in toolbar

## Widget Settings Reference

### Heading Widget
- **Content**: Text, HTML tag (H1-H6), Alignment
- **Style**: Color, Font size, Font weight

### Text Editor Widget
- **Content**: HTML content, Alignment
- Supports rich text formatting

### Image Widget
- **Content**: Image URL, Alt text, Width (%, px), Alignment

### Button Widget
- **Content**: Button text, Link URL, Alignment
- **Style**: Style (Primary, Secondary, Accent, Outline), Size (Small, Medium, Large)

### Spacer Widget
- **Content**: Height with unit (px, em, %)

### Divider Widget
- **Content**: Style (Solid, Dashed, Dotted), Color, Weight (px)

### Hero Section
Configure in Filament admin panel's Page Builder tab

### Dynamic Widgets
Configure in Filament admin panel's Page Builder tab

## Keyboard Shortcuts

- **Cmd/Ctrl + S** - Save draft
- **Escape** - Deselect element
- **Delete** - Delete selected section/widget (coming soon)

## Tips & Best Practices

1. **Start with Structure**: Add sections with correct column layout first
2. **Mobile-First**: Check mobile preview regularly
3. **Consistent Spacing**: Use section padding/margin for consistent spacing
4. **Save Often**: Use Cmd/Ctrl + S to save frequently
5. **Background Images**: Use high-quality images (1920px wide recommended)
6. **Accessibility**: Always add alt text to images

## Technical Details

### Architecture
- **Frontend**: Nuxt 3 + Vue 3 Composition API
- **State Management**: Composables (`useElementorEditor.ts`)
- **Data Format**: Sections → Columns → Widgets hierarchy
- **Persistence**: Converted to flat blocks for backend storage

### Files
```
frontend/
├── pages/elementor/[id].vue          # Main editor page
├── components/Elementor/
│   ├── WidgetPanel.vue               # Left sidebar - widget library
│   ├── Section.vue                   # Section container with controls
│   ├── Column.vue                    # Column with drop zones
│   ├── Widget.vue                    # Widget renderer
│   ├── SettingsPanel.vue             # Right sidebar - settings
│   └── SectionLayoutPicker.vue       # Column layout modal
└── composables/
    └── useElementorEditor.ts         # Editor state management
```

### API Endpoints
- `GET /api/v1/pages/{id}/edit` - Load page for editing
- `PUT /api/v1/pages/{id}/blocks` - Save page blocks
- `POST /api/v1/pages/{id}/publish` - Publish page

### Data Structure
```typescript
Section {
  id: string
  type: 'section'
  columns: Column[]
  settings: {
    layout: 'boxed' | 'full_width'
    gap: 'no' | 'narrow' | 'default' | 'wide'
    height: 'default' | 'min-height' | 'fit-to-screen'
    backgroundColor?: string
    backgroundImage?: string
    padding?: { top, right, bottom, left }
    margin?: { top, bottom }
  }
}

Column {
  id: string
  width: number  // percentage
  widgets: Widget[]
}

Widget {
  id: string
  type: string
  data: any  // widget-specific data
}
```

## Troubleshooting

**Editor not loading:**
- Ensure frontend dev server is running (`npm run dev`)
- Check browser console for errors
- Verify page ID is correct in URL

**Widgets not dragging:**
- Make sure you're dragging onto the column area (blue drop zone appears)
- Try clicking the widget instead to add it

**Settings panel not appearing:**
- Click directly on the widget (not the section)
- Look for blue outline indicating selection

**Changes not saving:**
- Check "Unsaved Changes" indicator in toolbar
- Click "Save Draft" button
- Check browser console for API errors

**Section layout not preserved:**
- This is fixed! Sections now properly save multi-column layouts
- If you have old pages, they'll convert to 1-column sections (re-edit to fix)

## Browser Support

- Chrome/Edge (recommended)
- Firefox
- Safari
- Requires modern browser with ES6+ support

## Need Help?

- Check browser console for errors
- Verify backend API is running
- Ensure page has `template_type = 'builder'`
- Review Filament admin panel for page configuration

---

**Built with:** Nuxt 3 + Vue 3 + Tailwind CSS + TypeScript
