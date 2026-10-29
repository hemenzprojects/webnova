<template>
  <NuxtLink
    :to="`/services/${service.slug}`"
    class="bg-primary/5 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 group overflow-hidden flex flex-col h-full"
  >
    <div v-if="showImage" class="aspect-[3/2] w-full overflow-hidden bg-gray-100">
      <img
        v-if="service.featured_image"
        :src="getImageUrl(service.featured_image)"
        :alt="service.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        loading="lazy"
      />
      <div v-else class="w-full h-full bg-gradient-to-br from-primary to-accent flex items-center justify-center">
        <svg class="w-16 h-16 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
      </div>
    </div>

    <div class="p-6 flex-1 flex flex-col">
      <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition">
        {{ service.name }}
      </h3>

      <p v-if="showDescription && service.description" class="text-gray-600 leading-relaxed mb-5 line-clamp-3">
        {{ service.description }}
      </p>

      <div v-if="showReadMore" class="mt-auto inline-flex items-center gap-2 font-semibold" :style="{ color: 'var(--color-accent)' }">
        <span>Read more</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
        </svg>
      </div>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  service: any
  showImage?: boolean
  showDescription?: boolean
  showReadMore?: boolean
}>(), {
  showImage: true,
  showDescription: true,
  showReadMore: true,
})

const { getImageUrl } = useImageUrl()
</script>