<template>
  <section class="py-16 md:py-24" :style="{ backgroundColor: data.background === 'plain' ? 'var(--block-bg, var(--color-surface, #fff))' : 'var(--block-bg, var(--color-surface-muted, #f3f4f6))' }">
    <div class="container mx-auto px-4 grid lg:grid-cols-2 gap-12 items-center">
      <div :class="data.imagePosition === 'left' ? 'lg:order-2' : ''">
        <PageBuilderSectionIntro :eyebrow="data.eyebrow" :heading="data.heading" :text="data.text" align="left" />

        <div v-if="data.features?.length" class="mt-9 grid sm:grid-cols-2 gap-x-8 gap-y-7">
          <div v-for="(feature, i) in data.features" :key="i" class="flex gap-4">
            <ThemeIcon :name="feature.icon" class="w-11 h-11 shrink-0 text-primary" />
            <div>
              <h3 class="text-lg md:text-xl mb-1.5" :style="{ fontFamily: 'var(--font-heading)', fontWeight: 600, color: 'var(--color-text)' }">
                {{ feature.title }}
              </h3>
              <p class="leading-relaxed" :style="{ color: 'var(--color-text-muted)' }">{{ feature.text }}</p>
            </div>
          </div>
        </div>
      </div>

      <img
        v-if="imageUrl"
        :src="imageUrl"
        alt=""
        class="w-full aspect-[4/3.7] object-cover"
        :style="{ borderRadius: 'var(--radius-card, 12px)' }"
        loading="lazy"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    eyebrow?: string
    heading?: string
    text?: string
    image?: string
    imagePosition?: 'left' | 'right'
    background?: 'tinted' | 'plain'
    features?: { icon?: string; title: string; text?: string }[]
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()
const imageUrl = computed(() => transformImageUrl(props.data.image || null))
</script>
