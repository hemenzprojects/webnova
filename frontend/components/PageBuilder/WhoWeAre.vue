<template>
  <section class="py-12 md:py-16 bg-gray-50">
    <div class="container mx-auto px-6 lg:px-12">
      <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-start">
        <!-- Left Column: Content & Feature Cards -->
        <div>
          <!-- Section Heading -->
          <h2 v-if="data.heading" class="text-2xl md:text-3xl lg:text-4xl font-bold mb-4" :style="{ color: data.headingColor || '#1E40AF' }">
            {{ data.heading }}
          </h2>

          <!-- Description -->
          <p v-if="data.description" class="text-base md:text-lg text-gray-700 mb-8 leading-relaxed">
            {{ data.description }}
          </p>

          <!-- Feature Cards -->
          <div class="space-y-4">
            <div
              v-for="(feature, index) in data.features"
              :key="index"
              class="flex items-start gap-3 p-4 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300"
            >
              <!-- Icon -->
              <div class="flex-shrink-0">
                <div
                  class="w-10 h-10 flex items-center justify-center rounded-lg"
                  :style="{ backgroundColor: feature.iconBgColor || '#EBF5FF' }"
                >
                  <!-- Users Icon -->
                  <svg v-if="feature.icon === 'users'" class="w-5 h-5" :style="{ color: feature.iconColor || '#1E40AF' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>

                  <!-- Shield Icon -->
                  <svg v-else-if="feature.icon === 'shield'" class="w-5 h-5" :style="{ color: feature.iconColor || '#1E40AF' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>

                  <!-- Star Icon -->
                  <svg v-else-if="feature.icon === 'star'" class="w-5 h-5" :style="{ color: feature.iconColor || '#1E40AF' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                  </svg>

                  <!-- Heart Icon -->
                  <svg v-else-if="feature.icon === 'heart'" class="w-5 h-5" :style="{ color: feature.iconColor || '#1E40AF' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>

                  <!-- Globe Icon -->
                  <svg v-else-if="feature.icon === 'globe'" class="w-5 h-5" :style="{ color: feature.iconColor || '#1E40AF' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>

                  <!-- Lightbulb Icon -->
                  <svg v-else-if="feature.icon === 'lightbulb'" class="w-5 h-5" :style="{ color: feature.iconColor || '#1E40AF' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                  </svg>

                  <!-- Target Icon -->
                  <svg v-else class="w-5 h-5" :style="{ color: feature.iconColor || '#1E40AF' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                  </svg>
                </div>
              </div>

              <!-- Content -->
              <div class="flex-1">
                <h3 class="text-base font-bold mb-1.5" :style="{ color: data.featureTitleColor || '#1E40AF' }">
                  {{ feature.title }}
                </h3>
                <p class="text-sm text-gray-600 leading-relaxed">
                  {{ feature.description }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Masonry Grid of Stat Cards -->
        <div class="grid grid-cols-2 grid-rows-[140px_140px_140px] gap-4">
          <div
            v-for="(card, index) in data.statCards"
            :key="index"
            :class="[
              'rounded-2xl shadow-md transition-all duration-300 hover:shadow-lg relative overflow-hidden flex items-center justify-center',
              getCardGridPosition(index, card.size),
              getCardPaddingClass(card.size)
            ]"
            :style="getCardStyle(card)"
          >
            <!-- Background Image (if any) -->
            <div v-if="getBackgroundImageUrl(card.backgroundImage)" class="absolute inset-0 opacity-30">
              <img :src="getBackgroundImageUrl(card.backgroundImage)" alt="" class="w-full h-full object-cover" />
            </div>

            <!-- Decorative Network Graphics (for Research card) -->
            <div v-if="card.showDecorations" class="absolute inset-0 opacity-30">
              <!-- Network nodes and connections -->
              <svg class="w-full h-full" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                <!-- Connection lines -->
                <line x1="80" y1="250" x2="200" y2="150" stroke="currentColor" stroke-width="2" :style="{ color: 'rgba(255,255,255,0.3)' }" />
                <line x1="200" y1="150" x2="320" y2="200" stroke="currentColor" stroke-width="2" :style="{ color: 'rgba(255,255,255,0.3)' }" />
                <line x1="200" y1="150" x2="250" y2="280" stroke="currentColor" stroke-width="2" :style="{ color: 'rgba(255,255,255,0.3)' }" />

                <!-- Nodes -->
                <circle cx="80" cy="250" r="8" fill="currentColor" :style="{ color: 'rgba(255,255,255,0.4)' }" />
                <circle cx="200" cy="150" r="12" fill="currentColor" :style="{ color: 'rgba(255,255,255,0.5)' }" />
                <circle cx="320" cy="200" r="8" fill="currentColor" :style="{ color: 'rgba(255,255,255,0.4)' }" />
                <circle cx="250" cy="280" r="8" fill="currentColor" :style="{ color: 'rgba(255,255,255,0.4)' }" />

                <!-- Additional small dots -->
                <circle cx="150" cy="300" r="4" fill="currentColor" :style="{ color: 'rgba(255,255,255,0.3)' }" />
                <circle cx="280" cy="250" r="4" fill="currentColor" :style="{ color: 'rgba(255,255,255,0.3)' }" />
              </svg>
            </div>

            <!-- Content -->
            <div class="relative z-10 w-full" :class="isLargeTallCard(card) ? 'text-left' : 'text-center'">
              <!-- Label/Tag (top) -->
              <div v-if="card.label" class="text-xs md:text-sm font-semibold mb-2 tracking-wide" :style="{ color: getTextColor(card.backgroundColor), opacity: 0.95 }">
                {{ card.label }}
              </div>

              <!-- Tag Box (for research card) -->
              <div v-if="card.tagBox" class="mb-3">
                <div class="inline-block px-2.5 py-1 rounded text-[9px] md:text-[10px] font-medium tracking-wide uppercase" :style="{ backgroundColor: 'rgba(255,255,255,0.2)', color: getTextColor(card.backgroundColor), opacity: 0.9 }">
                  {{ card.tagBox }}
                </div>
              </div>

              <!-- Main Text -->
              <div v-if="card.mainText" :class="[
                'font-bold',
                card.textEffect === 'glitch' ? 'relative mb-0' : 'mb-2',
                getMainTextSizeClass(card)
              ]" :style="{ color: getTextColor(card.backgroundColor) }">
                <!-- Glitch effect for Technical Team -->
                <template v-if="card.textEffect === 'glitch'">
                  <span class="relative inline-block leading-tight">
                    <span class="absolute inset-0 opacity-40" :style="{ transform: 'translate(-3px, -3px)' }">{{ card.mainText }}</span>
                    <span class="absolute inset-0 opacity-30" :style="{ transform: 'translate(3px, 3px)' }">{{ card.mainText }}</span>
                    <span class="relative">{{ card.mainText }}</span>
                  </span>
                </template>
                <template v-else>
                  {{ card.mainText }}
                </template>
              </div>

              <!-- Subtitle -->
              <div v-if="card.subtitle" class="text-[9px] md:text-[10px] font-semibold tracking-[0.15em] uppercase mt-1.5" :style="{ color: getTextColor(card.backgroundColor), opacity: 0.9 }">
                {{ card.subtitle }}
              </div>

              <!-- Description -->
              <p v-if="card.description" class="text-sm md:text-base mt-4" :style="{ color: getTextColor(card.backgroundColor), opacity: 0.8 }">
                {{ card.description }}
              </p>
            </div>
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
    description?: string
    featureTitleColor?: string
    features: Array<{
      icon: 'users' | 'shield' | 'star' | 'heart' | 'globe' | 'lightbulb' | 'target'
      iconColor?: string
      iconBgColor?: string
      title: string
      description: string
    }>
    statCards: Array<{
      label?: string
      tagBox?: string
      mainText?: string
      subtitle?: string
      description?: string
      backgroundColor?: string
      backgroundImage?: string
      showDecorations?: boolean
      textEffect?: 'normal' | 'glitch'
      size?: 'normal' | 'large' | 'tall' | 'large-tall'
    }>
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()

