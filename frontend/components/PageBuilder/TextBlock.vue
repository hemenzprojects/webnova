<template>
  <section class="py-12">
    <div :class="containerClass">
      <h2 v-if="data.heading" class="text-3xl font-bold mb-6" :class="alignmentClass">
        {{ data.heading }}
      </h2>
      <div
        class="prose prose-lg max-w-none"
        :class="alignmentClass"
        v-html="data.content"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    heading?: string
    content: string
    alignment: 'left' | 'center' | 'right'
    containerWidth: 'container' | 'narrow' | 'wide' | 'full'
  }
  blockId: string
}>()

const containerClass = computed(() => {
  const widthMap: Record<string, string> = {
    container: 'container mx-auto px-4',
    narrow: 'max-w-3xl mx-auto px-4',
    wide: 'max-w-7xl mx-auto px-4',
    full: 'w-full px-4',
  }
  return widthMap[props.data.containerWidth] || widthMap.container
})

const alignmentClass = computed(() => {
  const alignMap: Record<string, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
  }
  return alignMap[props.data.alignment] || alignMap.left
})
</script>
