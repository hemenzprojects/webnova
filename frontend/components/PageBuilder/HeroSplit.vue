
<template>
  <section :class="heightClass" :style="heightStyle" class="relative overflow-hidden">
    <!-- Background Image -->
    <div v-if="backgroundImageUrl" class="absolute inset-0">
      <img :src="backgroundImageUrl" alt="" class="w-full h-full object-cover" />
      <div class="absolute inset-0 bg-white/80"></div>
    </div>

    <!-- Background decorative elements -->
    <div v-if="data.showDecorations" class="absolute inset-0 opacity-5">
      <div class="absolute top-0 right-0 w-96 h-96 bg-primary rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 left-0 w-96 h-96 bg-accent rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 flex items-center px-0">
      <div :class="[widthClass, paddingClass]" :style="widthStyle" class="grid md:grid-cols-2 gap-8 lg:gap-12 items-center">
        <!-- Left Content -->
        <div :class="['space-y-4', contentPaddingClass]">
          <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
            <template v-if="data.headingLine1">
              <span :style="{ color: getHeadingColor(data.headingLine1Color, '#0A1E3E') }">{{ data.headingLine1 }}</span>
              <br />
            </template>
            <template v-if="data.headingLine2">
              <span :style="{ color: getHeadingColor(data.headingLine2Color, '#9333EA') }">{{ data.headingLine2 }}</span>
              <br />
            </template>
            <template v-if="data.headingLine3">
              <span :style="{ color: getHeadingColor(data.headingLine3Color, '#0A1E3E') }">{{ data.headingLine3 }}</span>
            </template>
          </h1>

          <p v-if="data.subheading" class="text-lg md:text-xl text-gray-600 leading-relaxed max-w-xl">
            {{ data.subheading }}
          </p>

          <div class="flex flex-wrap gap-4 pt-2">
            <NuxtLink
              v-if="data.primaryCtaText && data.primaryCtaLink"
              :to="data.primaryCtaLink"
              class="px-8 py-3 bg-primary text-white font-semibold rounded-md hover:bg-primary-dark transition-colors duration-300 shadow-lg hover:shadow-xl"
            >
              {{ data.primaryCtaText }}
            </NuxtLink>

            <NuxtLink
              v-if="data.secondaryCtaText && data.secondaryCtaLink"
              :to="data.secondaryCtaLink"
              class="px-8 py-3 bg-gray-200 text-gray-800 font-semibold rounded-md hover:bg-gray-300 transition-colors duration-300"
            >
              {{ data.secondaryCtaText }}
            </NuxtLink>
          </div>
        </div>

        <!-- Right Content -->
        <div v-if="data.showFeatureBox !== false" :class="['relative', contentPaddingClass]">
          <div
            :class="[
              'p-8 md:p-12 shadow-2xl min-h-[300px] flex items-center justify-center',
              data.featureBoxShape === 'circle' ? 'rounded-full aspect-square' : 'rounded-2xl'
            ]"
            :style="getFeatureBoxStyle()"
          >
            <div class="text-center space-y-4">
              <div class="space-y-3">
                <!-- Top Divider with Text -->
                <div v-if="data.featureBoxTopText" class="flex items-center justify-center gap-4">
                  <div class="h-px bg-white/50 flex-1"></div>
                  <span class="text-white/80 font-light tracking-widest text-sm">
                    {{ data.featureBoxTopText }}
                  </span>
                  <div class="h-px bg-white/50 flex-1"></div>
                </div>

                <!-- Main Feature Text -->
                <h2 v-if="data.featureBoxMainText" class="text-4xl md:text-5xl font-bold text-white tracking-wide">
                  {{ data.featureBoxMainText }}
                </h2>

                <!-- Bottom Feature Text -->
                <h3 v-if="data.featureBoxBottomText" class="text-3xl md:text-4xl font-bold text-white tracking-wider">
                  {{ data.featureBoxBottomText }}
                </h3>
              </div>
            </div>
          </div>

          <!-- Decorative corner accent -->
          <div v-if="data.showDecorations" class="absolute -bottom-4 -right-4 w-24 h-24 bg-purple-600 rounded-lg opacity-20 blur-xl"></div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    // Heading Configuration
    headingLine1?: string
    headingLine1Color?: 'primary' | 'purple' | 'accent' | 'secondary' | 'gray'
    headingLine2?: string
    headingLine2Color?: 'primary' | 'purple' | 'accent' | 'secondary' | 'gray'
    headingLine3?: string
    headingLine3Color?: 'primary' | 'purple' | 'accent' | 'secondary' | 'gray'
    subheading?: string

    // Call to Action
    primaryCtaText?: string
    primaryCtaLink?: string
    secondaryCtaText?: string
    secondaryCtaLink?: string

    // Feature Box (Text Content)
    showFeatureBox?: boolean
    featureBoxTopText?: string
    featureBoxMainText?: string
    featureBoxBottomText?: string
    featureBoxColor?: 'teal' | 'primary' | 'purple' | 'accent' | 'secondary'
    featureBoxShape?: 'rounded' | 'circle'

    // Background
    backgroundImage?: string

    // Layout Options
    height: 'medium' | 'large' | 'full' | 'custom'
    customHeight?: number
    width?: 'small' | 'medium' | 'large' | 'full' | 'custom'
    customWidth?: number
    showDecorations: boolean
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()

