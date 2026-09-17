<template>
  <section class="py-12">
    <div class="container mx-auto px-4">
      <div :class="gridClass">
        <div :class="reverseClass('left')" class="prose prose-lg max-w-none" v-html="data.leftContent"></div>
        <div :class="reverseClass('right')" class="prose prose-lg max-w-none" v-html="data.rightContent"></div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    leftContent: string
    rightContent: string
    ratio: '50-50' | '60-40' | '40-60' | '70-30' | '30-70'
    reverseOnMobile: boolean
  }
  blockId: string
}>()

const gridClass = computed(() => {
  const ratioMap: Record<string, string> = {
    '50-50': 'grid md:grid-cols-2 gap-8',
    '60-40': 'grid md:grid-cols-[3fr_2fr] gap-8',
    '40-60': 'grid md:grid-cols-[2fr_3fr] gap-8',
    '70-30': 'grid md:grid-cols-[7fr_3fr] gap-8',
    '30-70': 'grid md:grid-cols-[3fr_7fr] gap-8',
  }
  return ratioMap[props.data.ratio] || ratioMap['50-50']
})

const reverseClass = (side: 'left' | 'right') => {
  if (!props.data.reverseOnMobile) return ''

  if (side === 'left') {
    return 'order-2 md:order-1'
  } else {
    return 'order-1 md:order-2'
  }
}
</script>
