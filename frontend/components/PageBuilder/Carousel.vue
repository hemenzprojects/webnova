<template>
  <section class="py-12">
    <div class="container mx-auto px-4">
      <div class="relative overflow-hidden rounded-2xl" :class="heightClass">
        <!-- Slides -->
        <div
          v-for="(slide, index) in data.slides"
          :key="index"
          class="absolute inset-0 transition-opacity duration-700"
          :class="[
            currentSlide === index ? 'opacity-100 z-10' : 'opacity-0 z-0',
            data.transition === 'fade' ? 'transition-opacity' : 'transition-transform'
          ]"
          :style="data.transition === 'slide' && currentSlide !== index
            ? { transform: `translateX(${(index - currentSlide) * 100}%)` }
            : {}"
        >
          <!-- Slide Content -->
          <component
            :is="slide.link ? 'a' : 'div'"
            :href="slide.link || undefined"
            :target="slide.link && slide.openInNewTab ? '_blank' : undefined"
            :rel="slide.link && slide.openInNewTab ? 'noopener noreferrer' : undefined"
            class="relative w-full h-full group"
          >
            <!-- Image -->
            <img
              :src="getImageUrl(slide.image)"
              :alt="slide.title || `Slide ${index + 1}`"
              class="w-full h-full object-cover"
            />

            <!-- Overlay -->
            <div
              v-if="slide.title || slide.caption"
              class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent flex items-end"
            >
              <div class="p-8 text-white w-full">
                <h3
                  v-if="slide.title"
                  class="text-3xl md:text-4xl font-bold mb-2 transform transition-transform duration-300 group-hover:translate-y-[-4px]"
                >
                  {{ slide.title }}
                </h3>
                <p
                  v-if="slide.caption"
                  class="text-lg md:text-xl text-gray-200 max-w-3xl"
                >
                  {{ slide.caption }}
                </p>
              </div>
            </div>
          </component>
        </div>

        <!-- Navigation Arrows -->
        <div v-if="data.showArrows && data.slides.length > 1" class="absolute inset-0 flex items-center justify-between px-4 pointer-events-none z-20">
          <button
            @click="previousSlide"
            class="pointer-events-auto w-12 h-12 bg-white/90 hover:bg-white rounded-full shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110"
            aria-label="Previous slide"
          >
            <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button
            @click="nextSlide"
            class="pointer-events-auto w-12 h-12 bg-white/90 hover:bg-white rounded-full shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110"
            aria-label="Next slide"
          >
            <svg class="w-6 h-6 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <!-- Slide Indicators -->
        <div
          v-if="data.showIndicators && data.slides.length > 1"
          class="absolute bottom-4 left-0 right-0 flex justify-center gap-2 z-20"
        >
          <button
            v-for="(slide, index) in data.slides"
            :key="index"
            @click="goToSlide(index)"
            class="w-3 h-3 rounded-full transition-all duration-300"
            :class="currentSlide === index
              ? 'bg-white w-8'
              : 'bg-white/50 hover:bg-white/75'"
            :aria-label="`Go to slide ${index + 1}`"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

interface Slide {
  image: string
  title?: string
  caption?: string
  link?: string
  openInNewTab?: boolean
}

interface CarouselData {
  slides: Slide[]
  autoplay: boolean
  interval: number
  showIndicators: boolean
  showArrows: boolean
  height: 'small' | 'medium' | 'large' | 'xlarge'
  transition: 'slide' | 'fade'
}

const props = defineProps<{
  data: CarouselData
  blockId: string
}>()

const { getImageUrl } = useImageUrl()

const currentSlide = ref(0)
let autoplayInterval: NodeJS.Timeout | null = null

const heightClass = computed(() => {
  const heightMap: Record<string, string> = {
    small: 'h-[400px]',
    medium: 'h-[500px]',
    large: 'h-[600px]',
    xlarge: 'h-[700px]',
  }
  return heightMap[props.data.height] || heightMap.medium
})

const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % props.data.slides.length
}

const previousSlide = () => {
  currentSlide.value = (currentSlide.value - 1 + props.data.slides.length) % props.data.slides.length
}

const goToSlide = (index: number) => {
  currentSlide.value = index
}

const startAutoplay = () => {
  if (props.data.autoplay && props.data.slides.length > 1) {
    const intervalMs = (props.data.interval || 5) * 1000
    autoplayInterval = setInterval(nextSlide, intervalMs)
  }
}

const stopAutoplay = () => {
  if (autoplayInterval) {
    clearInterval(autoplayInterval)
    autoplayInterval = null
  }
}

onMounted(() => {
  startAutoplay()
})

onUnmounted(() => {
  stopAutoplay()
})

// Pause autoplay on user interaction
const handleUserInteraction = () => {
  stopAutoplay()
  // Restart autoplay after a delay
  setTimeout(startAutoplay, 3000)
}

// Watch for manual navigation
const handleManualNavigation = () => {
  handleUserInteraction()
}
</script>