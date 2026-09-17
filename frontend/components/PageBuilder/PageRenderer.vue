<template>
  <div class="page-builder-content">
    <template v-for="(section, sectionIndex) in groupedSections" :key="section.id || `section-${sectionIndex}`">
      <!-- Section Wrapper with Background -->
      <div :style="getSectionStyle(section.settings)" class="elementor-section">
        <!-- If section has columns, render in column layout -->
        <div
          v-if="section.columns && section.columns.length > 0"
          :class="[
            'flex gap-4',
            section.settings?.layout === 'boxed' ? 'container mx-auto px-4' : 'w-full'
          ]"
        >
          <div
            v-for="(column, colIndex) in section.columns"
            :key="column.id || `column-${colIndex}`"
            :style="{
              flexBasis: `${column.width}%`,
              flexGrow: 0,
              flexShrink: 0
            }"
            class="space-y-4"
          >
            <template v-for="(block, blockIndex) in column.blocks" :key="block.id || `block-${blockIndex}`">
              <!-- Heading Widget - Inline version for columns -->
              <component
                v-if="block.type === 'heading'"
                :is="block.data.tag || 'h2'"
                :class="[
                  getHeadingClass(block.data.tag || 'h2'),
                  getAlignmentClass(block.data.alignment)
                ]"
                :style="getTypographyStyle(block.data)"
                v-html="block.data.text"
              />

              <!-- Text Editor Widget - Inline version for columns -->
              <div
                v-else-if="block.type === 'text_editor' || block.type === 'text_block'"
                :class="['prose max-w-none', getAlignmentClass(block.data.alignment)]"
                v-html="block.data.content"
              />

              <!-- Image Widget - Inline version for columns -->
              <img
                v-else-if="block.type === 'image'"
                :src="block.data.url"
                :alt="block.data.alt"
                :class="['max-w-full h-auto', getAlignmentClass(block.data.alignment)]"
                :style="{ width: block.data.width ? `${block.data.width.size}${block.data.width.unit}` : '100%' }"
              />

              <!-- Button Widget - Inline version for columns -->
              <div v-else-if="block.type === 'button'" :class="getAlignmentClass(block.data.alignment)">
                <a
                  :href="block.data.url"
                  :class="[
                    'inline-block px-6 py-3 rounded-lg font-medium transition-colors',
                    getButtonClass(block.data.style),
                    getSizeClass(block.data.size)
                  ]"
                >
                  {{ block.data.text }}
                </a>
              </div>

              <!-- Spacer Widget - Inline version for columns -->
              <div
                v-else-if="block.type === 'spacer'"
                :style="{ height: `${block.data.space?.size || 50}${block.data.space?.unit || 'px'}` }"
              />

              <!-- Divider Widget - Inline version for columns -->
              <hr
                v-else-if="block.type === 'divider'"
                :class="[
                  'my-4',
                  block.data.style === 'dashed' ? 'border-dashed' : '',
                  block.data.style === 'dotted' ? 'border-dotted' : ''
                ]"
                :style="{
                  borderWidth: `${block.data.weight?.size || 1}${block.data.weight?.unit || 'px'}`,
                  borderColor: block.data.color || '#e0e0e0'
                }"
              />

              <!-- Carousel Block -->
              <PageBuilderCarousel v-else-if="block.type === 'carousel'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />

              <!-- Gallery Block -->
              <PageBuilderGallery v-else-if="block.type === 'gallery'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />

              <!-- Section Blocks -->
              <PageBuilderHero v-else-if="block.type === 'hero'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderHeroSlider v-else-if="block.type === 'hero_slider'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderHeroSplit v-else-if="block.type === 'hero_split'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderHeroDynamic v-else-if="block.type === 'hero_dynamic'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderStats v-else-if="block.type === 'stats'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderTwoColumn v-else-if="block.type === 'two_column'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderCardGrid v-else-if="block.type === 'card_grid'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderIconCards v-else-if="block.type === 'icon_cards'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderWhoWeAre v-else-if="block.type === 'who_we_are'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderTimeline v-else-if="block.type === 'timeline'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />

              <!-- Form Blocks -->
              <PageBuilderContactForm v-else-if="block.type === 'contact_form'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderSearchBox v-else-if="block.type === 'search_box'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />

              <!-- Dynamic Blocks -->
              <PageBuilderDynamicNews v-else-if="block.type === 'dynamic_news'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderDynamicEvents v-else-if="block.type === 'dynamic_events'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderDynamicServices v-else-if="block.type === 'dynamic_services'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderDynamicMembers v-else-if="block.type === 'dynamic_members'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderDynamicTeamMembers v-else-if="block.type === 'dynamic_team_members'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
              <PageBuilderDynamicCarousel v-else-if="block.type === 'dynamic_carousel'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />

              <!-- Unknown Block -->
              <PageBuilderUnknown v-else :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            </template>
          </div>
        </div>

        <!-- If no columns, render blocks sequentially (backwards compatibility) -->
        <template v-else>
          <template v-for="(block, blockIndex) in section.blocks" :key="block.id || `block-${blockIndex}`">
            <!-- Basic Blocks -->
            <PageBuilderTextBlock v-if="block.type === 'text_block'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderHeading v-else-if="block.type === 'heading'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderImage v-else-if="block.type === 'image'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderButton v-else-if="block.type === 'button'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderSpacer v-else-if="block.type === 'spacer'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderDivider v-else-if="block.type === 'divider'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />

            <!-- Gallery Block -->
            <PageBuilderGallery v-else-if="block.type === 'gallery'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />

            <!-- Section Blocks -->
            <PageBuilderHero v-else-if="block.type === 'hero'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderHeroSlider v-else-if="block.type === 'hero_slider'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderHeroSplit v-else-if="block.type === 'hero_split'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderHeroDynamic v-else-if="block.type === 'hero_dynamic'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderStats v-else-if="block.type === 'stats'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderTwoColumn v-else-if="block.type === 'two_column'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderCardGrid v-else-if="block.type === 'card_grid'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderIconCards v-else-if="block.type === 'icon_cards'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderWhoWeAre v-else-if="block.type === 'who_we_are'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderTimeline v-else-if="block.type === 'timeline'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />

            <!-- Form Blocks -->
            <PageBuilderContactForm v-else-if="block.type === 'contact_form'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderSearchBox v-else-if="block.type === 'search_box'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />

            <!-- Dynamic Blocks -->
            <PageBuilderDynamicNews v-else-if="block.type === 'dynamic_news'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderDynamicEvents v-else-if="block.type === 'dynamic_events'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderDynamicServices v-else-if="block.type === 'dynamic_services'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderDynamicMembers v-else-if="block.type === 'dynamic_members'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderDynamicTeamMembers v-else-if="block.type === 'dynamic_team_members'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
            <PageBuilderDynamicCarousel v-else-if="block.type === 'dynamic_carousel'" :data="block.data" :block-id="block.id || `block-${blockIndex}`" />

            <!-- Unknown Block -->
            <PageBuilderUnknown v-else :data="block.data" :block-id="block.id || `block-${blockIndex}`" />
          </template>
        </template>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  blocks: any[]
}>()

