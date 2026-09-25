<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <!-- Backdrop -->
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close" />

      <!-- Modal -->
      <div class="relative z-10 w-full max-w-5xl max-h-[90vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 shrink-0">
          <h2 class="text-lg font-semibold text-gray-900">Media Library</h2>
          <button @click="close" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Toolbar -->
        <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-100 shrink-0 bg-gray-50">
          <div class="flex-1 relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
            </svg>
            <input
              v-model="search"
              @input="debouncedFetch"
              type="text"
              placeholder="Search files…"
              class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <!-- Upload button -->
          <label class="relative cursor-pointer">
            <input
              ref="fileInput"
              type="file"
              multiple
              accept="image/*"
              class="sr-only"
              @change="handleUpload"
            />
            <span
              class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition"
              :class="{ 'opacity-60 cursor-not-allowed': uploading }"
            >
              <svg v-if="!uploading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              <svg v-else class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
              </svg>
              {{ uploading ? 'Uploading…' : 'Upload' }}
            </span>
          </label>
        </div>

        <!-- Grid -->
        <div class="flex-1 overflow-y-auto p-5">
          <!-- Loading skeleton -->
          <div v-if="loading" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
            <div
              v-for="i in 15"
              :key="i"
              class="aspect-square rounded-xl bg-gray-200 animate-pulse"
            />
          </div>

          <!-- Empty state -->
          <div v-else-if="!items.length" class="flex flex-col items-center justify-center h-48 text-gray-400">
            <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-sm font-medium">No files yet</p>
            <p class="text-xs mt-1">Upload images using the button above</p>
          </div>

          <!-- Image grid -->
          <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
            <div
              v-for="item in items"
              :key="item.id"
              class="group relative aspect-square rounded-xl overflow-hidden border-2 transition"
              :class="selected?.id === item.id
                ? 'border-blue-500 ring-2 ring-blue-500 ring-offset-1'
                : 'border-transparent hover:border-gray-300'"
            >
              <!-- Clickable image area -->
              <button
                type="button"
                @click="toggleSelect(item)"
                class="absolute inset-0 w-full h-full focus:outline-none"
              >
                <img
                  :src="getImageUrl(item.path)"
                  :alt="item.original_name"
                  class="w-full h-full object-cover"
                  loading="lazy"
                />
              </button>

              <!-- Selected overlay -->
              <div
                v-if="selected?.id === item.id"
                class="absolute inset-0 bg-blue-500/20 pointer-events-none flex items-center justify-center"
              >
                <div class="bg-blue-500 rounded-full p-1">
                  <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>

              <!-- Delete button (top-right, visible on hover) -->
              <button
                type="button"
                @click.stop="deleteItem(item)"
                class="absolute top-1.5 right-1.5 z-10 p-1 bg-red-600 text-white rounded-full opacity-0 group-hover:opacity-100 hover:bg-red-700 transition focus:outline-none focus:ring-2 focus:ring-red-500"
                title="Delete"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>

              <!-- Filename tooltip -->
              <div class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-xs p-1.5 truncate opacity-0 group-hover:opacity-100 transition pointer-events-none">
                {{ item.original_name }}
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="meta.last_page > 1" class="flex justify-center gap-2 mt-6">
            <button
              v-for="page in meta.last_page"
              :key="page"
              @click="goToPage(page)"
              class="w-8 h-8 rounded-lg text-sm font-medium transition"
              :class="page === meta.current_page
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
            >
              {{ page }}
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-5 py-4 border-t border-gray-200 bg-gray-50 shrink-0">
          <p v-if="selected" class="text-sm text-gray-600 truncate max-w-xs">
            Selected: <span class="font-medium text-gray-900">{{ selected.original_name }}</span>
          </p>
          <p v-else class="text-sm text-gray-400">Click an image to select it</p>

          <div class="flex items-center gap-2 ml-auto">
            <button
              @click="close"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
              Cancel
            </button>
            <button
              @click="confirmSelection"
              :disabled="!selected"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
            >
              Use Image
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
interface MediaItem {
  id: number
  path: string
  original_name: string
  filename: string
  size: number | null
  mime_type: string | null
}

interface Meta {
  current_page: number
  last_page: number
  total: number
}

const emit = defineEmits<{
  select: [path: string]
}>()

const { getImageUrl } = useImageUrl()
const config = useRuntimeConfig()

const isOpen = ref(false)
const items = ref<MediaItem[]>([])
const meta = ref<Meta>({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
const uploading = ref(false)
const search = ref('')
const selected = ref<MediaItem | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

let debounceTimer: ReturnType<typeof setTimeout>

const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchMedia(1), 300)
}

const fetchMedia = async (page = 1) => {
  loading.value = true
  try {
    const apiBase = config.public.apiBase
    const params = new URLSearchParams({ page: String(page) })
    if (search.value) params.set('search', search.value)

    const res = await $fetch<{ data: MediaItem[]; meta: Meta }>(`${apiBase}/media?${params}`)
    items.value = res.data
    meta.value = res.meta
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

const goToPage = (page: number) => {
  selected.value = null
  fetchMedia(page)
}

const toggleSelect = (item: MediaItem) => {
  selected.value = selected.value?.id === item.id ? null : item
}

const deleteItem = async (item: MediaItem) => {
  if (!window.confirm(`Delete "${item.original_name}"?`)) return

  try {
    const apiBase = config.public.apiBase
    await $fetch(`${apiBase}/media/${item.id}`, { method: 'DELETE' })
    items.value = items.value.filter(i => i.id !== item.id)
    if (selected.value?.id === item.id) selected.value = null
  } catch {
    window.alert('Failed to delete file. Please try again.')
  }
}

const handleUpload = async (event: Event) => {
  const input = event.target as HTMLInputElement
  const files = input.files
  if (!files?.length) return

  uploading.value = true
  const apiBase = config.public.apiBase

  try {
    for (const file of Array.from(files)) {
      const form = new FormData()
      form.append('image', file)
      form.append('folder', 'media')

      await $fetch(`${apiBase}/media/upload`, { method: 'POST', body: form })
    }
    await fetchMedia(1)
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

const confirmSelection = () => {
  if (selected.value) {
    emit('select', selected.value.path)
    close()
  }
}

const close = () => {
  isOpen.value = false
  selected.value = null
}

const open = () => {
  isOpen.value = true
  selected.value = null
  fetchMedia(1)
}

defineExpose({ open })
</script>