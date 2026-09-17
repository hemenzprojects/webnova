<template>
  <section class="py-20 bg-gradient-to-br from-primary to-primary-dark text-white relative overflow-hidden">
    <!-- Background Elements -->
    <div class="absolute inset-0">
      <div v-for="(particle, i) in particles" :key="`particle-${i}`" class="absolute w-3 h-3 bg-accent rounded-full opacity-20 animate-float" :style="particle"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
      <h2 v-if="data.heading" class="text-4xl font-bold text-center mb-12">
        {{ data.heading }}
      </h2>

      <div :class="gridClass">
        <div
          v-for="(item, index) in data.items"
          :key="`stat-${index}`"
          class="group p-8 rounded-2xl backdrop-blur backdrop-filter bg-white/10 border border-white/20 hover:bg-white/20 transition-all duration-300"
        >
          <div class="text-center">
            <div class="text-5xl md:text-6xl font-bold mb-2">
              <span v-if="data.animate" ref="counterRefs">{{ item.value }}</span>
              <span v-else>{{ item.value }}</span>
              <span v-if="item.suffix" class="text-accent">{{ item.suffix }}</span>
            </div>
            <div class="text-lg md:text-xl text-gray-200">{{ item.label }}</div>
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
    items: Array<{
      value: string
      suffix?: string
      label: string
    }>
    columns: '2' | '3' | '4'
    animate: boolean
  }
  blockId: string
}>()

const counterRefs = ref<HTMLElement[]>([])

const gridClass = computed(() => {
  const columnsMap: Record<string, string> = {
    '2': 'grid grid-cols-1 md:grid-cols-2 gap-6',
    '3': 'grid grid-cols-1 md:grid-cols-3 gap-6',
    '4': 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6',
  }
  return columnsMap[props.data.columns] || columnsMap['4']
})

// Generate particles only on client-side to avoid hydration mismatch
const particles = ref<Array<{ left: string; top: string; animationDelay: string }>>([])

onMounted(() => {
  // Generate particles
  particles.value = Array.from({ length: 10 }, () => ({
    left: `${Math.random() * 100}%`,
    top: `${Math.random() * 100}%`,
    animationDelay: `${Math.random() * 6}s`,
  }))

  if (props.data.animate) {
    // Counter animation logic would go here
    // For simplicity, we're just displaying the values
  }
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
