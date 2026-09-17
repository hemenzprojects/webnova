<template>
  <section class="py-8">
    <div class="container mx-auto px-4">
      <component
        :is="data.level"
        class="font-bold"
        :class="[sizeClass, alignmentClass]"
        :style="colorStyle"
      >
        {{ data.text }}
      </component>
      <p v-if="data.subtitle" class="mt-2 text-gray-600" :class="[subtitleSizeClass, alignmentClass]">
        {{ data.subtitle }}
      </p>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    text: string
    level: 'h1' | 'h2' | 'h3' | 'h4'
    subtitle?: string
    alignment: 'left' | 'center' | 'right'
    color?: string
  }
  blockId: string
}>()

const sizeClass = computed(() => {
  const sizeMap: Record<string, string> = {
    h1: 'text-5xl md:text-6xl',
    h2: 'text-4xl md:text-5xl',
    h3: 'text-3xl md:text-4xl',
    h4: 'text-2xl md:text-3xl',
  }
  return sizeMap[props.data.level] || sizeMap.h2
})

const subtitleSizeClass = computed(() => {
  const sizeMap: Record<string, string> = {
    h1: 'text-xl md:text-2xl',
    h2: 'text-lg md:text-xl',
    h3: 'text-base md:text-lg',
    h4: 'text-base',
  }
  return sizeMap[props.data.level] || sizeMap.h2
})

const alignmentClass = computed(() => {
  const alignMap: Record<string, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
  }
  return alignMap[props.data.alignment] || alignMap.center
})

const colorStyle = computed(() => {
  return props.data.color ? { color: props.data.color } : {}
})
</script>
