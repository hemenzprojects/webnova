<template>
  <section class="py-12 md:py-16">
    <div class="container mx-auto px-4">
      <!-- Gallery Header -->
      <div v-if="data.title || data.description" class="text-center mb-8 md:mb-12">
        <h2 v-if="data.title" class="text-3xl md:text-4xl font-bold mb-4" :style="{ color: data.titleColor || '#1e293b' }">
          {{ data.title }}
        </h2>
        <p v-if="data.description" class="text-lg text-gray-600 max-w-3xl mx-auto" :style="{ color: data.descriptionColor || '#64748b' }">
          {{ data.description }}
        </p>
      </div>

      <!-- Gallery Grid -->
      <div v-if="data.images && data.images.length > 0" :class="getGridClass()">
        <div
          v-for="(image, index) in data.images"
          :key="index"
          class="group relative overflow-hidden rounded-lg cursor-pointer transition-all duration-300 hover:shadow-2xl"
          :class="getImageContainerClass()"
          @click="openLightbox(index)"
        >
          <!-- Image -->
          <img
            :src="image.url"
            :alt="image.alt || image.caption || `Gallery image ${index + 1}`"
            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
          />

          <!-- Overlay -->
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            <!-- Caption -->
            <div v-if="image.caption" class="absolute bottom-0 left-0 right-0 p-4">
              <p class="text-white font-medium">{{ image.caption }}</p>
            </div>

            <!-- Zoom Icon -->
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
              <svg class="w-12 h-12 text-white opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-16 bg-gray-50 rounded-lg">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="text-gray-500">No images in gallery</p>
      </div>

      <!-- Lightbox Modal -->
      <Teleport to="body">
        <div
          v-if="lightboxOpen"
          class="fixed inset-0 z-50 bg-black/95 flex items-center justify-center"
          @click="closeLightbox"
        >
          <!-- Close Button -->
          <button
            @click="closeLightbox"
            class="absolute top-4 right-4 w-12 h-12 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors z-10"
            aria-label="Close lightbox"
          >
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Previous Button -->
          <button
            v-if="data.images && data.images.length > 1"
            @click.stop="previousImage"
            class="absolute left-4 w-12 h-12 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors z-10"
            aria-label="Previous image"
          >
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <!-- Image Container -->
          <div class="relative max-w-7xl max-h-[90vh] mx-auto px-16" @click.stop>
            <img
              v-if="data.images && data.images[currentImageIndex]"
              :src="data.images[currentImageIndex].url"
              :alt="data.images[currentImageIndex].alt || data.images[currentImageIndex].caption"
              class="max-w-full max-h-[90vh] object-contain rounded-lg"
            />

            <!-- Caption in Lightbox -->
            <div
              v-if="data.images && data.images[currentImageIndex] && data.images[currentImageIndex].caption"
              class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-6 rounded-b-lg"
            >
              <p class="text-white text-lg text-center">{{ data.images[currentImageIndex].caption }}</p>
            </div>
          </div>

          <!-- Next Button -->
          <button
            v-if="data.images && data.images.length > 1"
            @click.stop="nextImage"
            class="absolute right-4 w-12 h-12 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center transition-colors z-10"
            aria-label="Next image"
          >
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Image Counter -->
          <div
            v-if="data.images && data.images.length > 1"
            class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-white/10 backdrop-blur-sm px-4 py-2 rounded-full"
          >
            <p class="text-white text-sm">{{ currentImageIndex + 1 }} / {{ data.images.length }}</p>
          </div>
        </div>
      </Teleport>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface GalleryImage {
  url: string
  alt?: string
  caption?: string
}

interface GalleryData {
  title?: string
  titleColor?: string
  description?: string
  descriptionColor?: string
  images: GalleryImage[]
  columns?: number
  gap?: number
  aspectRatio?: 'square' | 'portrait' | 'landscape' | 'auto'
  layout?: 'grid' | 'masonry'
}

const props = defineProps<{
  data: GalleryData
  blockId: string
}>()

const lightboxOpen = ref(false)
const currentImageIndex = ref(0)

const getGridClass = () => {
  const columns = props.data.columns || 3
  const gap = props.data.gap || 4

  const columnClasses: Record<number, string> = {
    2: 'grid-cols-1 md:grid-cols-2',
    3: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    5: 'grid-cols-1 md:grid-cols-3 lg:grid-cols-5',
    6: 'grid-cols-1 md:grid-cols-3 lg:grid-cols-6'
  }

  const gapClasses: Record<number, string> = {
    2: 'gap-2',
    4: 'gap-4',
    6: 'gap-6',
    8: 'gap-8'
  }

  return `grid ${columnClasses[columns] || columnClasses[3]} ${gapClasses[gap] || gapClasses[4]}`
}

const getImageContainerClass = () => {
  const aspectRatio = props.data.aspectRatio || 'square'

  const aspectClasses: Record<string, string> = {
    square: 'aspect-square',
    portrait: 'aspect-[3/4]',
    landscape: 'aspect-[4/3]',
    auto: 'aspect-auto'
  }

  return aspectClasses[aspectRatio] || aspectClasses.square
}

const openLightbox = (index: number) => {
  currentImageIndex.value = index
  lightboxOpen.value = true
  document.body.style.overflow = 'hidden'
}

const closeLightbox = () => {
  lightboxOpen.value = false
  document.body.style.overflow = ''
}

const nextImage = () => {
  if (props.data.images && props.data.images.length > 0) {
    currentImageIndex.value = (currentImageIndex.value + 1) % props.data.images.length
  }
}

const previousImage = () => {
  if (props.data.images && props.data.images.length > 0) {
    currentImageIndex.value = (currentImageIndex.value - 1 + props.data.images.length) % props.data.images.length
  }
}

// Keyboard navigation
const handleKeydown = (e: KeyboardEvent) => {
  if (!lightboxOpen.value) return

  if (e.key === 'Escape') {
    closeLightbox()
  } else if (e.key === 'ArrowRight') {
    nextImage()
  } else if (e.key === 'ArrowLeft') {
    previousImage()
  }
}

if (typeof window !== 'undefined') {
  window.addEventListener('keydown', handleKeydown)
}
</script>

<style scoped>
/* Smooth image loading */
img {
  image-rendering: -webkit-optimize-contrast;
}
</style>