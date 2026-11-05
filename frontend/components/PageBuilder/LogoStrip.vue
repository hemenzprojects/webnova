<template>
  <section class="py-14 md:py-20" :style="{ backgroundColor: 'var(--block-bg, var(--color-surface-muted, #f3f4f6))' }">
    <div class="container mx-auto px-4">
      <PageBuilderSectionIntro :eyebrow="data.eyebrow" :heading="data.heading" />
      <!-- Five or more logos scroll by endlessly (paused on hover); fewer stay in a row -->
      <div v-if="scrolling" class="marquee mt-10" :style="{ '--marquee-duration': `${logos.length * 4}s` }">
        <div class="marquee-track items-center">
          <template v-for="copy in 2" :key="copy">
            <component
              :is="logo.url ? 'a' : 'div'"
              v-for="(logo, i) in logos"
              :key="`${copy}-${i}`"
              :href="logo.url || undefined"
              :target="logo.url ? '_blank' : undefined"
              :rel="logo.url ? 'noopener noreferrer' : undefined"
              :aria-hidden="copy === 2 ? 'true' : undefined"
              :tabindex="copy === 2 ? -1 : undefined"
              :class="['logo-tint block shrink-0 px-8 md:px-12', copy === 2 ? 'marquee-copy' : '']"
            >
              <img :src="logo.src" :alt="copy === 1 ? (logo.alt || 'Partner logo') : ''" class="h-10 md:h-12 w-auto max-w-[180px] object-contain" loading="lazy" />
            </component>
          </template>
        </div>
      </div>
      <div v-else v-reveal-stagger class="mt-8 flex flex-wrap items-center justify-center gap-x-14 gap-y-8">
        <component
          :is="logo.url ? 'a' : 'div'"
          v-for="(logo, i) in logos"
          :key="i"
          :href="logo.url || undefined"
          target="_blank"
          rel="noopener noreferrer"
          class="logo-tint block"
        >
          <img :src="logo.src" :alt="logo.alt || 'Partner logo'" class="h-10 md:h-12 w-auto" loading="lazy" />
        </component>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    eyebrow?: string
    heading?: string
    logos?: { image: string; alt?: string; url?: string }[]
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()
const logos = computed(() =>
  (props.data.logos || [])
    .map((logo) => ({ ...logo, src: transformImageUrl(logo.image) }))
    .filter((logo) => logo.src)
)
const scrolling = computed(() => logos.value.length >= 5)
</script>
