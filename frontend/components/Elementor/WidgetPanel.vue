<template>
  <div class="w-72 bg-white border-r border-gray-200 flex flex-col h-full">
    <!-- Panel Header -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-900">Elements</h3>
        <button class="text-gray-400 hover:text-gray-600">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Tabs -->
      <div class="flex border-b border-gray-200">
        <button
          @click="activeTab = 'widgets'"
          :class="[
            'flex-1 px-3 py-2 text-sm font-medium border-b-2 transition-colors',
            activeTab === 'widgets'
              ? 'text-blue-600 border-blue-600'
              : 'text-gray-600 border-transparent hover:text-gray-900'
          ]"
        >
          Widgets
        </button>
        <button
          @click="activeTab = 'globals'"
          :class="[
            'flex-1 px-3 py-2 text-sm font-medium border-b-2 transition-colors',
            activeTab === 'globals'
              ? 'text-blue-600 border-blue-600'
              : 'text-gray-600 border-transparent hover:text-gray-900'
          ]"
        >
          Globals
        </button>
      </div>
    </div>

    <!-- Search -->
    <div class="px-4 py-3 border-b border-gray-200">
      <div class="relative">
        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search Widget..."
          class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
      </div>
    </div>

    <!-- Widgets List -->
    <div v-if="activeTab === 'widgets'" class="flex-1 overflow-y-auto">
      <!-- Basic Widgets -->
      <div class="px-4 py-3">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Basic</h4>
          <button @click="toggleCategory('basic')" class="text-gray-400 hover:text-gray-600">
            <svg
              :class="['w-4 h-4 transition-transform', expandedCategories.basic ? 'transform rotate-180' : '']"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        </div>

        <div v-show="expandedCategories.basic" class="grid grid-cols-2 gap-2">
          <button
            v-for="widget in getFilteredWidgets('basic')"
            :key="widget.type"
            @click="$emit('add-widget', widget.type)"
            draggable="true"
            @dragstart="handleDragStart(widget.type, $event)"
            class="flex flex-col items-center p-3 bg-gray-50 hover:bg-blue-50 hover:border-blue-500 border border-gray-200 rounded-lg transition-all group cursor-move"
          >
            <span class="material-icons text-gray-600 group-hover:text-blue-600 mb-1">{{ widget.icon }}</span>
            <span class="text-xs font-medium text-gray-700 group-hover:text-blue-700 text-center">{{ widget.label }}</span>
          </button>
        </div>
      </div>

      <!-- Section Widgets -->
      <div class="px-4 py-3 border-t border-gray-100">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sections</h4>
          <button @click="toggleCategory('sections')" class="text-gray-400 hover:text-gray-600">
            <svg
              :class="['w-4 h-4 transition-transform', expandedCategories.sections ? 'transform rotate-180' : '']"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        </div>

        <div v-show="expandedCategories.sections" class="grid grid-cols-2 gap-2">
          <button
            v-for="widget in getFilteredWidgets('sections')"
            :key="widget.type"
            @click="$emit('add-widget', widget.type)"
            draggable="true"
            @dragstart="handleDragStart(widget.type, $event)"
            class="flex flex-col items-center p-3 bg-gray-50 hover:bg-blue-50 hover:border-blue-500 border border-gray-200 rounded-lg transition-all group cursor-move"
          >
            <span class="material-icons text-gray-600 group-hover:text-blue-600 mb-1">{{ widget.icon }}</span>
            <span class="text-xs font-medium text-gray-700 group-hover:text-blue-700 text-center">{{ widget.label }}</span>
          </button>
        </div>
      </div>

      <!-- Forms -->
      <div class="px-4 py-3 border-t border-gray-100">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Forms</h4>
          <button @click="toggleCategory('forms')" class="text-gray-400 hover:text-gray-600">
            <svg
              :class="['w-4 h-4 transition-transform', expandedCategories.forms ? 'transform rotate-180' : '']"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        </div>

        <div v-show="expandedCategories.forms" class="grid grid-cols-2 gap-2">
          <button
            v-for="widget in getFilteredWidgets('forms')"
            :key="widget.type"
            @click="$emit('add-widget', widget.type)"
            draggable="true"
            @dragstart="handleDragStart(widget.type, $event)"
            class="flex flex-col items-center p-3 bg-gray-50 hover:bg-blue-50 hover:border-blue-500 border border-gray-200 rounded-lg transition-all group cursor-move"
          >
            <span class="material-icons text-gray-600 group-hover:text-blue-600 mb-1">{{ widget.icon }}</span>
            <span class="text-xs font-medium text-gray-700 group-hover:text-blue-700 text-center">{{ widget.label }}</span>
          </button>
        </div>
      </div>

      <!-- Dynamic Content -->
      <div class="px-4 py-3 border-t border-gray-100">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Dynamic Content</h4>
          <button @click="toggleCategory('dynamic')" class="text-gray-400 hover:text-gray-600">
            <svg
              :class="['w-4 h-4 transition-transform', expandedCategories.dynamic ? 'transform rotate-180' : '']"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
        </div>

        <div v-show="expandedCategories.dynamic" class="grid grid-cols-2 gap-2">
          <button
            v-for="widget in getFilteredWidgets('dynamic')"
            :key="widget.type"
            @click="$emit('add-widget', widget.type)"
            draggable="true"
            @dragstart="handleDragStart(widget.type, $event)"
            class="flex flex-col items-center p-3 bg-gray-50 hover:bg-blue-50 hover:border-blue-500 border border-gray-200 rounded-lg transition-all group cursor-move"
          >
            <span class="material-icons text-gray-600 group-hover:text-blue-600 mb-1">{{ widget.icon }}</span>
            <span class="text-xs font-medium text-gray-700 group-hover:text-blue-700 text-center">{{ widget.label }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Globals Tab -->
    <div v-else class="flex-1 overflow-y-auto p-4">
      <p class="text-sm text-gray-500 text-center">Global styles coming soon</p>
    </div>
  </div>
</template>

<script setup lang="ts">
const emit = defineEmits(['add-widget'])

const { widgetLibrary } = useElementorEditor()

const activeTab = ref('widgets')
const searchQuery = ref('')
const expandedCategories = ref({
  basic: true,
  sections: true,
  forms: true,
  dynamic: true
})

const toggleCategory = (category: string) => {
  expandedCategories.value[category] = !expandedCategories.value[category]
}

const getFilteredWidgets = (category: string) => {
  let widgets = widgetLibrary.filter(w => w.category === category)

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    widgets = widgets.filter(w =>
      w.label.toLowerCase().includes(query) ||
      w.type.toLowerCase().includes(query)
    )
  }

  return widgets
}

const handleDragStart = (widgetType: string, event: DragEvent) => {
  if (event.dataTransfer) {
    event.dataTransfer.setData('widgetType', widgetType)
    event.dataTransfer.effectAllowed = 'copy'
  }
}
</script>

<style>
/* Add Material Icons if not already included */
@import url('https://fonts.googleapis.com/icon?family=Material+Icons');

.material-icons {
  font-size: 24px;
}
</style>
