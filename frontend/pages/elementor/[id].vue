<template>
  <div class="h-screen flex flex-col bg-gray-100">
    <!-- Top Toolbar -->
    <div class="bg-white border-b border-gray-200 shadow-sm z-50">
      <div class="px-4 py-2.5 flex items-center justify-between">
        <!-- Left: Back & Title -->
        <div class="flex items-center space-x-3">
          <NuxtLink
            to="/admin"
            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </NuxtLink>

          <div class="border-l border-gray-300 pl-3">
            <h1 class="text-sm font-semibold text-gray-900">{{ page?.title || 'Elementor Editor' }}</h1>
            <p class="text-xs text-gray-500">{{ hasChanges ? 'Unsaved Changes' : 'All Changes Saved' }}</p>
          </div>
        </div>

        <!-- Center: Add Section Button -->
        <div class="flex items-center space-x-2">
          <button
            @click="() => addSection([100])"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm font-medium transition-colors flex items-center space-x-1"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Add Section</span>
          </button>

          <button
            @click="showLayoutPicker = true"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-sm font-medium transition-colors flex items-center space-x-1"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z" />
            </svg>
            <span>Structure</span>
          </button>
        </div>

        <!-- Right: Preview & Publish -->
        <div class="flex items-center space-x-2">
          <!-- Device Preview -->
          <div class="flex items-center space-x-1 bg-gray-100 rounded p-0.5">
            <button
              v-for="mode in previewModes"
              :key="mode.value"
              @click="previewMode = mode.value"
              :class="[
                'p-1.5 rounded transition-colors',
                previewMode === mode.value
                  ? 'bg-white text-blue-600 shadow-sm'
                  : 'text-gray-600 hover:text-gray-900'
              ]"
              :title="mode.label"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="mode.icon" />
              </svg>
            </button>
          </div>

          <button
            @click="handleSave"
            :disabled="!hasChanges || isSaving"
            class="px-4 py-1.5 bg-gray-800 text-white rounded hover:bg-gray-900 disabled:opacity-50 text-sm font-medium transition-colors"
          >
            {{ isSaving ? 'Saving...' : 'Save Draft' }}
          </button>

          <button
            @click="handlePublish"
            class="px-4 py-1.5 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-medium transition-colors"
          >
            Publish
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
      <!-- Widget Panel (Left Sidebar) -->
      <ElementorWidgetPanel @add-widget="handleAddWidgetToNewSection" />

      <!-- Canvas Area -->
      <div class="flex-1 overflow-y-auto bg-gray-100 relative">
        <div
          :class="[
            'transition-all duration-300',
            previewMode === 'desktop' ? 'w-full' : '',
            previewMode === 'tablet' ? 'max-w-3xl mx-auto' : '',
            previewMode === 'mobile' ? 'max-w-md mx-auto' : ''
          ]"
        >
          <div class="p-4 min-h-screen">
            <!-- Empty State -->
            <div
              v-if="sections.length === 0"
              class="flex items-center justify-center min-h-[500px] bg-white border-2 border-dashed border-gray-300 rounded-lg"
            >
              <div class="text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Start Building Your Page</h3>
                <p class="text-sm text-gray-500 mb-4">Click "Add Section" or drag widgets from the left panel</p>
                <button
                  @click="() => addSection([50, 50])"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                  Add Your First Section
                </button>
              </div>
            </div>

            <!-- Sections -->
            <div v-else class="space-y-4">
              <ElementorSection
                v-for="(section, index) in sections"
                :key="section.id"
                :section="section"
                :selected-element="selectedElement"
                @select="selectElement($event.type, $event.id)"
                @move-up="handleMoveUp(section.id)"
                @move-down="handleMoveDown(section.id)"
                @duplicate="duplicateSection(section.id)"
                @delete="removeSection(section.id)"
                @add-widget="handleAddWidget"
                @move-widget="handleMoveWidget"
                @select-widget="handleSelectWidget"
                @update-widget="handleUpdateWidget"
                @remove-widget="handleRemoveWidget"
              />

              <!-- Add Section Between Button -->
              <div class="text-center py-4">
                <button
                  @click="showLayoutPicker = true"
                  class="px-4 py-2 border-2 border-dashed border-gray-300 hover:border-blue-500 hover:bg-blue-50 text-gray-600 hover:text-blue-600 rounded-lg transition-all inline-flex items-center space-x-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  <span class="text-sm font-medium">Add Section</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Settings Panel (Right Sidebar) - Shows when element is selected -->
      <ElementorSettingsPanel
        v-if="selectedElement"
        :selected-element="selectedElement"
        :sections="sections"
        @update="handleSettingsUpdate"
        @close="selectedElement = null"
      />
    </div>

    <!-- Section Layout Picker Modal -->
    <ElementorSectionLayoutPicker
      :is-open="showLayoutPicker"
      @select="handleLayoutSelect"
      @close="showLayoutPicker = false"
    />
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const api = useApi()

