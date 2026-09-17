<template>
  <section class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <div v-if="data.heading || data.subheading" class="text-center mb-12">
        <h2 v-if="data.heading" class="text-4xl font-bold text-gray-900 mb-4">
          {{ data.heading }}
        </h2>
        <p v-if="data.subheading" class="text-gray-600 max-w-2xl mx-auto">
          {{ data.subheading }}
        </p>
      </div>

      <div v-if="data.layout === 'grid'" class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div
          v-for="service in data.items"
          :key="service.id"
          class="group p-8 border border-gray-200 rounded-2xl hover:border-accent hover:shadow-xl transition-all duration-300"
        >
          <div v-if="shouldShowIcons" class="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>

          <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition">
            {{ service.name }}
          </h3>

          <p v-if="shouldShowDescription" class="text-gray-600 leading-relaxed">
            {{ service.description }}
          </p>
        </div>
      </div>

      <div v-else-if="data.layout === 'list'" class="max-w-4xl mx-auto space-y-6">
        <div
          v-for="service in data.items"
          :key="service.id"
          class="flex gap-6 bg-white border border-gray-200 rounded-xl hover:shadow-lg transition-all duration-300 p-6 group"
        >
          <div v-if="shouldShowIcons" class="w-16 h-16 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>

          <div class="flex-1">
            <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-primary transition">
              {{ service.name }}
            </h3>
            <p v-if="shouldShowDescription" class="text-gray-600">
              {{ service.description }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    heading?: string
    subheading?: string
    items: any[]
    limit: string | number
    layout: 'grid' | 'list'
    showIcons?: boolean
    showDescription?: boolean
    // Backward compatibility with old property names
    showImage?: boolean
    showExcerpt?: boolean
  }
  blockId: string
}>()

// Use new property names, fallback to old ones for backward compatibility
const shouldShowIcons = computed(() => props.data.showIcons ?? props.data.showImage ?? true)
const shouldShowDescription = computed(() => props.data.showDescription ?? props.data.showExcerpt ?? true)
</script>
