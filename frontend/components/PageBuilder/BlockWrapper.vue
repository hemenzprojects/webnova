<template>
  <div :id="styles?.anchorId || undefined" :class="css.classes" :style="css.style" class="relative">
    <!-- In the editor, hidden-on-device widgets stay visible with a note -->
    <span
      v-if="editor && hiddenOn.length"
      class="absolute top-1 right-1 z-10 px-2 py-0.5 text-[11px] font-medium rounded bg-gray-900/70 text-white pointer-events-none"
    >
      Hidden on {{ hiddenOn.join(', ') }}
    </span>
    <slot />
  </div>
</template>

<script setup lang="ts">
// Applies a widget's shared Style / Advanced settings (data._style). See utils/blockStyle.ts.
import type { BlockStyle } from '~/utils/blockStyle'

const props = defineProps<{
  styles?: BlockStyle
  editor?: boolean
}>()

const { getImageUrl } = useImageUrl()

const css = computed(() => {
  const s = props.styles
  // Stored image paths are relative; build the full URL for this site
  const resolved = s?.background?.image ? { ...s, background: { ...s.background, image: getImageUrl(s.background.image) } } : s
  return blockStyleToCss(resolved, props.editor)
})

const hiddenOn = computed(() => {
  const hide = props.styles?.hide || {}
  return (['mobile', 'tablet', 'desktop'] as const).filter((device) => hide[device])
})

useHead(() => ({
  link: css.value.fonts.map((href) => ({ rel: 'stylesheet', href, key: href })),
}))
</script>