const {
  sections,
  selectedElement,
  isSaving,
  hasChanges,
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
  exportToBlocks
} = useElementorEditor()

const page = ref<any>(null)
const previewMode = ref('desktop')
const showLayoutPicker = ref(false)

const previewModes = [
  { label: 'Desktop', value: 'desktop', icon: 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
  { label: 'Tablet', value: 'tablet', icon: 'M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z' },
  { label: 'Mobile', value: 'mobile', icon: 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z' }
]

// Load page
onMounted(async () => {
  try {
    const pageId = route.params.id
    page.value = await api.fetchPageForEdit(pageId)

    if (page.value.blocks && page.value.blocks.length > 0) {
      loadFromBlocks(page.value.blocks)
    }
  } catch (error) {
    console.error('Error loading page:', error)
    alert('Failed to load page')
  }
})

const handleAddWidget = ({ sectionId, columnId, widgetType }: any) => {
  const widget = addWidget(sectionId, columnId, widgetType)
  if (widget) {
    selectElement('widget', widget.id)
  }
}

const handleAddWidgetToNewSection = (widgetType: string) => {
  const section = addSection([100])
  if (section) {
    const widget = addWidget(section.id, section.columns[0].id, widgetType)
    if (widget) {
      selectElement('widget', widget.id)
    }
  }
}

const handleSelectWidget = ({ type, id, sectionId, columnId }: any) => {
  selectElement(type, id)
}

const handleUpdateWidget = ({ sectionId, columnId, widgetId, data }: any) => {
  updateWidgetData(sectionId, columnId, widgetId, data)
}

const handleRemoveWidget = ({ sectionId, columnId, widgetId }: any) => {
  removeWidget(sectionId, columnId, widgetId)
}

const handleMoveUp = (sectionId: string) => {
  const index = sections.value.findIndex(s => s.id === sectionId)
  if (index > 0) {
    moveSection(index, index - 1)
  }
}

const handleMoveDown = (sectionId: string) => {
  const index = sections.value.findIndex(s => s.id === sectionId)
  if (index < sections.value.length - 1) {
    moveSection(index, index + 1)
  }
}

const handleLayoutSelect = (columnWidths: number[]) => {
  addSection(columnWidths)
}

const handleMoveWidget = (params: any) => {
  moveWidget(params)
}

const handleSettingsUpdate = (data: any) => {
  if (selectedElement.value?.type === 'section') {
    updateSectionSettings(selectedElement.value.id, data)
  } else if (selectedElement.value?.type === 'widget') {
    // Find widget and update
    for (const section of sections.value) {
      for (const column of section.columns) {
        const widget = column.widgets.find(w => w.id === selectedElement.value?.id)
        if (widget) {
          updateWidgetData(section.id, column.id, widget.id, data)
          return
        }
      }
    }
  }
}

const handleSave = async () => {
  if (!hasChanges.value || isSaving.value) return

  isSaving.value = true
  try {
    const pageId = route.params.id
    const blocks = exportToBlocks()
    await api.updatePageBlocks(pageId, blocks)
    hasChanges.value = false
    alert('Page saved successfully!')
  } catch (error) {
    console.error('Error saving page:', error)
    alert('Failed to save page')
  } finally {
    isSaving.value = false
  }
}

const handlePublish = async () => {
  if (hasChanges.value) {
    await handleSave()
  }

  try {
    const pageId = route.params.id
    await api.publishPage(pageId)
    alert('Page published successfully!')
  } catch (error) {
    console.error('Error publishing page:', error)
    alert('Failed to publish page')
  }
}

// Keyboard shortcuts
onMounted(() => {
  const handleKeyDown = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 's') {
      e.preventDefault()
      handleSave()
    }

    if (e.key === 'Escape') {
      selectedElement.value = null
    }
  }

  window.addEventListener('keydown', handleKeyDown)

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown)
  })
})
</script>
