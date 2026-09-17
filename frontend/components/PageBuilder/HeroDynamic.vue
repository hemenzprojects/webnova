
<template>
  <section :class="heightClass" :style="heightStyle" class="relative overflow-hidden">
    <!-- Background Image -->
    <div v-if="backgroundImageUrl" class="absolute inset-0">
      <img :src="backgroundImageUrl" alt="" class="w-full h-full object-cover" />
      <div class="absolute inset-0" :style="getOverlayStyle()"></div>
    </div>

    <!-- Background decorative elements -->
    <div v-if="data.showDecorations" class="absolute inset-0 opacity-5">
      <div class="absolute top-0 right-0 w-96 h-96 bg-primary rounded-full blur-3xl"></div>
      <div class="absolute bottom-0 left-0 w-96 h-96 bg-accent rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 flex items-center px-0">
      <div :class="[widthClass, paddingClass]" :style="widthStyle" class="grid md:grid-cols-2 gap-8 lg:gap-12 items-center">
        <!-- Left Content -->
        <div :class="['space-y-6', contentPaddingClass]">
          <!-- Top Badge -->
          <div v-if="data.topBadge" class="inline-block">
            <span
              class="px-4 py-2 rounded-full text-xs font-semibold tracking-wider uppercase"
              :style="getTopBadgeStyle()"
            >
              {{ data.topBadge }}
            </span>
          </div>

          <!-- Heading -->
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

          <!-- Subheading -->
          <p v-if="data.subheading" class="text-lg md:text-xl text-gray-300 leading-relaxed max-w-xl">
            {{ data.subheading }}
          </p>

          <!-- CTA Buttons -->
          <div class="flex flex-wrap gap-4 pt-2">
            <NuxtLink
              v-if="data.primaryCtaText && data.primaryCtaLink"
              :to="data.primaryCtaLink"
              :class="[
                'px-8 py-3 font-semibold rounded-full transition-all duration-300 shadow-lg hover:shadow-xl',
                data.primaryCtaStyle === 'outline'
                  ? 'border-2 bg-transparent hover:bg-white/10'
                  : 'hover:scale-105'
              ]"
              :style="getPrimaryCtaStyle()"
            >
              {{ data.primaryCtaText }}
            </NuxtLink>

            <NuxtLink
              v-if="data.secondaryCtaText && data.secondaryCtaLink"
              :to="data.secondaryCtaLink"
              :class="[
                'px-8 py-3 font-semibold rounded-full transition-all duration-300',
                data.secondaryCtaStyle === 'outline'
                  ? 'border-2 bg-transparent hover:bg-white/10'
                  : 'bg-white/10 hover:bg-white/20'
              ]"
              :style="getSecondaryCtaStyle()"
            >
              {{ data.secondaryCtaText }}
            </NuxtLink>
          </div>

          <!-- Bottom Badges -->
          <div v-if="data.bottomBadges && data.bottomBadges.length > 0" class="flex flex-wrap gap-3 pt-4">
            <span
              v-for="(badge, index) in data.bottomBadges"
              :key="index"
              class="px-4 py-2 backdrop-blur-sm text-sm rounded-full border hover:opacity-90 transition-all"
              :style="getBottomBadgeStyle()"
            >
              {{ badge }}
            </span>
          </div>
        </div>

        <!-- Right Content -->
        <div :class="['relative flex items-center justify-center', contentPaddingClass]">
          <!-- Circular Image Option -->
          <div
            v-if="data.rightContentType === 'image' && data.circleImage && data.showCircleImage !== false"
            class="relative group"
            :style="getCircleImageContainerStyle()"
          >
            <div
              class="rounded-full overflow-hidden shadow-2xl transition-all duration-300 group-hover:shadow-3xl"
              :style="getCircleImageStyle()"
            >
              <img
                :src="circleImageUrl"
                alt="Hero image"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
              />
              <!-- Hover Overlay -->
              <div
                v-if="data.showHoverOverlay !== false"
                class="absolute inset-0 transition-opacity duration-300 opacity-0 group-hover:opacity-100 flex items-center justify-center"
                :style="getHoverOverlayStyle()"
              >
                <div v-if="data.hoverText" class="text-center px-6">
                  <p class="text-white text-xl md:text-2xl font-bold">{{ data.hoverText }}</p>
                </div>
              </div>
            </div>
            <!-- Decorative ring -->
            <div
              v-if="data.showDecorations"
              class="absolute inset-0 rounded-full border-4 border-purple-400/30 -z-10"
              :style="{
                transform: 'scale(1.1)',
                animation: 'pulse 3s ease-in-out infinite'
              }"
            ></div>
          </div>

          <!-- Info Cards Option -->
          <div
            v-else-if="data.rightContentType === 'cards'"
            class="space-y-4"
          >
            <div
              v-for="(card, index) in data.infoCards"
              :key="index"
              class="backdrop-blur-md border rounded-2xl p-6 hover:opacity-90 transition-all duration-300 group"
              :style="getInfoCardStyle(card)"
            >
              <!-- Card Header -->
              <div
                class="text-xs font-semibold tracking-widest uppercase mb-2"
                :style="{ color: card.headerTextColor || '#9ca3af' }"
              >
                {{ card.header }}
              </div>

              <!-- Card Title -->
              <div
                class="text-2xl md:text-3xl font-bold mb-2"
                :style="{ color: card.titleTextColor || '#ffffff' }"
              >
                {{ card.title }}
              </div>

              <!-- Card Description -->
              <div
                v-if="card.description"
                class="text-sm leading-relaxed"
                :style="{ color: card.descriptionTextColor || '#d1d5db' }"
              >
                {{ card.description }}
              </div>

              <!-- Progress Bar (if card has progress) -->
              <div v-if="card.progress !== undefined" class="mt-4">
                <div class="flex items-center justify-between text-xs text-gray-400 mb-2">
                  <span>{{ card.progressLabel || 'Progress' }}</span>
                  <span>{{ card.progressValue || card.progress }}%</span>
                </div>
                <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :style="{
                      width: `${card.progress}%`,
                      backgroundColor: card.progressColor || '#14b8a6'
                    }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Text Content Option (Feature Box) -->
          <div
            v-else-if="data.rightContentType === 'text' || !data.rightContentType"
            class="rounded-2xl p-8 md:p-12 shadow-2xl min-h-[300px] flex items-center justify-center group transition-all duration-300 hover:shadow-3xl"
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
    // Top Badge
    topBadge?: string
    topBadgeColor?: string
    topBadgeBgColor?: string

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
    primaryCtaStyle?: 'filled' | 'outline'
    primaryCtaColor?: string
    primaryCtaBgColor?: string
    secondaryCtaText?: string
    secondaryCtaLink?: string
    secondaryCtaStyle?: 'filled' | 'outline'
    secondaryCtaColor?: string
    secondaryCtaBgColor?: string

    // Bottom Badges
    bottomBadges?: string[]

    // Right Content Type
    rightContentType?: 'text' | 'image' | 'cards'

    // Info Cards
    infoCards?: Array<{
      header: string
      title: string
      description?: string
      progress?: number
      progressLabel?: string
      progressValue?: string
      progressColor?: string
    }>

    // Feature Box (Text Content)
    featureBoxTopText?: string
    featureBoxMainText?: string
    featureBoxBottomText?: string
    featureBoxColor?: 'teal' | 'primary' | 'purple' | 'accent' | 'secondary'

    // Circular Image Options
    circleImage?: string
    showCircleImage?: boolean
    circleImageSize?: 'small' | 'medium' | 'large' | 'xlarge'
    showHoverOverlay?: boolean
    hoverOverlayColor?: string
    hoverText?: string

    // Background
    backgroundImage?: string
    overlayColor?: string
    overlayOpacity?: number

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
const circleImageUrl = computed(() => transformImageUrl(props.data.circleImage || null))

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

