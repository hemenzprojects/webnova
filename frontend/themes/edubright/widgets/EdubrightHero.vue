<template>
  <section class="relative overflow-hidden" :style="{ backgroundColor: 'var(--color-surface-muted)' }">
    <div class="container mx-auto px-4 py-20 lg:py-28">
      <div class="grid lg:grid-cols-2 gap-12 items-center">
        <div>
          <h1
            class="text-5xl md:text-6xl leading-tight mb-6"
            :style="{ fontFamily: 'var(--font-heading)', fontWeight: 'var(--font-heading-weight)', color: 'var(--color-text)' }"
          >
            {{ data.heading }}
          </h1>
          <p v-if="data.subheading" class="text-lg mb-8 max-w-xl" :style="{ color: 'var(--color-text-muted)' }">
            {{ data.subheading }}
          </p>
          <NuxtLink
            v-if="data.ctaText && data.ctaLink"
            :to="data.ctaLink"
            class="inline-flex items-center px-6 py-3 text-white font-medium transition-transform hover:scale-105"
            :style="{ backgroundColor: 'var(--color-primary)', borderRadius: 'var(--radius-button)' }"
          >
            {{ data.ctaText }}
          </NuxtLink>
        </div>

        <div v-if="foregroundImageUrl" class="relative">
          <img :src="foregroundImageUrl" alt="" class="w-full h-auto" />
          <div
            v-if="data.stat"
            class="absolute top-6 right-6 bg-white px-6 py-4 text-center shadow-lg"
            :style="{ borderRadius: 'var(--radius-card)' }"
          >
            <div class="text-3xl font-bold" :style="{ color: 'var(--color-text)' }">{{ data.stat.value }}</div>
            <div class="text-xs tracking-wider mt-1" :style="{ color: 'var(--color-text-muted)' }">
              {{ data.stat.label }}
            </div>
          </div>
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
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()
const foregroundImageUrl = computed(() => transformImageUrl(props.data.foregroundImage || null))
</script>