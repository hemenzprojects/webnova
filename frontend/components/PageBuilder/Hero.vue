<template>
  <section :class="heightClass" class="relative overflow-hidden">
    <!-- Background Image -->
    <div v-if="backgroundImageUrl" class="absolute inset-0">
      <img :src="backgroundImageUrl" alt="" class="w-full h-full object-cover" />
      <div v-if="data.overlay !== 'none'" :class="overlayClass"></div>
    </div>

    <!-- Animated Particles -->
    <div v-if="data.showParticles" class="absolute inset-0">
      <div v-for="(particle, i) in particles" :key="`particle-${i}`" class="absolute w-2 h-2 bg-accent rounded-full opacity-30 animate-float" :style="particle"></div>
    </div>

    <!-- Content -->
    <div class="relative z-10 container mx-auto px-4 h-full flex items-center">
      <div class="grid md:grid-cols-2 gap-12 items-center w-full">
        <!-- Left side: Text content -->
        <div class="text-white">
          <h1 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
            {{ data.heading }}
          </h1>
          <p v-if="data.subheading" class="text-xl md:text-2xl mb-8 text-gray-200">
            {{ data.subheading }}
          </p>
          <NuxtLink
            v-if="data.ctaText && data.ctaLink"
            :to="data.ctaLink"
            class="inline-block px-8 py-4 rounded-full text-lg font-semibold transition-all duration-300 hover:scale-105"
            :style="{ backgroundColor: 'var(--color-accent)', color: 'white' }"
          >
            {{ data.ctaText }}
          </NuxtLink>
        </div>

        <!-- Right side: Image -->
        <div v-if="foregroundImageUrl" class="relative">
          <div class="relative rounded-full overflow-hidden" style="aspect-ratio: 1/1;">
            <div class="absolute inset-0 bg-gradient-to-br from-primary to-accent opacity-20"></div>
            <img :src="foregroundImageUrl" alt="" class="w-full h-full object-cover" />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    heading: string
    subheading?: string
    backgroundImage?: string
    foregroundImage?: string
    overlay: 'none' | 'dark' | 'gradient' | 'primary'
    ctaText?: string
    ctaLink?: string
    height: 'medium' | 'large' | 'full'
    showParticles: boolean
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()

const backgroundImageUrl = computed(() => transformImageUrl(props.data.backgroundImage || null))
const foregroundImageUrl = computed(() => transformImageUrl(props.data.foregroundImage || null))

const heightClass = computed(() => {
  const heightMap: Record<string, string> = {
    medium: 'min-h-[600px]',
    large: 'min-h-[700px]',
    full: 'min-h-screen',
  }
  return heightMap[props.data.height] || heightMap.large
})

const overlayClass = computed(() => {
  const overlayMap: Record<string, string> = {
    dark: 'absolute inset-0 bg-black bg-opacity-50',
    gradient: 'absolute inset-0 bg-gradient-to-r from-black/70 to-black/30',
    primary: 'absolute inset-0 bg-primary bg-opacity-70',
    none: '',
  }
  return overlayMap[props.data.overlay] || overlayMap.gradient
})

// Generate particles only on client-side to avoid hydration mismatch
const particles = ref<Array<{ left: string; top: string; animationDelay: string }>>([])

onMounted(() => {
  particles.value = Array.from({ length: 20 }, () => ({
    left: `${Math.random() * 100}%`,
    top: `${Math.random() * 100}%`,
    animationDelay: `${Math.random() * 6}s`,
  }))
})
</script>

<style scoped>
@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-20px);
  }
}

.animate-float {
  animation: float 6s ease-in-out infinite;
}
</style>
