<template>
  <section class="py-8">
    <div class="container mx-auto px-4">
      <div :class="containerClass">
        <hr :class="dividerClass" />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    style: 'solid' | 'dashed' | 'dotted' | 'gradient'
    width: 'narrow' | 'medium' | 'wide' | 'full'
  }
  blockId: string
}>()

const containerClass = computed(() => {
  const widthMap: Record<string, string> = {
    narrow: 'max-w-xs mx-auto',
    medium: 'max-w-2xl mx-auto',
    wide: 'max-w-4xl mx-auto',
    full: 'w-full',
  }
  return widthMap[props.data.width] || widthMap.medium
})

const dividerClass = computed(() => {
  const baseClass = 'border-0'

  const styleMap: Record<string, string> = {
    solid: 'border-t-2 border-gray-300',
    dashed: 'border-t-2 border-dashed border-gray-300',
    dotted: 'border-t-2 border-dotted border-gray-300',
    gradient: 'h-1 bg-gradient-to-r from-primary via-accent to-primary',
  }

  const styleClass = styleMap[props.data.style] || styleMap.solid

  return `${baseClass} ${styleClass}`
})
</script>
