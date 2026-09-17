<template>
  <section class="py-20 bg-white">
    <div class="container mx-auto px-4">
      <h2 v-if="data.heading" class="text-4xl font-bold text-gray-900 text-center mb-12">
        {{ data.heading }}
      </h2>

      <div :class="gridClass">
        <component
          :is="card.link ? 'a' : 'div'"
          v-for="(card, index) in data.cards"
          :key="`card-${index}`"
          :href="card.link || undefined"
          :target="card.link ? '_blank' : undefined"
          :rel="card.link ? 'noopener noreferrer' : undefined"
          class="group bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-xl transition-all duration-300"
          :class="{ 'cursor-pointer': card.link }"
        >
          <div v-if="card.image" class="aspect-video w-full overflow-hidden bg-gray-100">
            <img
              :src="getCardImageUrl(card.image)"
              :alt="card.title"
              class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
              loading="lazy"
            />
          </div>

          <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition">
              {{ card.title }}
            </h3>
            <p v-if="card.description" class="text-gray-600 leading-relaxed">
              {{ card.description }}
            </p>
          </div>
        </component>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    heading?: string
    cards: Array<{
      image?: string
      title: string
      description?: string
      link?: string
    }>
    columns: '2' | '3' | '4'
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()

const gridClass = computed(() => {
  const columnsMap: Record<string, string> = {
    '2': 'grid grid-cols-1 md:grid-cols-2 gap-8',
    '3': 'grid grid-cols-1 md:grid-cols-3 gap-8',
    '4': 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6',
  }
  return columnsMap[props.data.columns] || columnsMap['3']
})

const getCardImageUrl = (path: string | undefined) => {
  if (!path) return null
  return transformImageUrl(path)
}
</script>
