<template>
  <div class="space-y-6">
    <div class="text-sm text-gray-700 bg-blue-50 border border-blue-200 rounded-lg p-3">
      <p class="font-medium text-blue-900">Hero Slider Editor</p>
      <p class="text-blue-700 mt-1">Create modern split-screen hero slides with featured content cards.</p>
    </div>

    <!-- Slides -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Slides</label>

      <div v-for="(slide, index) in localData.slides" :key="index" class="mb-4 border border-gray-200 rounded-lg p-4 bg-white">
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-sm font-semibold text-gray-900">Slide {{ index + 1 }}</h4>
          <button
            v-if="localData.slides.length > 1"
            @click="removeSlide(index)"
            class="text-red-600 hover:text-red-700 text-xs"
          >
            Remove
          </button>
        </div>

        <!-- Slide Background Section -->
        <div class="space-y-3 mb-4 pb-4 border-b border-gray-100">
          <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Slide Background</h5>

          <!-- Background Image: uploaded to the media library (Elementor/ImageUpload.vue) -->
          <ElementorImageUpload
            :model-value="slide.backgroundImage || ''"
            @update:model-value="setBackgroundImage(index, $event)"
            label="Background Image"
            type="hero"
          />

          <!-- Background Overlay -->
          <div v-if="slide.backgroundImage">
            <label class="block text-xs font-medium text-gray-600 mb-1">Background Overlay</label>
            <select
              v-model="slide.backgroundOverlay"
              @change="handleUpdate"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="none">No Overlay</option>
              <option value="light">Light (30%)</option>
              <option value="medium">Medium (40%)</option>
              <option value="dark">Dark (60%)</option>
              <option value="gradient">Gradient</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Overlay improves text readability</p>
          </div>
        </div>

        <!-- Left Content Section -->
        <div class="space-y-3 mb-4 pb-4 border-b border-gray-100">
          <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Left Content</h5>

          <!-- Pre-Heading (Optional) -->
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Pre-heading (Optional)</label>
            <input
              v-model="slide.preHeading"
              @input="handleUpdate"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="e.g., Welcome to"
            />
          </div>

          <!-- Main Heading -->
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Main Heading</label>
            <input
              v-model="slide.heading"
              @input="handleUpdate"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Enter main heading"
            />
          </div>

          <!-- Heading Color (empty: theme / Style tab colour) -->
          <ElementorThemeColorField
            :model-value="slide.headingColor"
            @update:model-value="slide.headingColor = $event; handleUpdate()"
            label="Heading Color"
            theme-var="--color-text"
          />

          <!-- Subheading -->
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Subheading / Description</label>
            <textarea
              v-model="slide.subheading"
              @input="handleUpdate"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Enter slide description"
            />
          </div>

          <!-- Subheading Color (empty: theme / Style tab colour) -->
          <ElementorThemeColorField
            :model-value="slide.subheadingColor"
            @update:model-value="slide.subheadingColor = $event; handleUpdate()"
            label="Subheading Color"
            theme-var="--color-text-muted"
          />

          <!-- Primary Button -->
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Primary Button</label>
              <input
                v-model="slide.buttonText"
                @input="handleUpdate"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Button text"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Button Link</label>
              <input
                v-model="slide.buttonLink"
                @input="handleUpdate"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="#"
              />
            </div>
          </div>

          <!-- Secondary Button -->
          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Secondary Button</label>
              <input
                v-model="slide.secondaryButtonText"
                @input="handleUpdate"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Button text"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Button Link</label>
              <input
                v-model="slide.secondaryButtonLink"
                @input="handleUpdate"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="#"
              />
            </div>
          </div>

          <!-- Content Alignment -->
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Content Align</label>
            <select
              v-model="slide.contentAlign"
              @change="handleUpdate"
              class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="left">Left</option>
              <option value="center">Center</option>
              <option value="right">Right</option>
            </select>
          </div>
        </div>

        <!-- Right Featured Card Section -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h5 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Featured Card (Right Side)</h5>

            <!-- Toggle Show/Hide Featured Card -->
            <label class="flex items-center gap-2 cursor-pointer">
              <span class="text-xs text-gray-600">Show Card</span>
              <input
                v-model="slide.showFeaturedCard"
                @change="handleUpdate"
                type="checkbox"
                class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
              />
            </label>
          </div>

          <template v-if="slide.showFeaturedCard !== false">
            <!-- Featured Title -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Featured Title</label>
              <input
                v-model="slide.featuredTitle"
                @input="handleUpdate"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="e.g., Innovation At Your Fingertips"
              />
            </div>

            <!-- Featured Description (Optional) -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Featured Description (Optional)</label>
              <textarea
                v-model="slide.featuredDescription"
                @input="handleUpdate"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Optional description for featured card"
              />
            </div>

            <!-- Featured Image: uploaded to the media library -->
            <ElementorImageUpload
              :model-value="slide.featuredImage || ''"
              @update:model-value="slide.featuredImage = $event; handleUpdate()"
              label="Featured Image"
              type="featured"
            />

            <!-- Image Display Mode -->
            <div v-if="slide.featuredImage">
              <label class="block text-xs font-medium text-gray-600 mb-1">Image Display Mode</label>
              <select
                v-model="slide.featuredImageMode"
                @change="handleUpdate"
                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="background">Background (text overlay)</option>
                <option value="split">Split (image left, text right)</option>
                <option value="stacked">Stacked (image top, text bottom)</option>
              </select>
            </div>
          </template>

          <div v-else class="text-center py-4 text-sm text-gray-500 bg-gray-50 rounded-md border border-gray-200">
            Featured card is hidden. Toggle "Show Card" to enable.
          </div>
        </div>
      </div>

      <button
        @click="addSlide"
        class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-600 hover:border-blue-500 hover:text-blue-600 transition-colors"
      >
        + Add Slide
      </button>
    </div>

    <!-- Slider Settings -->
    <div class="border-t pt-4">
      <h3 class="text-sm font-semibold text-gray-900 mb-3">Slider Settings</h3>

      <div class="space-y-3">
        <!-- Autoplay -->
        <div class="flex items-center justify-between">
          <label class="text-sm text-gray-700">Autoplay</label>
          <input
            v-model="localData.autoplay"
            @change="handleUpdate"
            type="checkbox"
            class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Interval (if autoplay) -->
        <div v-if="localData.autoplay" class="flex items-center justify-between">
          <label class="text-sm text-gray-700">Interval (seconds)</label>
          <input
            v-model.number="localData.interval"
            @input="handleUpdate"
            type="number"
            min="3"
            max="20"
            class="w-20 px-3 py-1 border border-gray-300 rounded-md text-sm"
          />
        </div>

        <!-- Show Arrows -->
        <div class="flex items-center justify-between">
          <label class="text-sm text-gray-700">Show Arrows</label>
          <input
            v-model="localData.showArrows"
            @change="handleUpdate"
            type="checkbox"
            class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Show Indicators -->
        <div class="flex items-center justify-between">
          <label class="text-sm text-gray-700">Show Indicators</label>
          <input
            v-model="localData.showIndicators"
            @change="handleUpdate"
            type="checkbox"
            class="w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Height -->
        <div>
          <label class="block text-sm text-gray-700 mb-1">Slider Height</label>
          <select
            v-model="localData.height"
            @change="handleUpdate"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
          >
            <option value="medium">Medium (600px)</option>
            <option value="large">Large (700px)</option>
            <option value="xlarge">Extra Large (800px)</option>
            <option value="full">Full Screen</option>
          </select>
        </div>

        <!-- Transition -->
        <div>
          <label class="block text-sm text-gray-700 mb-1">Transition Effect</label>
          <select
            v-model="localData.transition"
            @change="handleUpdate"
            class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
          >
            <option value="slide">Slide</option>
            <option value="fade">Fade</option>
            <option value="zoom">Zoom</option>
          </select>
        </div>
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

