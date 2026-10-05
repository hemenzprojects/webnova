<template>
  <aside>
    <div class="bg-white rounded-2xl shadow-sm p-6 lg:sticky lg:top-24">
      <h2 class="text-xl font-bold text-gray-900 mb-4 pb-4 border-b border-gray-200">{{ title }}</h2>
      <ul class="space-y-2">
        <li v-for="item in items" :key="item.id">
          <NuxtLink
            :to="item.to"
            class="flex items-center gap-4 p-2 rounded-lg text-gray-700 font-medium hover:bg-primary/5 hover:text-primary transition group"
          >
            <div v-if="showImage" class="w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
              <img
                v-if="item.image"
                :src="getImageUrl(item.image)"
                :alt="item.label"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
              />
              <div v-else class="w-full h-full bg-gradient-to-br from-primary to-accent"></div>
            </div>
            <span class="flex-1 min-w-0">
              <span v-if="showDate && item.date" class="block text-xs font-normal text-gray-500 mb-1">{{ formatDate(item.date) }}</span>
              <span class="line-clamp-2">{{ item.label }}</span>
            </span>
          </NuxtLink>
        </li>
      </ul>
    </div>
  </aside>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  title: string
  items: { id: number | string; label: string; to: string; image?: string | null; date?: string }[]
  showImage?: boolean
  showDate?: boolean
}>(), {
  showImage: true,
  showDate: true,
})

const { getImageUrl } = useImageUrl()

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}
</script>
