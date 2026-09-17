<template>
  <section class="relative w-full overflow-hidden" :class="heightClass">
    <!-- Slides Container -->
    <div class="relative w-full" :class="heightClass">
      <div
        v-for="(slide, index) in data.slides"
        :key="index"
        class="absolute inset-0 transition-opacity duration-700 ease-in-out"
        :class="currentSlide === index ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'"
      >
        <!-- Background Image or Gradient -->
        <div class="absolute inset-0 z-0 w-full h-full">
          <div v-if="slide.backgroundImage" class="absolute inset-0">
            <img
              :src="slide.backgroundImage"
              alt="Background"
              class="w-full h-full object-cover"
            />
            <!-- Optional overlay for better text readability -->
            <div
              v-if="slide.backgroundOverlay"
              class="absolute inset-0"
              :class="getOverlayClass(slide.backgroundOverlay)"
            ></div>
          </div>
          <div v-else class="absolute inset-0 bg-gradient-to-br from-gray-50 to-white"></div>
        </div>

        <!-- Modern Split Layout -->
        <div class="relative z-20 container mx-auto px-4 md:px-8 h-full flex items-center py-12">
          <div class="w-full" :class="slide.showFeaturedCard !== false ? 'grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16 items-center' : 'max-w-4xl mx-auto'">

            <!-- Left Content -->
            <div class="slide-content space-y-6">
              <!-- Pre-heading -->
              <div v-if="slide.preHeading" class="text-lg md:text-xl font-normal text-gray-600">
                {{ slide.preHeading }}
              </div>

              <!-- Main Heading -->
              <h1
                class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight"
                :style="{ color: slide.headingColor || '#1e293b' }"
              >
                <span v-html="formatHeading(slide.heading || 'Welcome')"></span>
              </h1>

              <!-- Subheading / Description -->
              <p
                v-if="slide.subheading"
                class="text-lg md:text-xl leading-relaxed max-w-2xl"
                :style="{ color: slide.subheadingColor || '#64748b' }"
              >
                {{ slide.subheading }}
              </p>

              <!-- CTA Buttons -->
              <div class="flex flex-wrap gap-4 pt-4">
                <a
                  v-if="slide.buttonText"
                  :href="slide.buttonLink || '#'"
                  class="inline-flex items-center gap-2 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 hover:scale-105 hover:shadow-xl bg-slate-900 text-white hover:bg-slate-800"
                >
                  {{ slide.buttonText }}
                </a>
                <a
                  v-if="slide.secondaryButtonText"
                  :href="slide.secondaryButtonLink || '#'"
                  class="inline-flex items-center gap-2 px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 hover:scale-105 bg-gray-200 text-gray-800 hover:bg-gray-300"
                >
                  {{ slide.secondaryButtonText }}
                </a>
              </div>
            </div>

            <!-- Right Featured Card -->
            <div v-if="slide.showFeaturedCard !== false" class="hidden lg:block featured-card">
              <div class="relative rounded-3xl shadow-2xl overflow-hidden min-h-[400px]" :class="getFeaturedCardClasses(slide)">

                <!-- Background Image (if provided) -->
                <div v-if="slide.featuredImage && slide.featuredImageMode === 'background'" class="absolute inset-0">
                  <img
                    :src="slide.featuredImage"
                    :alt="slide.featuredTitle || 'Featured'"
                    class="w-full h-full object-cover"
                  />
                  <!-- Dark overlay for better text readability -->
                  <div class="absolute inset-0 bg-gradient-to-br from-black/60 via-black/50 to-black/40"></div>
                </div>

                <!-- Gradient Overlay (when no background image or overlay mode) -->
                <div v-else class="absolute inset-0 bg-gradient-to-br from-teal-500 via-teal-600 to-emerald-600"></div>

                <!-- Split Layout (Image + Text side by side) -->
                <div v-if="slide.featuredImage && slide.featuredImageMode === 'split'" class="relative z-10 h-full grid grid-cols-2 gap-0">
                  <!-- Left: Image -->
                  <div class="relative">
                    <img
                      :src="slide.featuredImage"
                      :alt="slide.featuredTitle || 'Featured'"
                      class="w-full h-full object-cover"
                    />
                  </div>

                  <!-- Right: Text Content -->
                  <div class="flex items-center justify-center p-8 bg-gradient-to-br from-teal-500 via-teal-600 to-emerald-600">
                    <div class="text-white space-y-4 text-center">
                      <!-- Featured Label -->
                      <div class="flex items-center gap-2">
                        <div class="h-px flex-1 bg-white/30"></div>
                        <span class="text-xs tracking-[0.3em] font-medium uppercase">Featured</span>
                        <div class="h-px flex-1 bg-white/30"></div>
                      </div>

                      <!-- Featured Heading -->
                      <h2 class="text-3xl md:text-4xl font-bold leading-tight">
                        {{ slide.featuredTitle || 'Innovation At Your Fingertips' }}
                      </h2>

                      <!-- Optional Featured Description -->
                      <p v-if="slide.featuredDescription" class="text-sm text-white/90">
                        {{ slide.featuredDescription }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Stacked Layout (Image on top, Text below) -->
                <div v-else-if="slide.featuredImage && slide.featuredImageMode === 'stacked'" class="relative z-10 h-full flex flex-col">
                  <!-- Top: Image -->
                  <div class="flex-1 relative">
                    <img
                      :src="slide.featuredImage"
                      :alt="slide.featuredTitle || 'Featured'"
                      class="w-full h-full object-cover"
                    />
                  </div>

                  <!-- Bottom: Text Content -->
                  <div class="p-8 bg-gradient-to-br from-teal-500 via-teal-600 to-emerald-600">
                    <div class="text-white space-y-3 text-center">
                      <!-- Featured Label -->
                      <div class="flex items-center gap-2">
                        <div class="h-px flex-1 bg-white/30"></div>
                        <span class="text-xs tracking-[0.3em] font-medium uppercase">Featured</span>
                        <div class="h-px flex-1 bg-white/30"></div>
                      </div>

                      <!-- Featured Heading -->
                      <h2 class="text-2xl md:text-3xl font-bold leading-tight">
                        {{ slide.featuredTitle || 'Innovation At Your Fingertips' }}
                      </h2>

                      <!-- Optional Featured Description -->
                      <p v-if="slide.featuredDescription" class="text-sm text-white/90">
                        {{ slide.featuredDescription }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Default Text-Only Layout -->
                <div v-else class="relative z-10 h-full flex items-center justify-center p-12">
                  <div class="text-white space-y-8 text-center">
                    <!-- Featured Label -->
                    <div class="flex items-center gap-4">
                      <div class="h-px flex-1 bg-white/30"></div>
                      <span class="text-sm tracking-[0.3em] font-medium uppercase">Featured</span>
                      <div class="h-px flex-1 bg-white/30"></div>
                    </div>

                    <!-- Featured Heading -->
                    <h2 class="text-4xl md:text-5xl font-bold leading-tight">
                      {{ slide.featuredTitle || 'Innovation At Your Fingertips' }}
                    </h2>

                    <!-- Optional Featured Description -->
                    <p v-if="slide.featuredDescription" class="text-lg text-white/90">
                      {{ slide.featuredDescription }}
                    </p>
                  </div>

                  <!-- Decorative Elements -->
                  <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                  <div class="absolute -top-20 -left-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Arrows -->
    <div v-if="data.showArrows && data.slides && data.slides.length > 1" class="absolute inset-y-0 left-0 right-0 flex items-center justify-between px-4 md:px-8 pointer-events-none z-40">
      <button
        @click="previousSlide"
        class="pointer-events-auto w-12 h-12 md:w-14 md:h-14 bg-white hover:bg-gray-100 rounded-full shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110 group border border-gray-200"
        aria-label="Previous slide"
      >
        <svg class="w-6 h-6 md:w-7 md:h-7 text-gray-800 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>
      <button
        @click="nextSlide"
        class="pointer-events-auto w-12 h-12 md:w-14 md:h-14 bg-white hover:bg-gray-100 rounded-full shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110 group border border-gray-200"
        aria-label="Next slide"
      >
        <svg class="w-6 h-6 md:w-7 md:h-7 text-gray-800 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>

    <!-- Slide Indicators -->
    <div
      v-if="data.showIndicators && data.slides && data.slides.length > 1"
      class="absolute bottom-6 md:bottom-8 left-0 right-0 flex justify-center gap-2 md:gap-3 z-40"
    >
      <button
        v-for="(slide, index) in data.slides"
        :key="index"
        @click="goToSlide(index)"
        class="transition-all duration-300 rounded-full"
        :class="currentSlide === index
          ? 'bg-slate-900 w-10 md:w-12 h-3'
          : 'bg-gray-300 hover:bg-gray-400 w-3 h-3'"
        :aria-label="`Go to slide ${index + 1}`"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

interface Slide {
  backgroundImage?: string
  backgroundOverlay?: 'none' | 'light' | 'medium' | 'dark' | 'gradient'
  preHeading?: string
  heading: string
  headingColor?: string
  subheading?: string
  subheadingColor?: string
  buttonText?: string
  buttonLink?: string
  secondaryButtonText?: string
  secondaryButtonLink?: string
  showFeaturedCard?: boolean
  featuredTitle?: string
  featuredDescription?: string
  featuredImage?: string
  featuredImageMode?: 'background' | 'split' | 'stacked' | 'none'
  featuredGradient?: string
  contentAlign?: 'left' | 'center' | 'right'
}

interface HeroSliderData {
  slides: Slide[]
  autoplay?: boolean
  interval?: number
  showIndicators?: boolean
  showArrows?: boolean
  height?: 'medium' | 'large' | 'xlarge' | 'full'
  transition?: 'slide' | 'fade' | 'zoom'
  transitionSpeed?: number
}

const props = defineProps<{
  data: HeroSliderData
  blockId: string
}>()

const currentSlide = ref(0)
let autoplayInterval: NodeJS.Timeout | null = null

const heightClass = computed(() => {
  const heightMap: Record<string, string> = {
    medium: 'min-h-[600px]',
    large: 'min-h-[700px]',
    xlarge: 'min-h-[800px]',
    full: 'min-h-screen',
  }
  return heightMap[props.data.height || 'large'] || heightMap.large
})

const formatHeading = (heading: string) => {
  if (!heading) return 'Welcome'

  // Split heading and apply gradient to specific words
  const gradientWords = ['Our Platform', 'Platform', 'Our']
  let formattedHeading = heading

  gradientWords.forEach(word => {
    const regex = new RegExp(`\\b${word}\\b`, 'gi')
    formattedHeading = formattedHeading.replace(
      regex,
      `<span class="bg-gradient-to-r from-purple-600 to-purple-400 bg-clip-text text-transparent">${word}</span>`
    )
  })

  return formattedHeading
}

const getFeaturedCardClasses = (slide: Slide) => {
  // Add flex classes based on layout mode
  if (slide.featuredImageMode === 'stacked') {
    return 'flex flex-col'
  }
  return ''
}

const getOverlayClass = (overlay: string) => {
  const overlayMap: Record<string, string> = {
    none: '',
    light: 'bg-white/30',
    medium: 'bg-black/40',
    dark: 'bg-black/60',
    gradient: 'bg-gradient-to-r from-black/70 via-black/40 to-black/20'
  }
  return overlayMap[overlay] || ''
}

const nextSlide = () => {
  if (!props.data.slides || props.data.slides.length === 0) return
  currentSlide.value = (currentSlide.value + 1) % props.data.slides.length
}

const previousSlide = () => {
  if (!props.data.slides || props.data.slides.length === 0) return
  currentSlide.value = (currentSlide.value - 1 + props.data.slides.length) % props.data.slides.length
}

const goToSlide = (index: number) => {
  currentSlide.value = index
}

const startAutoplay = () => {
  if (props.data.autoplay && props.data.slides && props.data.slides.length > 1) {
    const intervalMs = (props.data.interval || 6) * 1000
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
</script>

<style scoped>
.slide-content {
  animation: fadeIn 0.6s ease-out;
}

.featured-card {
  animation: slideInRight 0.8s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(30px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}
</style>