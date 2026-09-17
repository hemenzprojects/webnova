<template>
  <section class="py-16 md:py-24 bg-white">
    <div class="container mx-auto px-6 lg:px-12">
      <!-- Section Heading -->
      <div v-if="data.heading" class="text-center mb-16">
        <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold" :style="{ color: data.headingColor || '#1E40AF' }">
          {{ data.heading }}
        </h2>
      </div>

      <!-- Timeline Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 auto-rows-auto">
        <div
          v-for="(item, index) in data.items"
          :key="index"
          :class="[
            'rounded-2xl shadow-md p-8 md:p-10 relative overflow-hidden transition-all duration-300 hover:shadow-xl',
            getItemSizeClass(item.size)
          ]"
          :style="getItemStyle(item)"
        >
          <!-- Background Year Watermark -->
          <div
            v-if="item.showIcon !== false"
            class="absolute bottom-0 right-0 opacity-5 font-bold pointer-events-none select-none leading-none"
            :style="{
              color: getWatermarkColor(item.backgroundColor),
              fontSize: item.size === 'full' ? '12rem' : '10rem',
              lineHeight: '1'
            }"
          >
            {{ item.year || index + 1 }}
          </div>

          <!-- Optional Icon (for full-width cards, positioned on the right) -->
          <div
            v-if="item.icon && item.icon !== 'none' && item.size === 'full'"
            class="absolute top-1/2 right-8 md:right-16 transform -translate-y-1/2"
          >
            <!-- Globe Icon -->
            <svg v-if="item.icon === 'globe'" class="w-32 h-32 md:w-40 md:h-40 opacity-40" :style="{ color: getIconColorForDisplay(item.backgroundColor) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>

            <!-- Star Icon -->
            <svg v-else-if="item.icon === 'star'" class="w-32 h-32 md:w-40 md:h-40 opacity-40" :style="{ color: getIconColorForDisplay(item.backgroundColor) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>

            <!-- Trophy Icon -->
            <svg v-else-if="item.icon === 'trophy'" class="w-32 h-32 md:w-40 md:h-40 opacity-40" :style="{ color: getIconColorForDisplay(item.backgroundColor) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
            </svg>

            <!-- Rocket Icon -->
            <svg v-else-if="item.icon === 'rocket'" class="w-32 h-32 md:w-40 md:h-40 opacity-40" :style="{ color: getIconColorForDisplay(item.backgroundColor) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>

            <!-- Users Icon -->
            <svg v-else-if="item.icon === 'users'" class="w-32 h-32 md:w-40 md:h-40 opacity-40" :style="{ color: getIconColorForDisplay(item.backgroundColor) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>

            <!-- Lightbulb Icon -->
            <svg v-else-if="item.icon === 'lightbulb'" class="w-32 h-32 md:w-40 md:h-40 opacity-40" :style="{ color: getIconColorForDisplay(item.backgroundColor) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
          </div>

          <!-- Content -->
          <div class="relative z-10" :class="item.size === 'full' ? 'max-w-2xl' : ''">
            <!-- Year -->
            <div v-if="item.year" class="text-5xl md:text-6xl font-bold mb-4" :style="{ color: item.yearColor || getYearColor(item.backgroundColor) }">
              {{ item.year }}
            </div>

            <!-- Title -->
            <h3 v-if="item.title" class="text-2xl md:text-3xl font-bold mb-6" :style="{ color: getTextColor(item.backgroundColor) }">
              {{ item.title }}
            </h3>

            <!-- Description -->
            <p v-if="item.description" class="text-base md:text-lg leading-relaxed" :style="{ color: getDescriptionColor(item.backgroundColor) }">
              {{ item.description }}
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
    headingColor?: string
    items: Array<{
      year?: string
      yearColor?: string
      title?: string
      description?: string
      backgroundColor?: string
      size?: 'normal' | 'large' | 'full'
      showIcon?: boolean
      icon?: 'globe' | 'star' | 'trophy' | 'rocket' | 'users' | 'lightbulb' | 'none'
    }>
  }
  blockId: string
}>()

const getItemSizeClass = (size?: string) => {
  const sizeMap: Record<string, string> = {
    normal: '',
    large: 'md:col-span-1',
    full: 'md:col-span-2',
  }
  return sizeMap[size || 'normal'] || sizeMap.normal
}

const getItemStyle = (item: any) => {
  return {
    backgroundColor: item.backgroundColor || '#E5E7EB'
  }
}

const calculateBrightness = (hexColor: string): number => {
  const hex = hexColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)
  return (r * 299 + g * 587 + b * 114) / 1000
}

const getYearColor = (bgColor?: string) => {
  if (bgColor?.startsWith('#')) {
    const brightness = calculateBrightness(bgColor)
    // For dark backgrounds, use white; for light backgrounds, use dark blue
    return brightness < 128 ? '#FFFFFF' : '#1E40AF'
  }
  return '#1E40AF'
}

const getTextColor = (bgColor?: string) => {
  if (bgColor?.startsWith('#')) {
    const brightness = calculateBrightness(bgColor)
    return brightness < 128 ? '#FFFFFF' : '#1E40AF'
  }
  return '#1E40AF'
}

const getDescriptionColor = (bgColor?: string) => {
  if (bgColor?.startsWith('#')) {
    const brightness = calculateBrightness(bgColor)
    // For descriptions, use slightly lighter/darker tones
    return brightness < 128 ? 'rgba(255, 255, 255, 0.9)' : 'rgba(55, 65, 81, 0.9)'
  }
  return 'rgba(55, 65, 81, 0.9)'
}

const getWatermarkColor = (bgColor?: string) => {
  if (bgColor?.startsWith('#')) {
    const brightness = calculateBrightness(bgColor)
    return brightness < 128 ? '#FFFFFF' : '#1F2937'
  }
  return '#1F2937'
}

const getIconColorForDisplay = (bgColor?: string) => {
  if (bgColor?.startsWith('#')) {
    const brightness = calculateBrightness(bgColor)
    return brightness < 128 ? '#FFFFFF' : '#1E40AF'
  }
  return '#1E40AF'
}
</script>

<style scoped>
/* Additional custom styles if needed */
</style>