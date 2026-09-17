<template>
  <section class="py-8">
    <div class="container mx-auto px-4">
      <div :class="alignmentClass">
        <div :class="imageContainerClass">
          <img
            :src="imageUrl"
            :alt="data.alt"
            :class="imageClass"
            loading="lazy"
          />
          <p v-if="data.caption" class="mt-3 text-sm text-gray-600 text-center">
            {{ data.caption }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    image: string
    alt: string
    caption?: string
    size: 'small' | 'medium' | 'large' | 'full'
    alignment: 'left' | 'center' | 'right'
    rounded: boolean
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()

const imageUrl = computed(() => transformImageUrl(props.data.image) || '')

const alignmentClass = computed(() => {
  const alignMap: Record<string, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
  }
  return alignMap[props.data.alignment] || alignMap.center
})

const imageContainerClass = computed(() => {
  const sizeMap: Record<string, string> = {
    small: 'max-w-md inline-block',
    medium: 'max-w-2xl inline-block',
    large: 'max-w-5xl inline-block',
    full: 'w-full',
  }
  return sizeMap[props.data.size] || sizeMap.medium
})

const imageClass = computed(() => {
  const classes = ['w-full h-auto']
  if (props.data.rounded) {
    classes.push('rounded-lg')
  }
  return classes.join(' ')
})
</script>
