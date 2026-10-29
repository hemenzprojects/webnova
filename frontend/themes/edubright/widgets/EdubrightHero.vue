<template>
  <section class="relative" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <!-- Coloured band (theme lavender unless the editor sets a background); the stats bar overlaps its lower edge -->
    <div :style="bandStyle">
      <div class="container mx-auto px-4 pt-14 lg:pt-20" :class="stats.length ? 'pb-28 lg:pb-32' : 'pb-16 lg:pb-20'">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div>
            <h1
              class="text-4xl md:text-6xl leading-[1.15] mb-6"
              :style="{ fontFamily: 'var(--font-heading)', fontWeight: 'var(--font-heading-weight)', color: hasImage ? '#ffffff' : 'var(--color-text)' }"
            >
              {{ data.heading }}
            </h1>
            <p v-if="data.subheading" class="text-lg mb-8 max-w-xl leading-relaxed" :style="{ color: hasImage ? 'rgba(255,255,255,.88)' : 'var(--color-text-muted)' }">
              {{ data.subheading }}
            </p>
            <SmartLink
              v-if="data.ctaText && data.ctaLink"
              :to="data.ctaLink"
              class="inline-flex items-center px-6 py-3 text-white font-semibold bg-primary hover:opacity-90 transition"
              :style="{ borderRadius: 'var(--radius-button)' }"
            >
              {{ data.ctaText }}
            </SmartLink>
          </div>

          <div v-if="foregroundImageUrl" class="relative max-w-md lg:ml-auto w-full">
            <img
              :src="foregroundImageUrl"
              alt=""
              class="w-full aspect-[4/5] object-cover"
              :style="{ borderRadius: 'var(--radius-card)' }"
            />
            <div
              v-if="data.stat?.value"
              class="absolute top-8 -right-2 md:-right-10 px-7 py-5 text-center shadow-lg"
              :style="{ borderRadius: 'var(--radius-card)', backgroundColor: 'var(--color-surface-muted, #F4F0FE)' }"
            >
              <div class="text-4xl" :style="{ fontFamily: 'var(--font-heading)', fontWeight: 700, color: 'var(--color-text)' }">{{ data.stat.value }}</div>
              <div class="text-xs font-semibold tracking-widest uppercase mt-1 text-primary">{{ data.stat.label }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="stats.length" class="container mx-auto px-4 relative -mt-20">
      <div
        class="grid gap-6 py-9 px-6 text-center"
        :class="stats.length >= 3 ? 'grid-cols-1 sm:grid-cols-3' : 'grid-cols-1 sm:grid-cols-2'"
        :style="{ borderRadius: 'var(--radius-card)', backgroundColor: 'var(--color-surface-muted, #F4F0FE)' }"
      >
        <div v-for="(stat, i) in stats" :key="i">
          <div class="text-4xl md:text-5xl" :style="{ fontFamily: 'var(--font-heading)', fontWeight: 700, color: 'var(--color-text)' }">{{ stat.value }}</div>
          <div class="text-xs md:text-sm font-semibold tracking-widest uppercase mt-2 text-primary">{{ stat.label }}</div>
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
    foregroundImage?: string
    ctaText?: string
    ctaLink?: string
    stat?: { value: string; label: string }
    stats?: { value: string; label: string }[]
    backgroundImage?: string
    overlay?: string
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()
const foregroundImageUrl = computed(() => transformImageUrl(props.data.foregroundImage || null))

// The Hero's own Background Image (Content tab) and Overlay setting
const OVERLAYS: Record<string, string> = {
  dark: 'rgba(0,0,0,.55)',
  gradient: 'rgba(0,0,0,.35)',
  primary: 'color-mix(in srgb, var(--color-primary) 70%, transparent)',
}
const backgroundUrl = computed(() => transformImageUrl(props.data.backgroundImage || null))
// Text over a photo is white so it stays readable
const hasImage = computed(() => !!backgroundUrl.value)
const bandStyle = computed(() => {
  const image = backgroundUrl.value
  const style: Record<string, string> = { backgroundColor: 'var(--block-bg, var(--color-surface-strong, #E8DEFB))' }
  if (image) {
    const shade = OVERLAYS[props.data.overlay || ''] || ''
    style.backgroundImage = shade ? `linear-gradient(${shade}, ${shade}), url("${image}")` : `url("${image}")`
    style.backgroundSize = 'cover'
    style.backgroundPosition = 'center'
  }
  return style
})
const stats = computed(() => (props.data.stats || []).filter((s) => s?.value))
</script>