// Colours older slides were given by default mean "theme colour" (as in PageBuilder/HeroSlider.vue).
// Saving marks the slider with themeColors, after which any colour, those two included, is kept as chosen.
const LEGACY_COLORS: Record<string, string> = { headingColor: '#1e293b', subheadingColor: '#64748b' }
const normaliseSlides = (slides: any[]) => props.widget.data.themeColors ? slides : slides.map((slide) => {
  const out = { ...slide }
  for (const [key, legacy] of Object.entries(LEGACY_COLORS)) {
    if (typeof out[key] === 'string' && out[key].toLowerCase() === legacy) out[key] = ''
  }
  return out
})

const localData = ref({
  slides: props.widget.data.slides ? normaliseSlides(props.widget.data.slides) : [{
    backgroundImage: '',
    backgroundOverlay: 'gradient',
    preHeading: '',
    heading: 'Welcome to Our Platform',
    headingColor: '',
    subheading: 'Discover amazing features and services designed to help you succeed.',
    subheadingColor: '',
    buttonText: 'Get Started',
    buttonLink: '#',
    secondaryButtonText: 'Learn More',
    secondaryButtonLink: '#',
    showFeaturedCard: true,
    featuredTitle: 'Innovation At Your Fingertips',
    featuredDescription: '',
    featuredImage: '',
    featuredImageMode: 'background',
    contentAlign: 'left'
  }],
  autoplay: props.widget.data.autoplay ?? true,
  interval: props.widget.data.interval || 6,
  showIndicators: props.widget.data.showIndicators ?? true,
  showArrows: props.widget.data.showArrows ?? true,
  height: props.widget.data.height || 'large',
  transition: props.widget.data.transition || 'fade',
  transitionSpeed: props.widget.data.transitionSpeed || 700
})

