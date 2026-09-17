<template>
  <div
    :data-column-id="column.id"
    @click.stop="$emit('select', { type: 'column', id: column.id })"
    @dragover.prevent="handleDragOver"
    @dragleave="isDragOver = false"
    @drop="handleDrop"
    :class="[
      'relative min-h-[100px] transition-all',
      isSelected ? 'ring-2 ring-green-500' : '',
      isDragOver ? 'ring-2 ring-blue-400 bg-blue-50' : ''
    ]"
    :style="{
      flexBasis: `${column.width}%`,
      flexGrow: 0,
      flexShrink: 0
    }"
  >
    <!-- Column Outline when selected -->
    <div
      v-if="isSelected"
      class="absolute -inset-px border-2 border-dashed border-green-500 pointer-events-none rounded"
    ></div>

    <!-- Widgets Container -->
    <div class="space-y-2 p-2">
      <!-- Empty Column Indicator -->
      <div
        v-if="column.widgets.length === 0"
        class="flex items-center justify-center h-24 border-2 border-dashed border-gray-300 rounded-lg text-gray-400"
      >
        <span class="text-xs">Drop widget here</span>
      </div>

      <!-- Widgets -->
      <ElementorWidget
        v-for="widget in column.widgets"
        :key="widget.id"
        :widget="widget"
        :section-id="sectionId"
        :column-id="column.id"
        :is-selected="selectedElement?.type === 'widget' && selectedElement?.id === widget.id"
        @select="$emit('select-widget', $event)"
        @update="$emit('update-widget', $event)"
        @remove="$emit('remove-widget', $event)"
      />
    </div>

    <!-- Drag Over Indicator -->
    <div
      v-if="isDragOver"
      class="absolute inset-0 flex items-center justify-center bg-blue-500 bg-opacity-10 pointer-events-none rounded-lg"
    >
      <div class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg text-sm font-medium">
        Drop widget here
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  column: any
  sectionId: string
  selectedElement: { type: string; id: string } | null
  isSelected: boolean
}>()

const emit = defineEmits(['select', 'add-widget', 'move-widget', 'select-widget', 'update-widget', 'remove-widget'])

const isDragOver = ref(false)

const handleDragOver = (e: DragEvent) => {
  e.preventDefault()
  isDragOver.value = true
}

const handleDrop = (e: DragEvent) => {
  e.preventDefault()
  e.stopPropagation()
  isDragOver.value = false

  const widgetType = e.dataTransfer?.getData('widgetType')
  const widgetId = e.dataTransfer?.getData('widgetId')

  if (widgetId) {
    // Moving existing widget
    emit('move-widget', {
      widgetId,
      fromSectionId: e.dataTransfer.getData('sourceSectionId'),
      fromColumnId: e.dataTransfer.getData('sourceColumnId'),
      toSectionId: props.sectionId,
      toColumnId: props.column.id
    })
  } else if (widgetType) {
    // Adding new widget from sidebar
    emit('add-widget', {
      sectionId: props.sectionId,
      columnId: props.column.id,
      widgetType
    })
  }
}
</script>
