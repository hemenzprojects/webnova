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

          <!-- Background Image Upload -->
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Background Image</label>

            <!-- Image Preview or Upload Button -->
            <div v-if="slide.backgroundImage" class="space-y-2">
              <div class="relative border border-gray-200 rounded-md p-2 group">
                <img
                  :src="slide.backgroundImage"
                  alt="Background preview"
                  class="w-full h-32 object-cover rounded"
                  @error="(e) => (e.target as HTMLImageElement).src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3EImage not found%3C/text%3E%3C/svg%3E'"
                />
                <button
                  @click="slide.backgroundImage = ''; handleUpdate()"
                  class="absolute top-3 right-3 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <button
                @click="openBackgroundImagePicker(index)"
                class="w-full px-3 py-2 text-sm text-blue-600 hover:text-blue-700 border border-blue-300 hover:border-blue-400 rounded-md transition-colors"
              >
                Change Background
              </button>
            </div>

            <div v-else class="space-y-2">
              <button
                @click="openBackgroundImagePicker(index)"
                class="w-full px-4 py-8 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-colors group"
              >
                <div class="flex flex-col items-center gap-2">
                  <svg class="w-8 h-8 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <span class="text-sm font-medium text-gray-600 group-hover:text-blue-600">Upload Background</span>
                  <span class="text-xs text-gray-500">or enter URL below</span>
                </div>
              </button>

              <!-- URL Input Alternative -->
              <div class="relative">
                <input
                  v-model="slide.backgroundImage"
                  @input="handleUpdate"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Or paste background image URL"
                />
              </div>
            </div>
          </div>

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
            <p class="text-xs text-gray-500 mt-1">Tip: Words like "Our Platform" will get auto-styled with gradient</p>
          </div>

          <!-- Heading Color -->
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Heading Color</label>
            <input
              v-model="slide.headingColor"
              @input="handleUpdate"
              type="color"
              class="w-full h-10 border border-gray-300 rounded-md cursor-pointer"
            />
          </div>

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

          <!-- Subheading Color -->
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Subheading Color</label>
            <input
              v-model="slide.subheadingColor"
              @input="handleUpdate"
              type="color"
              class="w-full h-10 border border-gray-300 rounded-md cursor-pointer"
            />
          </div>

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

            <!-- Featured Image Upload -->
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Featured Image</label>

              <!-- Image Preview or Upload Button -->
              <div v-if="slide.featuredImage" class="space-y-2">
                <div class="relative border border-gray-200 rounded-md p-2 group">
                  <img
                    :src="slide.featuredImage"
                    alt="Featured preview"
                    class="w-full h-32 object-cover rounded"
                    @error="(e) => (e.target as HTMLImageElement).src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23ddd%22 width=%22100%22 height=%22100%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22%3EImage not found%3C/text%3E%3C/svg%3E'"
                  />
                  <button
                    @click="slide.featuredImage = ''; handleUpdate()"
                    class="absolute top-3 right-3 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                <button
                  @click="openImagePicker(index)"
                  class="w-full px-3 py-2 text-sm text-blue-600 hover:text-blue-700 border border-blue-300 hover:border-blue-400 rounded-md transition-colors"
                >
                  Change Image
                </button>
              </div>

              <div v-else class="space-y-2">
                <button
                  @click="openImagePicker(index)"
                  class="w-full px-4 py-8 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition-colors group"
                >
                  <div class="flex flex-col items-center gap-2">
                    <svg class="w-8 h-8 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-600 group-hover:text-blue-600">Upload Image</span>
                    <span class="text-xs text-gray-500">or enter URL below</span>
                  </div>
                </button>

                <!-- URL Input Alternative -->
                <div class="relative">
                  <input
                    v-model="slide.featuredImage"
                    @input="handleUpdate"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Or paste image URL"
                  />
                </div>
              </div>
            </div>

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

const localData = ref({
  slides: props.widget.data.slides || [{
    backgroundImage: '',
    backgroundOverlay: 'gradient',
    preHeading: '',
    heading: 'Welcome to Our Platform',
    headingColor: '#1e293b',
    subheading: 'Discover amazing features and services designed to help you succeed.',
    subheadingColor: '#64748b',
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
  emit('update', { ...localData.value })
}

const openImagePicker = (slideIndex: number) => {
  // Create a file input element
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'

  input.onchange = (e: Event) => {
    const target = e.target as HTMLInputElement
    const file = target.files?.[0]

    if (file) {
      // For now, we'll use a data URL. In production, you'd upload to server
      const reader = new FileReader()
      reader.onload = (event) => {
        const imageUrl = event.target?.result as string
        localData.value.slides[slideIndex].featuredImage = imageUrl
        handleUpdate()
      }
      reader.readAsDataURL(file)
    }
  }

  input.click()
}

const openBackgroundImagePicker = (slideIndex: number) => {
  // Create a file input element
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'

  input.onchange = (e: Event) => {
    const target = e.target as HTMLInputElement
    const file = target.files?.[0]

    if (file) {
      const reader = new FileReader()
      reader.onload = (event) => {
        const imageUrl = event.target?.result as string
        localData.value.slides[slideIndex].backgroundImage = imageUrl
        // Set default overlay for better text readability
        if (!localData.value.slides[slideIndex].backgroundOverlay) {
          localData.value.slides[slideIndex].backgroundOverlay = 'gradient'
        }
        handleUpdate()
      }
      reader.readAsDataURL(file)
    }
  }

  input.click()
}

const addSlide = () => {
  localData.value.slides.push({
    backgroundImage: '',
    backgroundOverlay: 'gradient',
    preHeading: '',
    heading: 'New Slide',
    headingColor: '#1e293b',
    subheading: 'Enter your slide description here',
    subheadingColor: '#64748b',
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
      slides: newData.slides || localData.value.slides,
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