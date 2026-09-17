<template>
  <div
    v-if="isOpen"
    @click="$emit('close')"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
  >
    <div
      @click.stop
      class="bg-white rounded-lg shadow-2xl p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto"
    >
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Select Column Structure</h3>
        <button
          @click="$emit('close')"
          class="text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="grid grid-cols-3 gap-4">
        <!-- 1 Column -->
        <button
          @click="selectLayout([100])"
          class="group aspect-square border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all p-4 flex flex-col items-center justify-center"
        >
          <div class="w-full h-full bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
          <p class="text-xs text-gray-600 group-hover:text-blue-600 mt-2 font-medium">1 Column</p>
        </button>

        <!-- 2 Columns Equal -->
        <button
          @click="selectLayout([50, 50])"
          class="group aspect-square border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all p-4 flex flex-col items-center justify-center"
        >
          <div class="w-full h-full flex gap-1">
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
          </div>
          <p class="text-xs text-gray-600 group-hover:text-blue-600 mt-2 font-medium">50 / 50</p>
        </button>

        <!-- 2 Columns (33-66) -->
        <button
          @click="selectLayout([33, 67])"
          class="group aspect-square border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all p-4 flex flex-col items-center justify-center"
        >
          <div class="w-full h-full flex gap-1">
            <div class="w-1/3 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
          </div>
          <p class="text-xs text-gray-600 group-hover:text-blue-600 mt-2 font-medium">33 / 67</p>
        </button>

        <!-- 2 Columns (67-33) -->
        <button
          @click="selectLayout([67, 33])"
          class="group aspect-square border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all p-4 flex flex-col items-center justify-center"
        >
          <div class="w-full h-full flex gap-1">
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="w-1/3 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
          </div>
          <p class="text-xs text-gray-600 group-hover:text-blue-600 mt-2 font-medium">67 / 33</p>
        </button>

        <!-- 2 Columns (25-75) -->
        <button
          @click="selectLayout([25, 75])"
          class="group aspect-square border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all p-4 flex flex-col items-center justify-center"
        >
          <div class="w-full h-full flex gap-1">
            <div class="w-1/4 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
          </div>
          <p class="text-xs text-gray-600 group-hover:text-blue-600 mt-2 font-medium">25 / 75</p>
        </button>

        <!-- 2 Columns (75-25) -->
        <button
          @click="selectLayout([75, 25])"
          class="group aspect-square border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all p-4 flex flex-col items-center justify-center"
        >
          <div class="w-full h-full flex gap-1">
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="w-1/4 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
          </div>
          <p class="text-xs text-gray-600 group-hover:text-blue-600 mt-2 font-medium">75 / 25</p>
        </button>

        <!-- 3 Columns Equal -->
        <button
          @click="selectLayout([33, 33, 34])"
          class="group aspect-square border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all p-4 flex flex-col items-center justify-center"
        >
          <div class="w-full h-full flex gap-1">
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
          </div>
          <p class="text-xs text-gray-600 group-hover:text-blue-600 mt-2 font-medium">33 / 33 / 34</p>
        </button>

        <!-- 3 Columns (25-50-25) -->
        <button
          @click="selectLayout([25, 50, 25])"
          class="group aspect-square border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all p-4 flex flex-col items-center justify-center"
        >
          <div class="w-full h-full flex gap-1">
            <div class="w-1/4 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="w-1/4 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
          </div>
          <p class="text-xs text-gray-600 group-hover:text-blue-600 mt-2 font-medium">25 / 50 / 25</p>
        </button>

        <!-- 4 Columns Equal -->
        <button
          @click="selectLayout([25, 25, 25, 25])"
          class="group aspect-square border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all p-4 flex flex-col items-center justify-center"
        >
          <div class="w-full h-full flex gap-1">
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex-1 bg-blue-500 rounded opacity-80 group-hover:opacity-100 transition-opacity"></div>
          </div>
          <p class="text-xs text-gray-600 group-hover:text-blue-600 mt-2 font-medium">4 Columns</p>
        </button>
      </div>

      <div class="mt-6 pt-6 border-t border-gray-200">
        <button
          @click="$emit('close')"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits(['select', 'close'])

const selectLayout = (columnWidths: number[]) => {
  emit('select', columnWidths)
  emit('close')
}
</script>
