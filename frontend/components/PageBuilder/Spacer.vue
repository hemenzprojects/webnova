<template>
  <div :class="spacerClass"></div>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    height: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  }
  blockId: string
}>()

const spacerClass = computed(() => {
  const heightMap: Record<string, string> = {
    xs: 'h-4',
    sm: 'h-8',
    md: 'h-12',
    lg: 'h-20',
    xl: 'h-32',
  }
  return heightMap[props.data.height] || heightMap.md
})
</script>
