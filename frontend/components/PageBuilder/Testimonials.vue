<template>
  <section class="py-16 md:py-24" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4">
      <PageBuilderSectionIntro :eyebrow="data.eyebrow" :heading="data.heading" :text="data.text" />

      <div class="mt-12 grid md:grid-cols-2 lg:grid-cols-3 gap-5 max-w-6xl mx-auto">
        <figure
          v-for="(item, i) in data.items"
          :key="i"
          class="relative overflow-hidden px-8 pt-8 pb-10 text-center"
          :style="{ backgroundColor: 'var(--color-surface-muted, #f3f4f6)', borderRadius: 'var(--radius-card, 12px)' }"
        >
          <!-- Quote mark in the corner -->
          <span class="absolute top-0 right-0 w-[74px] h-[74px] flex items-start justify-end p-4 bg-primary text-white" style="border-bottom-left-radius: 74px" aria-hidden="true">
            <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M10 7H5a1 1 0 00-1 1v5a1 1 0 001 1h3v1a3 3 0 01-3 3v2a5 5 0 005-5V8a1 1 0 00-1-1zm10 0h-5a1 1 0 00-1 1v5a1 1 0 001 1h3v1a3 3 0 01-3 3v2a5 5 0 005-5V8a1 1 0 00-1-1z" /></svg>
          </span>

          <img
            v-if="photo(item)"
            :src="photo(item)!"
            :alt="item.name"
            class="w-16 h-16 rounded-full object-cover mx-auto mb-4"
            loading="lazy"
          />
          <figcaption class="mb-4">
            <div class="font-bold" :style="{ color: 'var(--color-text)' }">{{ item.name }}</div>
            <div v-if="item.role" class="text-sm" :style="{ color: 'var(--color-text-muted)' }">{{ item.role }}</div>
          </figcaption>
          <blockquote class="leading-relaxed" :style="{ color: 'var(--color-text-muted)' }">{{ item.quote }}</blockquote>
        </figure>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
defineProps<{
  data: {
    eyebrow?: string
    heading?: string
    text?: string
    items?: { photo?: string; name: string; role?: string; quote: string }[]
  }
  blockId: string
}>()

const { transformImageUrl } = usePageBuilder()
const photo = (item: { photo?: string }) => transformImageUrl(item.photo || null)
</script>