const handleUpdate = () => {
  emit('update', { ...localData.value, themeColors: true })
}

const setBackgroundImage = (slideIndex: number, path: string) => {
  const slide = localData.value.slides[slideIndex]
  slide.backgroundImage = path
  // Default overlay so the text stays readable on a photo
  if (path && !slide.backgroundOverlay) slide.backgroundOverlay = 'gradient'
  handleUpdate()
}

const addSlide = () => {
  localData.value.slides.push({
    backgroundImage: '',
    backgroundOverlay: 'gradient',
    preHeading: '',
    heading: 'New Slide',
    headingColor: '',
    subheading: 'Enter your slide description here',
    subheadingColor: '',
    buttonText: 'Learn More',
    buttonLink: '#',
    secondaryButtonText: '',
    secondaryButtonLink: '',
    showFeaturedCard: true,
    featuredTitle: 'Featured Content',
    featuredDescription: '',
    featuredImage: '',
    featuredImageMode: 'background',
    contentAlign: 'left'
  })
  handleUpdate()
}

const removeSlide = (index: number) => {
  if (localData.value.slides.length > 1) {
    localData.value.slides.splice(index, 1)
    handleUpdate()
  }
}

// Watch for external changes
watch(() => props.widget.data, (newData) => {
  if (newData && JSON.stringify(newData) !== JSON.stringify(localData.value)) {
    localData.value = {
      slides: newData.slides ? normaliseSlides(newData.slides) : localData.value.slides,
      autoplay: newData.autoplay ?? localData.value.autoplay,
      interval: newData.interval || localData.value.interval,
      showIndicators: newData.showIndicators ?? localData.value.showIndicators,
      showArrows: newData.showArrows ?? localData.value.showArrows,
      height: newData.height || localData.value.height,
      transition: newData.transition || localData.value.transition,
      transitionSpeed: newData.transitionSpeed || localData.value.transitionSpeed
    }
  }
}, { deep: true })
</script>