<template>
  <section class="py-20 bg-gray-50">
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
        <NuxtLink
          v-for="item in data.items"
          :key="item.id"
          :to="`/news/${item.slug}`"
          class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 group overflow-hidden flex flex-col"
        >
          <div v-if="data.showImage && item.featured_image" class="aspect-video w-full overflow-hidden bg-gray-100">
            <img
              :src="getImageUrl(item.featured_image)"
              :alt="item.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              loading="lazy"
            />
          </div>

          <div class="p-6 flex-1 flex flex-col">
            <div v-if="data.showDate" class="flex items-center gap-2 text-sm text-gray-500 mb-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>{{ formatDate(item.published_at) }}</span>
            </div>

            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition">
              {{ item.title }}
            </h3>

            <p v-if="data.showExcerpt" class="text-gray-600 mb-4 flex-1">
              {{ item.excerpt }}
            </p>

            <div v-if="data.showReadMore" class="inline-flex items-center gap-2 font-semibold" :style="{ color: 'var(--color-accent)' }">
              <span>Read more</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
              </svg>
            </div>
          </div>
        </NuxtLink>
      </div>

      <div v-else-if="data.layout === 'list'" class="max-w-4xl mx-auto space-y-6">
        <NuxtLink
          v-for="item in data.items"
          :key="item.id"
          :to="`/news/${item.slug}`"
          class="flex gap-6 bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 group"
        >
          <div v-if="data.showImage && item.featured_image" class="w-48 h-32 flex-shrink-0">
            <img
              :src="getImageUrl(item.featured_image)"
              :alt="item.title"
              class="w-full h-full object-cover rounded-lg"
              loading="lazy"
            />
          </div>
          <div class="flex-1">
            <div v-if="data.showDate" class="text-sm text-gray-500 mb-1">
              {{ formatDate(item.published_at) }}
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-primary transition">
              {{ item.title }}
            </h3>
            <p v-if="data.showExcerpt" class="text-gray-600">
              {{ item.excerpt }}
            </p>
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
    limit: number
    layout: 'grid' | 'list' | 'carousel'
    columns: string
    showImage: boolean
    showExcerpt: boolean
    showDate: boolean
    showReadMore: boolean
  }
  blockId: string
}>()

const { getImageUrl } = useImageUrl()

const gridClass = computed(() => {
  const colMap: Record<string, string> = {
    '2': 'grid grid-cols-1 md:grid-cols-2 gap-8',
    '3': 'grid grid-cols-1 md:grid-cols-3 gap-8',
    '4': 'grid grid-cols-1 md:grid-cols-4 gap-6',
  }
  return colMap[props.data.columns] || colMap['3']
})

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}
</script>
