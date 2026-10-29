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

      <div v-if="data.layout === 'grid'" :class="gridClass">
        <ServiceCard
          v-for="service in data.items"
          :key="service.id"
          :service="service"
          :show-image="shouldShowIcons"
          :show-description="shouldShowDescription"
          :show-read-more="shouldShowReadMore"
        />
      </div>

      <div v-else-if="data.layout === 'list'" class="max-w-4xl mx-auto space-y-6">
        <NuxtLink
          v-for="service in data.items"
          :key="service.id"
          :to="`/services/${service.slug}`"
          class="flex gap-6 bg-white border border-gray-200 rounded-xl hover:shadow-lg transition-all duration-300 p-6 group"
        >
          <template v-if="shouldShowIcons">
            <div v-if="service.featured_image" class="w-48 h-32 flex-shrink-0">
              <img
                :src="getImageUrl(service.featured_image)"
                :alt="service.name"
                class="w-full h-full object-cover rounded-lg"
                loading="lazy"
              />
            </div>
            <div v-else class="w-16 h-16 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
          </template>

          <div class="flex-1">
            <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-primary transition">
              {{ service.name }}
            </h3>
            <p v-if="shouldShowDescription" class="text-gray-600 line-clamp-3">
              {{ service.description }}
            </p>
            <div v-if="shouldShowReadMore" class="inline-flex items-center gap-2 font-semibold mt-3" :style="{ color: 'var(--color-accent)' }">
              <span>Read more</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
              </svg>
            </div>
          </div>
        </NuxtLink>
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
    columns?: string
    showIcons?: boolean
    showDescription?: boolean
    showReadMore?: boolean
    // Backward compatibility with old property names
    showImage?: boolean
    showExcerpt?: boolean
  }
  blockId: string
}>()

// Use new property names, fallback to old ones for backward compatibility
const shouldShowIcons = computed(() => props.data.showIcons ?? props.data.showImage ?? true)
const shouldShowDescription = computed(() => props.data.showDescription ?? props.data.showExcerpt ?? true)
const shouldShowReadMore = computed(() => props.data.showReadMore ?? true)

const { getImageUrl } = useImageUrl()

const gridClass = computed(() => {
  const colMap: Record<string, string> = {
    '2': 'grid grid-cols-1 md:grid-cols-2 gap-8',
    '3': 'grid grid-cols-1 md:grid-cols-3 gap-8',
    '4': 'grid grid-cols-1 md:grid-cols-4 gap-6',
  }
  return colMap[props.data.columns || '3'] || colMap['3']
})
</script>