const { sortBlocks } = usePageBuilder()

const groupedSections = computed(() => {
  const blocks = sortBlocks(props.blocks || [])
  const sections: any[] = []
  let currentSection: any = null

  blocks.forEach((block) => {
    // Skip invalid blocks
    if (!block || !block.type) {
      console.warn('Invalid block found:', block)
      return
    }

    if (block.type === '_section_meta') {
      // Start a new section with column structure
      const columnDefs = block.data?.columns || []
      currentSection = {
        id: block.id,
        settings: block.data?.settings || {},
        blocks: [],
        columns: columnDefs.length > 0 ? columnDefs.map((col: any) => ({
          id: col.id,
          width: col.width,
          blocks: []
        })) : null
      }
      sections.push(currentSection)
    } else {
      // Add block to current section or create default section
      if (!currentSection) {
        currentSection = {
          id: 'default',
          settings: {},
          blocks: [],
          columns: null
        }
        sections.push(currentSection)
      }

      // If section has columns, add block to appropriate column
      if (currentSection.columns && block._columnId) {
        const column = currentSection.columns.find((col: any) => col.id === block._columnId)
        if (column) {
          column.blocks.push(block)
        } else {
          // Fallback: add to first column if column not found
          console.warn(`Column ${block._columnId} not found for block ${block.id}, adding to first column`)
          currentSection.columns[0]?.blocks.push(block)
        }
      } else {
        // No columns or no column ID, add to section blocks
        currentSection.blocks.push(block)
      }
    }
  })

  return sections
})

const getSectionStyle = (settings: any) => {
  const style: any = {}

  if (settings.backgroundColor) {
    style.backgroundColor = settings.backgroundColor
  }

  if (settings.backgroundImage) {
    style.backgroundImage = `url(${settings.backgroundImage})`
    style.backgroundSize = 'cover'
    style.backgroundPosition = 'center'
  }

  if (settings.padding) {
    style.paddingTop = `${settings.padding.top}px`
    style.paddingRight = `${settings.padding.right}px`
    style.paddingBottom = `${settings.padding.bottom}px`
    style.paddingLeft = `${settings.padding.left}px`
  }

  if (settings.margin) {
    style.marginTop = `${settings.margin.top}px`
    style.marginBottom = `${settings.margin.bottom}px`
  }

  return style
}

// Utility functions for inline widget rendering
const getAlignmentClass = (alignment: string) => {
  const classes: Record<string, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
    justify: 'text-justify'
  }
  return classes[alignment] || 'text-left'
}

const getHeadingClass = (tag: string) => {
  const classes: Record<string, string> = {
    h1: 'text-4xl font-bold mb-4',
    h2: 'text-3xl font-bold mb-3',
    h3: 'text-2xl font-semibold mb-3',
    h4: 'text-xl font-semibold mb-2',
    h5: 'text-lg font-semibold mb-2',
    h6: 'text-base font-semibold mb-2'
  }
  return classes[tag] || classes.h2
}

const getTypographyStyle = (data: any) => {
  const style: any = {}

  if (data.typography?.size) {
    style.fontSize = `${data.typography.size}px`
  }

  if (data.typography?.weight) {
    style.fontWeight = data.typography.weight
  }

  if (data.color) {
    style.color = data.color
  }

  return style
}

const getButtonClass = (buttonStyle: string) => {
  const classes: Record<string, string> = {
    primary: 'bg-blue-600 text-white hover:bg-blue-700',
    secondary: 'bg-gray-600 text-white hover:bg-gray-700',
    accent: 'bg-green-600 text-white hover:bg-green-700',
    outline: 'border-2 border-blue-600 text-blue-600 hover:bg-blue-50'
  }
  return classes[buttonStyle] || classes.primary
}

const getSizeClass = (size: string) => {
  const classes: Record<string, string> = {
    sm: 'text-sm px-4 py-2',
    md: 'text-base px-6 py-3',
    lg: 'text-lg px-8 py-4'
  }
  return classes[size] || classes.md
}
</script>