const getCardGridPosition = (index: number, size?: string) => {
  // Layout based on card order in default data:
  // Index 0: Research (large-tall) - row 1-2, col 1
  // Index 1: 10Gbps (normal) - row 1, col 2
  // Index 2: 40+ (normal) - row 3, col 1
  // Index 3: Technical Team (tall) - row 2-3, col 2

  // Determine position based on size if provided, otherwise use index-based positioning
  if (size === 'large-tall') {
    return 'row-start-1 row-span-2 col-start-1'
  } else if (size === 'tall') {
    return 'row-start-2 row-span-2 col-start-2'
  }

  // For normal-sized cards, use index-based positioning
  const positions = [
    'row-start-1 row-span-2 col-start-1', // Research (index 0)
    'row-start-1 col-start-2',             // 10Gbps (index 1)
    'row-start-3 col-start-1',             // 40+ (index 2)
    'row-start-2 row-span-2 col-start-2'   // Technical Team (index 3)
  ]

  return positions[index] || 'row-start-1 col-start-1'
}

const getCardPaddingClass = (size?: string) => {
  // Tall cards get less padding
  if (size === 'large-tall' || size === 'tall') {
    return 'p-4 md:p-6'
  }
  // Normal cards get more padding
  return 'p-6 md:p-8'
}

const isLargeTallCard = (card: any) => {
  return card.size === 'large-tall'
}

const getCardStyle = (card: any) => {
  return {
    backgroundColor: card.backgroundColor || '#1E40AF'
  }
}

const getBackgroundImageUrl = (imagePath?: string) => {
  if (!imagePath) return null
  return transformImageUrl(imagePath)
}

const getMainTextSizeClass = (card: any) => {
  // If it has glitch effect (Technical Team), use smaller size
  if (card.textEffect === 'glitch') {
    return 'text-lg md:text-xl lg:text-2xl'
  }

  // For large-tall cards (Research), don't show main text prominently
  if (card.size === 'large-tall') {
    return 'text-lg md:text-xl'
  }

  // For stat cards with numbers (10Gbps, 40+), use large text
  return 'text-4xl md:text-5xl lg:text-6xl'
}

const calculateBrightness = (hexColor: string): number => {
  const hex = hexColor.replace('#', '')
  const r = parseInt(hex.substr(0, 2), 16)
  const g = parseInt(hex.substr(2, 2), 16)
  const b = parseInt(hex.substr(4, 2), 16)
  return (r * 299 + g * 587 + b * 114) / 1000
}

const getTextColor = (bgColor?: string) => {
  if (bgColor?.startsWith('#')) {
    const brightness = calculateBrightness(bgColor)
    return brightness < 128 ? '#FFFFFF' : '#1F2937'
  }
  return '#FFFFFF'
}
</script>