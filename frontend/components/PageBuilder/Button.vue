<template>
  <section class="py-8">
    <div class="container mx-auto px-4">
      <div :class="alignmentClass">
        <a
          :href="data.url"
          :target="data.openInNewTab ? '_blank' : '_self'"
          :rel="data.openInNewTab ? 'noopener noreferrer' : undefined"
          :class="buttonClass"
        >
          {{ data.text }}
        </a>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    text: string
    url: string
    style: 'primary' | 'secondary' | 'accent' | 'outline'
    size: 'sm' | 'md' | 'lg'
    alignment: 'left' | 'center' | 'right'
    openInNewTab: boolean
  }
  blockId: string
}>()

const alignmentClass = computed(() => {
  const alignMap: Record<string, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
  }
  return alignMap[props.data.alignment] || alignMap.center
})

const buttonClass = computed(() => {
  const baseClasses = 'inline-block font-semibold transition-all duration-300 rounded-lg'

  const sizeMap: Record<string, string> = {
    sm: 'px-4 py-2 text-sm',
    md: 'px-6 py-3 text-base',
    lg: 'px-8 py-4 text-lg',
  }

  const styleMap: Record<string, string> = {
    primary: 'bg-primary text-white hover:bg-primary-light',
    secondary: 'bg-gray-600 text-white hover:bg-gray-700',
    accent: 'bg-accent text-white hover:bg-accent-light',
    outline: 'border-2 border-primary text-primary hover:bg-primary hover:text-white',
  }

  const sizeClass = sizeMap[props.data.size] || sizeMap.md
  const styleClass = styleMap[props.data.style] || styleMap.primary

  return `${baseClasses} ${sizeClass} ${styleClass}`
})
</script>
