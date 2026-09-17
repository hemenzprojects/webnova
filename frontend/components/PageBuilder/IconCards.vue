<template>
  <section class="py-16 md:py-24 bg-gray-50">
    <div class="container mx-auto px-6 lg:px-12">
      <!-- Optional Section Heading -->
      <div v-if="data.heading" class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900">{{ data.heading }}</h2>
        <p v-if="data.subheading" class="mt-4 text-lg text-gray-600">{{ data.subheading }}</p>
      </div>

      <!-- Cards Grid -->
      <div :class="gridClass">
        <div
          v-for="(card, index) in data.cards"
          :key="index"
          class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300"
        >
          <!-- Colored Top Border -->
          <div
            class="h-1"
            :style="{ backgroundColor: card.borderColor || '#3B82F6' }"
          ></div>

          <!-- Card Content -->
          <div class="p-8">
            <!-- Icon -->
            <div v-if="card.icon" class="mb-6">
              <div
                class="w-16 h-16 flex items-center justify-center rounded-full"
                :style="getIconStyle(card.iconColor)"
              >
                <!-- Rocket Icon -->
                <svg v-if="card.icon === 'rocket'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>

                <!-- Eye Icon -->
                <svg v-else-if="card.icon === 'eye'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>

                <!-- Star Icon -->
                <svg v-else-if="card.icon === 'star'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>

                <!-- Heart Icon -->
                <svg v-else-if="card.icon === 'heart'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>

                <!-- Lightbulb Icon -->
                <svg v-else-if="card.icon === 'lightbulb'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>

                <!-- Target Icon -->
                <svg v-else-if="card.icon === 'target'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                </svg>

                <!-- Shield Icon -->
                <svg v-else-if="card.icon === 'shield'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>

                <!-- Users Icon -->
                <svg v-else-if="card.icon === 'users'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
            </div>

            <!-- Heading -->
            <h3 v-if="card.title" class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
              {{ card.title }}
            </h3>

            <!-- Description -->
            <p v-if="card.description" class="text-gray-600 leading-relaxed">
              {{ card.description }}
            </p>

            <!-- Optional Link -->
            <NuxtLink
              v-if="card.link && card.linkText"
              :to="card.link"
              class="inline-flex items-center mt-4 text-primary font-semibold hover:text-primary-dark transition-colors"
            >
              {{ card.linkText }}
              <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
              </svg>
            </NuxtLink>
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
    cards: Array<{
      icon?: string
      iconColor?: string
      borderColor?: string
      title?: string
      description?: string
      link?: string
      linkText?: string
    }>
    columns?: '1' | '2' | '3' | '4'
  }
  blockId: string
}>()

const gridClass = computed(() => {
  const columnMap: Record<string, string> = {
    '1': 'grid grid-cols-1 gap-8',
    '2': 'grid grid-cols-1 md:grid-cols-2 gap-8',
    '3': 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8',
    '4': 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8',
  }
  return columnMap[props.data.columns || '2'] || columnMap['2']
})

const getIconStyle = (color?: string) => {
  const hexColor = color || '#3B82F6' // Default to blue

  // Convert hex to RGB for opacity
  const r = parseInt(hexColor.slice(1, 3), 16)
  const g = parseInt(hexColor.slice(3, 5), 16)
  const b = parseInt(hexColor.slice(5, 7), 16)

  return {
    backgroundColor: `rgba(${r}, ${g}, ${b}, 0.1)`,
    color: hexColor
  }
}
</script>

<style scoped>
/* Additional custom styles if needed */
</style>