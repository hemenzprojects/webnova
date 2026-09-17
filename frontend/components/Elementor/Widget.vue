<template>
  <div
    draggable="true"
    @dragstart="handleDragStart"
    @dragend="handleDragEnd"
    @click.stop="$emit('select', { type: 'widget', id: widget.id, sectionId, columnId })"
    :class="[
      'relative group transition-all rounded cursor-move',
      isSelected ? 'ring-2 ring-blue-500 shadow-lg' : 'hover:ring-2 hover:ring-gray-300',
      isDragging ? 'opacity-50 scale-95' : 'opacity-100 scale-100'
    ]"
  >
    <!-- Widget Controls -->
    <div
      v-if="isSelected"
      class="absolute -top-8 left-0 right-0 z-10 flex items-center justify-between bg-gray-900 text-white px-2 py-1 rounded-t text-xs"
    >
      <span class="font-medium">{{ getWidgetLabel() }}</span>
      <div class="flex items-center space-x-1">
        <button
          @click.stop="$emit('remove', { sectionId, columnId, widgetId: widget.id })"
          class="p-1 hover:bg-red-600 rounded"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Widget Content -->
    <div class="p-2">
      <!-- Heading Widget -->
      <div v-if="widget.type === 'heading'" :class="getAlignmentClass(widget.data.alignment)">
        <component
          :is="widget.data.tag || 'h2'"
          contenteditable="true"
          @blur="handleInlineEdit"
          @input="handleInlineEdit"
          :class="getHeadingClass(widget.data.tag)"
          :style="getTypographyStyle(widget.data)"
          v-html="widget.data.text"
        />
      </div>

      <!-- Text Editor Widget -->
      <div v-else-if="widget.type === 'text_editor'" :class="getAlignmentClass(widget.data.alignment)">
        <div
          contenteditable="true"
          @blur="handleInlineEdit"
          @input="handleInlineEdit"
          class="prose max-w-none focus:outline-none focus:ring-2 focus:ring-blue-300 rounded p-2"
          v-html="widget.data.content"
        />
      </div>

      <!-- Image Widget -->
      <div v-else-if="widget.type === 'image'" :class="getAlignmentClass(widget.data.alignment)">
        <img
          v-if="widget.data.url"
          :src="widget.data.url"
          :alt="widget.data.alt"
          :style="{ width: widget.data.width ? `${widget.data.width.size}${widget.data.width.unit}` : '100%' }"
          class="max-w-full h-auto rounded"
        />
        <div v-else class="bg-gray-200 h-48 flex items-center justify-center rounded">
          <span class="text-gray-500 text-sm">No image selected</span>
        </div>
      </div>

      <!-- Button Widget -->
      <div v-else-if="widget.type === 'button'" :class="getAlignmentClass(widget.data.alignment)">
        <a
          :href="widget.data.url"
          :class="[
            'inline-block px-6 py-3 rounded-lg font-medium transition-colors',
            getButtonClass(widget.data.style),
            getSizeClass(widget.data.size)
          ]"
        >
          <span
            contenteditable="true"
            @blur="handleInlineEdit"
            @input="handleInlineEdit"
            class="focus:outline-none"
          >
            {{ widget.data.text }}
          </span>
        </a>
      </div>

      <!-- Spacer Widget -->
      <div v-else-if="widget.type === 'spacer'">
        <div
          :style="{ height: `${widget.data.space?.size || 50}${widget.data.space?.unit || 'px'}` }"
          class="bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center"
        >
          <span class="text-xs text-gray-400">Spacer: {{ widget.data.space?.size }}{{ widget.data.space?.unit }}</span>
        </div>
      </div>

      <!-- Divider Widget -->
      <div v-else-if="widget.type === 'divider'">
        <hr
          :class="[
            'my-4',
            widget.data.style === 'dashed' ? 'border-dashed' : '',
            widget.data.style === 'dotted' ? 'border-dotted' : ''
          ]"
          :style="{
            borderWidth: `${widget.data.weight?.size || 1}${widget.data.weight?.unit || 'px'}`,
            borderColor: widget.data.color || '#e0e0e0'
          }"
        />
      </div>

      <!-- Gallery Widget -->
      <PageBuilderGallery v-else-if="widget.type === 'gallery'" :data="widget.data" :block-id="widget.id" />

      <!-- Other Widgets (use existing PageBuilder components) -->
      <PageBuilderHero v-else-if="widget.type === 'hero'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderHeroSlider v-else-if="widget.type === 'hero_slider'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderHeroSplit v-else-if="widget.type === 'hero_split'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderHeroDynamic v-else-if="widget.type === 'hero_dynamic'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderStats v-else-if="widget.type === 'stats'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderCardGrid v-else-if="widget.type === 'card_grid'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderIconCards v-else-if="widget.type === 'icon_cards'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderWhoWeAre v-else-if="widget.type === 'who_we_are'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderTimeline v-else-if="widget.type === 'timeline'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderContactForm v-else-if="widget.type === 'contact_form'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderSearchBox v-else-if="widget.type === 'search_box'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderDynamicNews v-else-if="widget.type === 'dynamic_news'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderDynamicEvents v-else-if="widget.type === 'dynamic_events'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderDynamicServices v-else-if="widget.type === 'dynamic_services'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderDynamicMembers v-else-if="widget.type === 'dynamic_members'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderDynamicTeamMembers v-else-if="widget.type === 'dynamic_team_members'" :data="widget.data" :block-id="widget.id" />
      <PageBuilderDynamicCarousel v-else-if="widget.type === 'dynamic_carousel'" :data="widget.data" :block-id="widget.id" />

      <!-- Unknown Widget -->
      <div v-else class="p-4 bg-gray-100 rounded text-center">
        <span class="text-sm text-gray-600">{{ widget.type }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  widget: any
  sectionId: string
  columnId: string
  isSelected: boolean
}>()

const emit = defineEmits(['select', 'update', 'remove'])

const { widgetLibrary } = useElementorEditor()
const isDragging = ref(false)

const handleDragStart = (e: DragEvent) => {
  isDragging.value = true
  if (e.dataTransfer) {
    e.dataTransfer.setData('widgetId', props.widget.id)
    e.dataTransfer.setData('sourceSectionId', props.sectionId)
    e.dataTransfer.setData('sourceColumnId', props.columnId)
    e.dataTransfer.effectAllowed = 'move'
  }
}

const handleDragEnd = () => {
  isDragging.value = false
}

const getWidgetLabel = () => {
  const widgetDef = widgetLibrary.find(w => w.type === props.widget.type)
  return widgetDef?.label || props.widget.type
}

const handleInlineEdit = (e: Event) => {
  const target = e.target as HTMLElement
  const newContent = target.innerHTML

  if (props.widget.type === 'heading') {
    emit('update', {
      sectionId: props.sectionId,
      columnId: props.columnId,
      widgetId: props.widget.id,
      data: { text: newContent }
    })
  } else if (props.widget.type === 'text_editor') {
    emit('update', {
      sectionId: props.sectionId,
      columnId: props.columnId,
      widgetId: props.widget.id,
      data: { content: newContent }
    })
  } else if (props.widget.type === 'button') {
    emit('update', {
      sectionId: props.sectionId,
      columnId: props.columnId,
      widgetId: props.widget.id,
      data: { text: target.textContent }
    })
  }
}

// Utility functions
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
    h1: 'text-4xl font-bold',
    h2: 'text-3xl font-bold',
    h3: 'text-2xl font-semibold',
    h4: 'text-xl font-semibold',
    h5: 'text-lg font-semibold',
    h6: 'text-base font-semibold'
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
