import { ref, computed } from 'vue'

export interface ElementorWidget {
  id: string
  type: string
  data: any
}

export interface ElementorColumn {
  id: string
  width: number // percentage
  widgets: ElementorWidget[]
}

export interface ElementorSection {
  id: string
  type: 'section'
  columns: ElementorColumn[]
  settings: {
    layout: 'boxed' | 'full_width'
    gap: 'default' | 'narrow' | 'wide' | 'no'
    height: 'default' | 'min-height' | 'fit-to-screen'
    backgroundColor?: string
    backgroundImage?: string
    padding?: { top: number; right: number; bottom: number; left: number }
    margin?: { top: number; bottom: number }
  }
}

export const useElementorEditor = () => {
  const sections = ref<ElementorSection[]>([])
  const selectedElement = ref<{ type: 'section' | 'column' | 'widget'; id: string } | null>(null)
  const isDragging = ref(false)
  const isSaving = ref(false)
  const hasChanges = ref(false)

  const widgetLibrary = [
    // Basic Widgets
    { type: 'heading', label: 'Heading', icon: 'title', category: 'basic' },
    { type: 'text_editor', label: 'Text Editor', icon: 'text_fields', category: 'basic' },
    { type: 'image', label: 'Image', icon: 'image', category: 'basic' },
    { type: 'button', label: 'Button', icon: 'smart_button', category: 'basic' },
    { type: 'spacer', label: 'Spacer', icon: 'space_bar', category: 'basic' },
    { type: 'divider', label: 'Divider', icon: 'horizontal_rule', category: 'basic' },
    { type: 'gallery', label: 'Gallery', icon: 'collections', category: 'basic' },

    // Section Widgets
    { type: 'hero', label: 'Hero Section', icon: 'wallpaper', category: 'sections' },
    { type: 'hero_slider', label: 'Hero Slider', icon: 'view_carousel', category: 'sections' },
    { type: 'hero_split', label: 'Hero Split', icon: 'view_column', category: 'sections' },
    { type: 'hero_dynamic', label: 'Hero Dynamic', icon: 'dashboard', category: 'sections' },
    { type: 'stats', label: 'Counter', icon: 'analytics', category: 'sections' },
    { type: 'card_grid', label: 'Card Grid', icon: 'grid_view', category: 'sections' },
    { type: 'icon_cards', label: 'Icon Cards', icon: 'dashboard_customize', category: 'sections' },
    { type: 'who_we_are', label: 'Who We Are', icon: 'info', category: 'sections' },
    { type: 'timeline', label: 'Timeline', icon: 'timeline', category: 'sections' },

    // Form Widgets
    { type: 'contact_form', label: 'Form', icon: 'contact_mail', category: 'forms' },
    { type: 'search_box', label: 'Search', icon: 'search', category: 'forms' },

    // Dynamic Widgets
    { type: 'dynamic_news', label: 'Posts', icon: 'article', category: 'dynamic' },
    { type: 'dynamic_events', label: 'Events', icon: 'event', category: 'dynamic' },
    { type: 'dynamic_services', label: 'Services', icon: 'miscellaneous_services', category: 'dynamic' },
    { type: 'dynamic_members', label: 'Members', icon: 'groups', category: 'dynamic' },
    { type: 'dynamic_team_members', label: 'Team', icon: 'people', category: 'dynamic' },
    { type: 'dynamic_carousel', label: 'Carousel', icon: 'view_carousel', category: 'dynamic' },
  ]

  const getDefaultWidgetData = (type: string): any => {
    const defaults: Record<string, any> = {
      heading: {
        text: 'Your Heading',
        tag: 'h2',
        alignment: 'left',
        color: '#000000',
        typography: { size: 32, weight: 600 }
      },
      text_editor: {
        content: '<p>Start typing...</p>',
        alignment: 'left'
      },
      image: {
        url: '',
        alt: '',
        alignment: 'center',
        width: { size: 100, unit: '%' }
      },
      button: {
        text: 'Click Here',
        url: '#',
        alignment: 'left',
        size: 'md',
        style: 'primary'
      },
      spacer: {
        space: { size: 50, unit: 'px' }
      },
      divider: {
        style: 'solid',
        weight: { size: 1, unit: 'px' },
        color: '#e0e0e0'
      },
      gallery: {
        title: 'Our Gallery',
        titleColor: '#1e293b',
        description: 'Browse our latest work and achievements',
        descriptionColor: '#64748b',
        images: [],
        columns: 3,
        gap: 4,
        aspectRatio: 'square',
        layout: 'grid'
      },
      hero: {
        heading: 'Welcome to Our Website',
        subheading: 'Discover amazing content and services',
        backgroundImage: '',
        foregroundImage: '',
        overlay: 'gradient',
        ctaText: 'Get Started',
        ctaLink: '#',
        height: 'large',
        showParticles: true
      },
      hero_slider: {
        slides: [
          {
            backgroundImage: '',
            backgroundOverlay: 'gradient',
            preHeading: 'Welcome to',
            heading: 'Our Platform',
            headingColor: '#1e293b',
            subheading: 'Discover amazing features and services designed to help you succeed.',
            subheadingColor: '#64748b',
            buttonText: 'Get Started',
            buttonLink: '#',
            secondaryButtonText: 'Learn More',
            secondaryButtonLink: '#',
            showFeaturedCard: true,
            featuredTitle: 'Innovation At Your Fingertips',
            featuredDescription: '',
            featuredImage: '',
            featuredImageMode: 'background',
            contentAlign: 'left'
          }
        ],
        autoplay: true,
        interval: 6,
        showIndicators: true,
        showArrows: true,
        height: 'large',
        transition: 'fade',
        transitionSpeed: 700
      },
      hero_split: {
        headingLine1: 'Welcome to',
        headingLine1Color: 'primary',
        headingLine2: 'Our Platform',
        headingLine2Color: 'purple',
        headingLine3: '',
        headingLine3Color: 'primary',
        subheading: 'Discover amazing features and services designed to help you succeed.',
        primaryCtaText: 'Get Started',
        primaryCtaLink: '#',
        secondaryCtaText: 'Learn More',
        secondaryCtaLink: '#',
        showFeatureBox: true,
        featureBoxTopText: 'FEATURED',
        featureBoxMainText: 'Innovation',
        featureBoxBottomText: 'At Your Fingertips',
        featureBoxColor: 'teal',
        featureBoxShape: 'rounded',
        backgroundImage: '',
        height: 'large',
        customHeight: 700,
        showDecorations: true
      },
      hero_dynamic: {
        topBadge: 'SAFETY & TRUST',
        topBadgeColor: '#14b8a6',
        topBadgeBgColor: 'rgba(20, 184, 166, 0.1)',
        headingLine1: 'Safety that moves',
        headingLine1Color: '#ffffff',
        headingLine2: 'at city speed.',
        headingLine2Color: '#14b8a6',
        headingLine3: '',
        headingLine3Color: 'primary',
        subheading: 'We are committed to upholding high standards and safety regulations designed to protect our customers and communities at every step.',
        primaryCtaText: 'Get Started',
        primaryCtaLink: '#',
        primaryCtaStyle: 'filled',
        primaryCtaColor: '#ffffff',
        primaryCtaBgColor: '#14b8a6',
        secondaryCtaText: 'Learn More',
        secondaryCtaLink: '#',
        secondaryCtaStyle: 'outline',
        secondaryCtaColor: '#ffffff',
        secondaryCtaBgColor: 'transparent',
        bottomBadges: ['Verified drivers', 'Live trip tracking', '24/7 support'],
        bottomBadgeTextColor: '#ffffff',
        bottomBadgeBgColor: 'rgba(255, 255, 255, 0.1)',
        rightContentType: 'cards',
        infoCards: [
          {
            header: 'LIVE TRIP STATUS',
            title: 'Active',
            description: 'Driver ETA: 3 min',
            progress: 85,
            progressLabel: 'Driver ETA',
            progressValue: '3 min',
            progressColor: '#14b8a6',
            cardBackgroundColor: 'rgba(255, 255, 255, 0.05)',
            cardBorderColor: 'rgba(255, 255, 255, 0.1)',
            headerTextColor: '#9ca3af',
            titleTextColor: '#ffffff',
            descriptionTextColor: '#d1d5db'
          },
          {
            header: 'SAFETY CHECK-IN',
            title: 'All good',
            description: 'We check rides in real time for unusual route changes and unexpected stops.',
            cardBackgroundColor: 'rgba(255, 255, 255, 0.05)',
            cardBorderColor: 'rgba(255, 255, 255, 0.1)',
            headerTextColor: '#9ca3af',
            titleTextColor: '#ffffff',
            descriptionTextColor: '#d1d5db'
          },
          {
            header: 'EMERGENCY ASSIST',
            title: 'One tap',
            description: 'Reach local emergency services and share trip details instantly.',
            cardBackgroundColor: 'rgba(255, 255, 255, 0.05)',
            cardBorderColor: 'rgba(255, 255, 255, 0.1)',
            headerTextColor: '#9ca3af',
            titleTextColor: '#ffffff',
            descriptionTextColor: '#d1d5db'
          }
        ],
        featureBoxTopText: 'FEATURE',
        featureBoxMainText: 'MAIN TEXT',
        featureBoxBottomText: 'TAGLINE',
        featureBoxColor: 'teal',
        circleImage: '',
        showCircleImage: true,
        circleImageSize: 'medium',
        showHoverOverlay: true,
        hoverOverlayColor: 'rgba(147, 51, 234, 0.85)',
        hoverText: '',
        backgroundImage: '',
        height: 'large',
        customHeight: 600,
        showDecorations: true,
        overlayColor: '#000000',
        overlayOpacity: 0.5
      },
      stats: {
        heading: 'Our Achievements',
        items: [
          { value: '100', suffix: '+', label: 'Projects Completed' },
          { value: '50', suffix: '+', label: 'Happy Clients' },
          { value: '10', suffix: '', label: 'Years Experience' },
          { value: '25', suffix: '+', label: 'Team Members' }
        ],
        columns: '4',
        animate: true
      },
      card_grid: {
        heading: 'Our Services',
        cards: [
          { image: '', title: 'Service 1', description: 'Description of service 1', link: '' },
          { image: '', title: 'Service 2', description: 'Description of service 2', link: '' },
          { image: '', title: 'Service 3', description: 'Description of service 3', link: '' }
        ],
        columns: '3'
      },
      icon_cards: {
        heading: 'Our Mission & Vision',
        subheading: '',
        cards: [
          {
            icon: 'rocket',
            iconColor: '#3B82F6',
            borderColor: '#3B82F6',
            title: 'Our Mission',
            description: 'To facilitate the development of a robust and sustainable national research and education network that connects Ghanaian tertiary and research institutions to each other and to the rest of the world.',
            link: '',
            linkText: ''
          },
          {
            icon: 'eye',
            iconColor: '#9333EA',
            borderColor: '#9333EA',
            title: 'Our Vision',
            description: 'To be the catalyst for digital transformation in Ghana\'s academic landscape, empowering researchers and students with high-speed connectivity and collaborative tools.',
            link: '',
            linkText: ''
          }
        ],
        columns: '2'
      },
      who_we_are: {
        heading: 'Who We Are',
        headingColor: '#1E40AF',
        description: 'WEBNOVA is governed by a Board of Trustees representing our diverse member institutions. We operate on a collaborative model where technical expertise is shared across the network to benefit all.',
        featureTitleColor: '#1E40AF',
        features: [
          {
            icon: 'users',
            iconColor: '#1E40AF',
            iconBgColor: '#EBF5FF',
            title: 'Member-Driven',
            description: 'Decisions are made collectively for the advancement of all Ghanaian scholars.'
          },
          {
            icon: 'shield',
            iconColor: '#1E40AF',
            iconBgColor: '#EBF5FF',
            title: 'Trusted Authority',
            description: 'The sole recognized NREN by the Ministry of Education and international bodies.'
          }
        ],
        statCards: [
          {
            label: 'Research',
            tagBox: 'RESEARCH/EDUCATION/COLLABORATION',
            mainText: '',
            subtitle: '',
            description: '',
            backgroundColor: '#2F5B5B',
            backgroundImage: '',
            showDecorations: true,
            textEffect: 'normal',
            size: 'large-tall'
          },
          {
            label: '',
            tagBox: '',
            mainText: '10Gbps',
            subtitle: 'BACKBONE SPEED',
            description: '',
            backgroundColor: '#3A5BA6',
            backgroundImage: '',
            showDecorations: false,
            textEffect: 'normal',
            size: 'normal'
          },
          {
            label: '',
            tagBox: '',
            mainText: '40+',
            subtitle: 'INSTITUTIONS',
            description: '',
            backgroundColor: '#7C2D8E',
            backgroundImage: '',
            showDecorations: false,
            textEffect: 'normal',
            size: 'normal'
          },
          {
            label: '',
            tagBox: '',
            mainText: 'TECHNICAL TEAM',
            subtitle: '',
            description: '',
            backgroundColor: '#2F5B5B',
            backgroundImage: '',
            showDecorations: false,
            textEffect: 'glitch',
            size: 'tall'
          }
        ]
      },
      timeline: {
        heading: 'Our History',
        headingColor: '#1E40AF',
        items: [
          {
            year: '2006',
            yearColor: '#1E40AF',
            title: 'The Foundation',
            description: 'The Ghanaian Academic and Research Network (WEBNOVA) was formally incorporated as a non-profit organization, bringing together major public universities to explore shared digital infrastructure.',
            backgroundColor: '#E5E7EB',
            size: 'normal',
            icon: 'none',
            showIcon: true
          },
          {
            year: '2012',
            yearColor: '#9333EA',
            title: 'WACREN Integration',
            description: 'WEBNOVA officially joined the West and Central African Research and Education Network (WACREN) as a founding member.',
            backgroundColor: '#FFFFFF',
            size: 'normal',
            icon: 'none',
            showIcon: false
          },
          {
            year: '2018',
            yearColor: '#1E40AF',
            title: 'Network Core',
            description: 'Successful deployment of the first dedicated high-speed fiber backbone connecting Kumasi, Accra, and Cape Coast.',
            backgroundColor: '#FFFFFF',
            size: 'normal',
            icon: 'none',
            showIcon: false
          },
          {
            year: 'Today',
            yearColor: '#FFFFFF',
            title: 'Leading Digital Transformation',
            description: 'Serving over 40 member institutions, WEBNOVA provides not just bandwidth, but advanced identity management (eduroam), cloud services, and shared research repositories.',
            backgroundColor: '#1E40AF',
            size: 'full',
            icon: 'globe',
            showIcon: false
          }
        ]
      },
      contact_form: {
        heading: 'Get In Touch',
        description: 'Fill out the form below and we will get back to you as soon as possible.',
        submitText: 'Send Message',
        emailTo: 'info@example.com',
        showPhone: true,
        showCompany: true
      },
      search_box: {
        placeholder: 'Search...',
        searchScope: 'all',
        size: 'md'
      },
      dynamic_news: {
        heading: 'Latest News',
        subheading: 'Stay updated with our latest articles and announcements',
        items: [],
        limit: 6,
        layout: 'grid',
        columns: '3',
        showImage: true,
        showExcerpt: true,
        showDate: true,
        showReadMore: true
      },
      dynamic_events: {
        heading: 'Upcoming Events',
        items: [],
        limit: 6,
        layout: 'grid',
        filter: 'upcoming',
        showLocation: true
      },
      dynamic_services: {
        heading: 'Our Services',
        items: [],
        limit: 6,
        layout: 'grid',
        columns: '3',
        showIcons: true,
        showDescription: true
      },
      dynamic_members: {
        heading: 'Our Members',
        items: [],
        limit: 12,
        display: 'grid',
        columns: '4',
        showLogo: true,
        showDescription: false
      },
      dynamic_team_members: {
        heading: 'Meet our professional and expert team members',
        subheading: 'There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form',
        items: [],
        limit: 4,
        layout: 'grid',
        columns: '4',
        showPhoto: true,
        showBio: false,
        showEmail: false,
        showSocialLinks: false
      },
      dynamic_carousel: {
        heading: 'Featured Content',
        subheading: '',
        contentType: 'members',
        items: [],
        limit: 12,
        itemsPerView: 4,
        autoplay: true,
        autoplayDelay: 3000,
        showNavigation: true,
        showPagination: true,
        navigationColor: '#0ea5e9',
        navigationBgColor: '#ffffff',
        navigationHoverColor: '#0ea5e9',
        paginationColor: '#cbd5e1',
        paginationActiveColor: '#0ea5e9'
      }
    }
    return defaults[type] || {}
  }

  const addSection = (columnWidths: number[] = [100]) => {
    const columns: ElementorColumn[] = []

    columnWidths.forEach((width, i) => {
      columns.push({
        id: `column-${Date.now()}-${i}`,
        width,
        widgets: []
      })
    })

    const newSection: ElementorSection = {
      id: `section-${Date.now()}`,
      type: 'section',
      columns,
      settings: {
        layout: 'boxed',
        gap: 'default',
        height: 'default'
      }
    }

    sections.value.push(newSection)
    hasChanges.value = true
    return newSection
  }

  const addWidget = (sectionId: string, columnId: string, widgetType: string) => {
    const section = sections.value.find(s => s.id === sectionId)
    if (!section) return null

    const column = section.columns.find(c => c.id === columnId)
    if (!column) return null

    const newWidget: ElementorWidget = {
      id: `widget-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
      type: widgetType,
      data: getDefaultWidgetData(widgetType)
    }

    column.widgets.push(newWidget)
    hasChanges.value = true
    return newWidget
  }

  const removeSection = (sectionId: string) => {
    const index = sections.value.findIndex(s => s.id === sectionId)
    if (index > -1) {
      sections.value.splice(index, 1)
      hasChanges.value = true
      if (selectedElement.value?.id === sectionId) {
        selectedElement.value = null
      }
    }
  }

  const removeWidget = (sectionId: string, columnId: string, widgetId: string) => {
    const section = sections.value.find(s => s.id === sectionId)
    if (!section) return

    const column = section.columns.find(c => c.id === columnId)
    if (!column) return

    const index = column.widgets.findIndex(w => w.id === widgetId)
    if (index > -1) {
      column.widgets.splice(index, 1)
      hasChanges.value = true
      if (selectedElement.value?.id === widgetId) {
        selectedElement.value = null
      }
    }
  }

  const moveWidget = (params: {
    widgetId: string
    fromSectionId: string
    fromColumnId: string
    toSectionId: string
    toColumnId: string
  }) => {
    // Find and remove from source
    const sourceSection = sections.value.find(s => s.id === params.fromSectionId)
    const sourceColumn = sourceSection?.columns.find(c => c.id === params.fromColumnId)
    const widgetIndex = sourceColumn?.widgets.findIndex(w => w.id === params.widgetId)

    if (widgetIndex === undefined || widgetIndex === -1 || !sourceColumn) return

    const widget = sourceColumn.widgets.splice(widgetIndex, 1)[0]

    // Add to target
    const targetSection = sections.value.find(s => s.id === params.toSectionId)
    const targetColumn = targetSection?.columns.find(c => c.id === params.toColumnId)

    if (targetColumn) {
      targetColumn.widgets.push(widget)
      hasChanges.value = true
    }
  }

  const duplicateSection = (sectionId: string) => {
    const section = sections.value.find(s => s.id === sectionId)
    if (!section) return

    const newSection = JSON.parse(JSON.stringify(section))
    newSection.id = `section-${Date.now()}`
    newSection.columns.forEach((col: ElementorColumn, i: number) => {
      col.id = `column-${Date.now()}-${i}`
      col.widgets.forEach((widget: ElementorWidget) => {
        widget.id = `widget-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
      })
    })

    const index = sections.value.findIndex(s => s.id === sectionId)
    sections.value.splice(index + 1, 0, newSection)
    hasChanges.value = true
  }

  const updateWidgetData = (sectionId: string, columnId: string, widgetId: string, data: any) => {
    const section = sections.value.find(s => s.id === sectionId)
    if (!section) return

    const column = section.columns.find(c => c.id === columnId)
    if (!column) return

    const widget = column.widgets.find(w => w.id === widgetId)
    if (!widget) return

    widget.data = { ...widget.data, ...data }
    hasChanges.value = true
  }

  const updateSectionSettings = (sectionId: string, settings: any) => {
    const section = sections.value.find(s => s.id === sectionId)
    if (!section) return

    section.settings = { ...section.settings, ...settings }
    hasChanges.value = true
  }

  const moveSection = (fromIndex: number, toIndex: number) => {
    const section = sections.value.splice(fromIndex, 1)[0]
    sections.value.splice(toIndex, 0, section)
    hasChanges.value = true
  }

  const selectElement = (type: 'section' | 'column' | 'widget', id: string) => {
    selectedElement.value = { type, id }
  }

  const loadFromBlocks = (blocks: any[]) => {
    // Convert blocks back to section/column format
    sections.value = []
    let currentSection: ElementorSection | null = null

    blocks.forEach(block => {
      if (block.type === '_section_meta') {
        // Create section with proper column structure
        currentSection = {
          id: block.id,
          type: 'section',
          columns: block.data.columns.map((c: any) => ({
            id: c.id,
            width: c.width,
            widgets: []
          })),
          settings: block.data.settings || {
            layout: 'boxed',
            gap: 'default',
            height: 'default'
          }
        }
        sections.value.push(currentSection)
      } else if (block._sectionId && currentSection) {
        // Add widget to correct column
        const column = currentSection.columns.find(c => c.id === block._columnId)
        if (column) {
          column.widgets.push({
            id: block.id,
            type: block.type,
            data: block.data
          })
        }
      } else if (block.type !== '_section_meta') {
        // Fallback for old format: create 1-column section for each block
        const section = addSection([100])
        if (section) {
          section.columns[0].widgets.push({
            id: block.id || `widget-${Date.now()}`,
            type: block.type,
            data: block.data
          })
        }
      }
    })

    hasChanges.value = false
  }

  const exportToBlocks = () => {
    // Convert section/column format back to flat blocks for backend
    const blocks: any[] = []

    sections.value.forEach((section) => {
      // Add section metadata as special block
      blocks.push({
        id: section.id,
        type: '_section_meta',
        data: {
          columns: section.columns.map(c => ({ id: c.id, width: c.width })),
          settings: section.settings
        },
        order: blocks.length
      })

      // Add widgets with column reference
      section.columns.forEach(column => {
        column.widgets.forEach(widget => {
          const block = {
            id: widget.id,
            type: widget.type,
            data: widget.data,
            _columnId: column.id,
            _sectionId: section.id,
            order: blocks.length
          }

          // Debug: Log if widget type is missing or looks like an ID
          if (!block.type || block.type.startsWith('widget-') || block.type.startsWith('column-')) {
            console.error('Invalid widget type detected:', {
              widgetId: widget.id,
              type: widget.type,
              widget: widget
            })
          }

          blocks.push(block)
        })
      })
    })

    console.log('Exporting blocks:', blocks)
    return blocks
  }

  return {
    sections,
    selectedElement,
    isDragging,
    isSaving,
    hasChanges,
    widgetLibrary,
    addSection,
    addWidget,
    removeSection,
    removeWidget,
    moveWidget,
    duplicateSection,
    updateWidgetData,
    updateSectionSettings,
    moveSection,
    selectElement,
    loadFromBlocks,
    exportToBlocks,
    getDefaultWidgetData
  }
}