const backgroundImageUrl = computed(() => transformImageUrl(props.data.backgroundImage || null))

const heightClass = computed(() => {
  if (props.data.height === 'custom') {
    return 'py-12 md:py-16'
  }
  const heightMap: Record<string, string> = {
    medium: 'min-h-[500px] max-h-[600px] py-12 md:py-16',
    large: 'min-h-[600px] max-h-[700px] py-16 md:py-20',
    full: 'min-h-[700px] max-h-screen py-16 md:py-20',
  }
  return heightMap[props.data.height] || heightMap.medium
})

const heightStyle = computed(() => {
  if (props.data.height === 'custom' && props.data.customHeight) {
    return {
      minHeight: `${props.data.customHeight}px`,
      maxHeight: `${props.data.customHeight}px`
    }
  }
  return {}
})

const widthClass = computed(() => {
  if (props.data.width === 'custom') {
    return 'mx-auto'
  }
  const widthMap: Record<string, string> = {
    small: 'max-w-4xl mx-auto',
    medium: 'max-w-6xl mx-auto',
    large: 'max-w-7xl mx-auto',
    full: 'w-full',
  }
  return widthMap[props.data.width || 'full'] || widthMap.full
})

const widthStyle = computed(() => {
  if (props.data.width === 'custom' && props.data.customWidth) {
    return {
      maxWidth: `${props.data.customWidth}px`
    }
  }
  return {}
})

const paddingClass = computed(() => {
  // No padding for full width - truly edge to edge
  if (props.data.width === 'full' || !props.data.width) {
    return ''
  }
  // Add padding for constrained widths
  return 'px-6 lg:px-12'
})

const contentPaddingClass = computed(() => {
  // When full width, add padding to content columns only
  if (props.data.width === 'full' || !props.data.width) {
    return 'px-6 lg:px-12'
  }
  // For constrained widths, no additional padding needed on content
  return ''
})

const getHeadingColor = (color?: string, defaultHex: string = '#0A1E3E') => {
  // Support both hex colors and predefined color names
  if (color?.startsWith('#')) {
    return color
  }

  const colorMap: Record<string, string> = {
    primary: '#0A1E3E',
    purple: '#9333EA',
    accent: '#00D9FF',
    secondary: '#059669',
    gray: '#1F2937',
  }
  return colorMap[color || ''] || defaultHex
}

const getFeatureBoxStyle = () => {
  const color = props.data.featureBoxColor

  // If it's a hex color, create gradient from it
  if (color?.startsWith('#')) {
    return {
      background: `linear-gradient(to bottom right, ${color}, ${adjustColorBrightness(color, -20)})`
    }
  }

  // Fallback to predefined colors
  const colorMap: Record<string, string> = {
    teal: 'linear-gradient(to bottom right, #0D9488, #115E59)',
    primary: 'linear-gradient(to bottom right, #0A1E3E, #050F1F)',
    purple: 'linear-gradient(to bottom right, #9333EA, #6B21A8)',
    accent: 'linear-gradient(to bottom right, #00D9FF, #00A8CC)',
    secondary: 'linear-gradient(to bottom right, #059669, #047857)',
  }

  return {
    background: colorMap[color || 'teal'] || colorMap.teal
  }
}

// Helper to darken a color
const adjustColorBrightness = (hex: string, percent: number) => {
  const num = parseInt(hex.replace('#', ''), 16)
  const amt = Math.round(2.55 * percent)
  const R = (num >> 16) + amt
  const G = (num >> 8 & 0x00FF) + amt
  const B = (num & 0x0000FF) + amt

  return '#' + (
    0x1000000 +
    (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
    (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 +
    (B < 255 ? (B < 1 ? 0 : B) : 255)
  ).toString(16).slice(1).toUpperCase()
}
</script>

<style scoped>
@keyframes pulse {
  0%, 100% {
    transform: scale(1.1);
    opacity: 0.3;
  }
  50% {
    transform: scale(1.15);
    opacity: 0.2;
  }
}
</style>