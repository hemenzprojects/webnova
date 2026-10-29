<template>
  <section class="py-14 md:py-20" :style="{ backgroundColor: 'var(--block-bg, var(--color-surface-muted, #f3f4f6))' }">
    <div class="container mx-auto px-4">
      <PageBuilderSectionIntro :eyebrow="data.eyebrow" :heading="data.heading" />
      <div class="mt-8 flex flex-wrap items-center justify-center gap-x-14 gap-y-8">
        <component
          :is="logo.url ? 'a' : 'div'"
          v-for="(logo, i) in logos"
          :key="i"
          :href="logo.url || undefined"
          target="_blank"
          rel="noopener noreferrer"
          class="block opacity-90 hover:opacity-100 transition"
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
</script>