// Get circular image container style
const getCircleImageContainerStyle = () => {
  const sizeMap: Record<string, string> = {
    small: '250px',
    medium: '350px',
    large: '450px',
    xlarge: '550px'
  }
  const size = sizeMap[props.data.circleImageSize || 'medium']

  return {
    width: size,
    height: size
  }
}

// Get circular image style
const getCircleImageStyle = () => {
  return {
    width: '100%',
    height: '100%',
    position: 'relative' as const
  }
}

// Get hover overlay style
const getHoverOverlayStyle = () => {
  const color = props.data.hoverOverlayColor || 'rgba(147, 51, 234, 0.85)' // Default purple with opacity

  return {
    backgroundColor: color
  }
}

// Get top badge style
const getTopBadgeStyle = () => {
  return {
    color: props.data.topBadgeColor || '#14b8a6',
    backgroundColor: props.data.topBadgeBgColor || 'rgba(20, 184, 166, 0.1)',
    border: `1px solid ${props.data.topBadgeColor || '#14b8a6'}40`
  }
}

// Get primary CTA style
const getPrimaryCtaStyle = () => {
  if (props.data.primaryCtaStyle === 'outline') {
    return {
      color: props.data.primaryCtaColor || '#14b8a6',
      borderColor: props.data.primaryCtaColor || '#14b8a6'
    }
  }
  return {
    color: props.data.primaryCtaColor || '#ffffff',
    backgroundColor: props.data.primaryCtaBgColor || '#14b8a6'
  }
}

// Get secondary CTA style
const getSecondaryCtaStyle = () => {
  if (props.data.secondaryCtaStyle === 'outline') {
    return {
      color: props.data.secondaryCtaColor || '#ffffff',
      borderColor: props.data.secondaryCtaColor || '#ffffff'
    }
  }
  return {
    color: props.data.secondaryCtaColor || '#ffffff',
    backgroundColor: props.data.secondaryCtaBgColor || 'rgba(255, 255, 255, 0.1)'
  }
}

// Get bottom badge style
const getBottomBadgeStyle = () => {
  const textColor = props.data.bottomBadgeTextColor || '#ffffff'
  const bgColor = props.data.bottomBadgeBgColor || 'rgba(255, 255, 255, 0.1)'

  return {
    color: textColor,
    backgroundColor: bgColor,
    borderColor: `${textColor}33` // Add 33 for ~20% opacity
  }
}

// Get info card style
const getInfoCardStyle = (card: any) => {
  const bgColor = card.cardBackgroundColor || 'rgba(255, 255, 255, 0.05)'
  const borderColor = card.cardBorderColor || 'rgba(255, 255, 255, 0.1)'

  return {
    backgroundColor: bgColor,
    borderColor: borderColor
  }
}

// Get overlay style
const getOverlayStyle = () => {
  const overlayColor = props.data.overlayColor || '#000000'
  const overlayOpacity = props.data.overlayOpacity !== undefined ? props.data.overlayOpacity : 0.5

  // Convert hex color to rgba with opacity
  const r = parseInt(overlayColor.slice(1, 3), 16)
  const g = parseInt(overlayColor.slice(3, 5), 16)
  const b = parseInt(overlayColor.slice(5, 7), 16)

  return {
    backgroundColor: `rgba(${r}, ${g}, ${b}, ${overlayOpacity})`
  }
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