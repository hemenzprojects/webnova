<template>
  <div class="space-y-6">
    <div class="text-sm text-gray-700 bg-blue-50 border border-blue-200 rounded-lg p-3">
      <p class="font-medium text-blue-900">Gallery Editor</p>
      <p class="text-blue-700 mt-1">Upload images, add captions, and customize your gallery layout.</p>
    </div>

    <!-- Gallery Header Section -->
    <div class="space-y-3 pb-4 border-b border-gray-100">
      <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Gallery Header</h5>

      <!-- Title -->
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Gallery Title (Optional)</label>
        <input
          v-model="localData.title"
          @input="handleUpdate"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="e.g., Our Projects"
        />
      </div>

      <!-- Title Color -->
      <div v-if="localData.title">
        <label class="block text-xs font-medium text-gray-600 mb-1">Title Color</label>
        <input
          v-model="localData.titleColor"
          @input="handleUpdate"
          type="color"
          class="w-full h-10 border border-gray-300 rounded-md cursor-pointer"
        />
      </div>

      <!-- Description -->
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Description (Optional)</label>
        <textarea
          v-model="localData.description"
          @input="handleUpdate"
          rows="2"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="e.g., Browse our latest work and achievements"
        />
      </div>

      <!-- Description Color -->
      <div v-if="localData.description">
        <label class="block text-xs font-medium text-gray-600 mb-1">Description Color</label>
        <input
          v-model="localData.descriptionColor"
          @input="handleUpdate"
          type="color"
          class="w-full h-10 border border-gray-300 rounded-md cursor-pointer"
        />
      </div>
    </div>

    <!-- Images Section -->
    <div class="space-y-3">
      <div class="flex items-center justify-between">
        <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Gallery Images</h5>
        <span class="text-xs text-gray-500">{{ localData.images.length }} images</span>
      </div>

      <!-- Image List -->
      <div v-if="localData.images.length > 0" class="space-y-3 max-h-96 overflow-y-auto">
        <div
          v-for="(image, index) in localData.images"
          :key="index"
          class="border border-gray-200 rounded-lg p-3 bg-white group hover:border-blue-300 transition-colors"
        >
          <div class="flex gap-3">
            <!-- Image Preview -->
            <div class="relative w-20 h-20 flex-shrink-0">
              <img
                :src="image.url"
                :alt="image.alt || 'Gallery image'"
                class="w-full h-full object-cover rounded"
                @error="(e) => (e.target as HTMLImageElement).src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo image%3C/text%3E%3C/svg%3E'"
              />
              <!-- Remove Button Overlay -->
              <button
                @click="removeImage(index)"
                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Image Details -->
            <div class="flex-1 space-y-2">
              <!-- Alt Text -->
              <input
                v-model="image.alt"
                @input="handleUpdate"
                type="text"
                class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-transparent"
                placeholder="Alt text (for accessibility)"
              />

              <!-- Caption -->
              <input
                v-model="image.caption"
                @input="handleUpdate"
                type="text"
                class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-transparent"
                placeholder="Caption (optional)"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Add Images Button -->
      <button
        @click="openImageUpload"
        class="w-full px-4 py-8 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-colors group"
      >
        <div class="flex flex-col items-center gap-2">
          <svg class="w-8 h-8 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span class="text-sm font-medium text-gray-600 group-hover:text-blue-600">Add Images</span>
          <span class="text-xs text-gray-500">Click to upload or paste URLs</span>
        </div>
      </button>

      <!-- URL Input Alternative -->
      <div class="relative">
        <input
          v-model="imageUrlInput"
          @keydown.enter="addImageFromUrl"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Or paste image URL and press Enter"
        />
      </div>
    </div>

    <!-- Layout Settings -->
    <div class="space-y-3 pt-4 border-t border-gray-100">
      <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Layout Settings</h5>

      <!-- Columns -->
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Columns</label>
        <select
          v-model.number="localData.columns"
          @change="handleUpdate"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option :value="2">2 Columns</option>
          <option :value="3">3 Columns</option>
          <option :value="4">4 Columns</option>
          <option :value="5">5 Columns</option>
          <option :value="6">6 Columns</option>
        </select>
      </div>

      <!-- Gap -->
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Gap</label>
        <select
          v-model.number="localData.gap"
          @change="handleUpdate"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option :value="2">Small (0.5rem)</option>
          <option :value="4">Medium (1rem)</option>
          <option :value="6">Large (1.5rem)</option>
          <option :value="8">Extra Large (2rem)</option>
        </select>
      </div>

      <!-- Aspect Ratio -->
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Image Aspect Ratio</label>
        <select
          v-model="localData.aspectRatio"
          @change="handleUpdate"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option value="square">Square (1:1)</option>
          <option value="landscape">Landscape (4:3)</option>
          <option value="portrait">Portrait (3:4)</option>
          <option value="auto">Auto (original)</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  widget: any
}>()

const emit = defineEmits(['update'])

const localData = ref({
  title: props.widget.data.title || '',
  titleColor: props.widget.data.titleColor || '#1e293b',
  description: props.widget.data.description || '',
  descriptionColor: props.widget.data.descriptionColor || '#64748b',
  images: props.widget.data.images || [],
  columns: props.widget.data.columns || 3,
  gap: props.widget.data.gap || 4,
  aspectRatio: props.widget.data.aspectRatio || 'square',
  layout: props.widget.data.layout || 'grid'
})

const imageUrlInput = ref('')

const handleUpdate = () => {
  emit('update', { ...localData.value })
}

const openImageUpload = () => {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.multiple = true

  input.onchange = (e: Event) => {
    const target = e.target as HTMLInputElement
    const files = target.files

    if (files) {
      Array.from(files).forEach(file => {
        const reader = new FileReader()
        reader.onload = (event) => {
          const imageUrl = event.target?.result as string
          localData.value.images.push({
            url: imageUrl,
            alt: '',
            caption: ''
          })
          handleUpdate()
        }
        reader.readAsDataURL(file)
      })
    }
  }

  input.click()
}

const addImageFromUrl = () => {
  if (imageUrlInput.value.trim()) {
    localData.value.images.push({
      url: imageUrlInput.value.trim(),
      alt: '',
      caption: ''
    })
    imageUrlInput.value = ''
    handleUpdate()
  }
}

const removeImage = (index: number) => {
  localData.value.images.splice(index, 1)
  handleUpdate()
}

// Watch for external changes
watch(() => props.widget.data, (newData) => {
  if (newData && JSON.stringify(newData) !== JSON.stringify(localData.value)) {
    localData.value = {
      title: newData.title || '',
      titleColor: newData.titleColor || '#1e293b',
      description: newData.description || '',
      descriptionColor: newData.descriptionColor || '#64748b',
      images: newData.images || [],
      columns: newData.columns || 3,
      gap: newData.gap || 4,
      aspectRatio: newData.aspectRatio || 'square',
      layout: newData.layout || 'grid'
    }
  }
}, { deep: true })
</script>