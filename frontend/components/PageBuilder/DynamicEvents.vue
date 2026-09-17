<template>
  <section class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <h2 v-if="data.heading" class="text-4xl font-bold text-gray-900 text-center mb-12">
        {{ data.heading }}
      </h2>

      <div v-if="data.layout === 'grid'" :class="gridClass">
        <NuxtLink
          v-for="event in data.items"
          :key="event.id"
          :to="`/events/${event.slug}`"
          class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300 group"
        >
          <div v-if="event.featured_image" class="aspect-video w-full overflow-hidden bg-gray-100">
            <img
              :src="getImageUrl(event.featured_image)"
              :alt="event.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              loading="lazy"
            />
          </div>

          <div class="p-6">
            <div class="flex items-center gap-2 text-sm text-accent font-semibold mb-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>{{ formatDate(event.start_date) }}</span>
            </div>

            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition">
              {{ event.title }}
            </h3>

            <p v-if="event.description" class="text-gray-600 mb-4">
              {{ truncate(event.description, 120) }}
            </p>

            <div v-if="data.showLocation && (event.location || event.venue)" class="flex items-center gap-2 text-sm text-gray-500">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{{ event.venue || event.location }}</span>
            </div>
          </div>
        </NuxtLink>
      </div>

      <div v-else-if="data.layout === 'list'" class="max-w-4xl mx-auto space-y-6">
        <NuxtLink
          v-for="event in data.items"
          :key="event.id"
          :to="`/events/${event.slug}`"
          class="flex gap-6 bg-white border border-gray-200 rounded-xl hover:shadow-lg transition-all duration-300 p-6 group"
        >
          <div class="flex-1">
            <div class="flex items-center gap-2 text-sm text-accent font-semibold mb-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>{{ formatDate(event.start_date) }}</span>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-primary transition">
              {{ event.title }}
            </h3>
            <p v-if="event.description" class="text-gray-600 mb-3">
              {{ truncate(event.description, 150) }}
            </p>
            <div v-if="data.showLocation && (event.location || event.venue)" class="text-sm text-gray-500">
              {{ event.venue || event.location }}
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
    items: any[]
    limit: number
    layout: 'grid' | 'list' | 'timeline'
    filter: 'upcoming' | 'past' | 'all'
    showLocation: boolean
  }
  blockId: string
}>()

const { getImageUrl } = useImageUrl()

const gridClass = computed(() => {
  return 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8'
})

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const truncate = (text: string, length: number) => {
  if (text.length <= length) return text
  return text.substring(0, length) + '...'
}
</script>
