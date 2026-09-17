<template>
  <div
    :data-section-id="section.id"
    @click.stop="$emit('select', { type: 'section', id: section.id })"
    :class="[
      'relative group transition-all',
      isSelected ? 'ring-2 ring-blue-500' : 'hover:ring-2 hover:ring-gray-300'
    ]"
  >
    <!-- Section Controls Overlay -->
    <div
      v-if="isSelected || isHovered"
      @mouseenter="isHovered = true"
      @mouseleave="isHovered = false"
      class="absolute -top-10 left-0 right-0 z-20 flex items-center justify-between bg-blue-600 text-white px-3 py-1.5 rounded-t-lg"
    >
      <div class="flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z" />
        </svg>
        <span class="text-xs font-medium">Section</span>
      </div>

      <div class="flex items-center space-x-1">
        <button
          @click.stop="$emit('move-up')"
          class="p-1 hover:bg-blue-700 rounded transition-colors"
          title="Move Up"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
          </svg>
        </button>
        <button
          @click.stop="$emit('move-down')"
          class="p-1 hover:bg-blue-700 rounded transition-colors"
          title="Move Down"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <button
          @click.stop="$emit('duplicate')"
          class="p-1 hover:bg-blue-700 rounded transition-colors"
          title="Duplicate"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
        </button>
        <button
          @click.stop="$emit('delete')"
          class="p-1 hover:bg-red-600 rounded transition-colors"
          title="Delete"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Section Content -->
    <div
      :class="[
        'py-8',
        section.settings.layout === 'boxed' ? 'container mx-auto px-4' : 'w-full px-4'
      ]"
      :style="getSectionStyle()"
    >
      <!-- Columns -->
      <div
        :class="[
          'flex gap-4',
          section.settings.gap === 'no' ? 'gap-0' : '',
          section.settings.gap === 'narrow' ? 'gap-2' : '',
          section.settings.gap === 'wide' ? 'gap-8' : ''
        ]"
      >
        <ElementorColumn
          v-for="column in section.columns"
          :key="column.id"
          :column="column"
          :section-id="section.id"
          :selected-element="selectedElement"
          :is-selected="selectedElement?.type === 'column' && selectedElement?.id === column.id"
          @select="$emit('select', $event)"
          @add-widget="$emit('add-widget', $event)"
          @move-widget="$emit('move-widget', $event)"
          @select-widget="$emit('select-widget', $event)"
          @update-widget="$emit('update-widget', $event)"
          @remove-widget="$emit('remove-widget', $event)"
        />
      </div>

      <!-- Empty Section Message -->
      <div
        v-if="section.columns.every(c => c.widgets.length === 0)"
        class="text-center py-12 border-2 border-dashed border-gray-300 rounded-lg"
      >
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        <p class="text-sm text-gray-500">Drag widgets here</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  section: any
  selectedElement: { type: string; id: string } | null
}>()

const emit = defineEmits(['select', 'move-up', 'move-down', 'duplicate', 'delete', 'add-widget', 'move-widget', 'select-widget', 'update-widget', 'remove-widget'])

const isHovered = ref(false)

const isSelected = computed(() => {
  return props.selectedElement?.type === 'section' && props.selectedElement?.id === props.section.id
})

const getSectionStyle = () => {
  const settings = props.section.settings
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

  if (settings.height === 'fit-to-screen') {
    style.minHeight = '100vh'
  } else if (settings.height === 'min-height') {
    style.minHeight = '400px'
  }

  return style
}
</script>
