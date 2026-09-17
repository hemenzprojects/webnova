<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-2">{{ label }}</label>

    <div class="space-y-3">
      <!-- Preview -->
      <div v-if="currentImageUrl" class="relative inline-block">
        <img :src="currentImageUrl" :alt="label" class="max-w-xs max-h-40 rounded-lg border border-gray-300" />
        <button
          @click="removeImage"
          type="button"
          class="absolute -top-2 -right-2 bg-red-600 text-white rounded-full p-1 hover:bg-red-700 transition"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Upload Button or Input -->
      <div class="flex items-center gap-2">
        <input
          ref="fileInput"
          type="file"
          accept="image/*"
          @change="handleFileSelect"
          class="hidden"
        />

        <button
          @click="$refs.fileInput.click()"
          type="button"
          :disabled="uploading"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center gap-2"
        >
          <svg v-if="!uploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <svg v-else class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>{{ uploading ? 'Uploading...' : (currentImageUrl ? 'Change Image' : 'Upload Image') }}</span>
        </button>

        <!-- URL Input Option -->
        <span class="text-sm text-gray-500">or</span>
        <input
          type="url"
          :value="modelValue"
          @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
          placeholder="Enter image URL"
          class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>

      <!-- Error Message -->
      <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

      <!-- File Info -->
      <p v-if="uploading" class="text-xs text-gray-500">Uploading image...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  modelValue: string
  label?: string
  type?: 'hero' | 'card' | 'featured' | 'general'
}>()

const emit = defineEmits(['update:modelValue'])

const fileInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)
const error = ref('')

const currentImageUrl = computed(() => {
  console.log('ImageUpload modelValue:', props.modelValue)

  if (!props.modelValue) {
    console.log('No modelValue, returning null')
    return null
  }

  // If it's already a full URL, use it
  if (props.modelValue.startsWith('http')) {
    console.log('Full URL detected:', props.modelValue)
    return props.modelValue
  }

  // If it's a storage path, construct the full URL
  const config = useRuntimeConfig()
  const fullUrl = `${config.public.backendUrl}${props.modelValue}`
  console.log('Constructed URL:', fullUrl)
  return fullUrl
})

const handleFileSelect = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (!file) return

  // Validate file type
  if (!file.type.startsWith('image/')) {
    error.value = 'Please select an image file'
    return
  }

  // Validate file size (5MB)
  if (file.size > 5 * 1024 * 1024) {
    error.value = 'Image must be less than 5MB'
    return
  }

  error.value = ''
  uploading.value = true

  try {
    const formData = new FormData()
    formData.append('image', file)
    formData.append('type', props.type || 'general')

    const config = useRuntimeConfig()
    const apiBase = process.server ? config.apiBaseSSR : config.public.apiBase

    const response = await $fetch<{ success: boolean; url: string; path: string }>(`${apiBase}/media/upload`, {
      method: 'POST',
      body: formData
    })

    if (response.success) {
      console.log('Upload successful:', response)
      console.log('Image URL:', response.url)
      emit('update:modelValue', response.url)
    }
  } catch (err: any) {
    error.value = err.data?.message || 'Failed to upload image'
    console.error('Upload error:', err)
  } finally {
    uploading.value = false
    // Reset file input
    if (target) target.value = ''
  }
}

const removeImage = () => {
  emit('update:modelValue', '')
  error.value = ''
}
</script>