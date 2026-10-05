<template>
  <section class="py-16 md:py-24" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4">
      <PageBuilderSectionIntro :eyebrow="data.eyebrow" :heading="data.heading" :text="data.subheading" />

      <div v-if="items.length" class="mt-12 grid sm:grid-cols-2 gap-6" :class="columnClass">
        <article
          v-for="course in items"
          :key="course.id"
          class="flex flex-col p-6"
          :style="{ backgroundColor: 'var(--color-surface-muted, #F4F0FE)', borderRadius: 'var(--radius-card)' }"
        >
          <NuxtLink :to="`/services/${course.slug}`" class="block mb-6">
            <img
              v-if="course.featured_image"
              :src="getImageUrl(course.featured_image)"
              :alt="course.name"
              class="w-full aspect-[6/5] object-cover"
              :style="{ borderRadius: 'var(--radius-button)' }"
              loading="lazy"
            />
            <div
              v-else
              class="w-full aspect-[6/5] flex items-center justify-center bg-gradient-to-br from-primary to-accent"
              :style="{ borderRadius: 'var(--radius-button)' }"
            >
              <ThemeIcon name="academic-cap" class="w-14 h-14 text-white/70" />
            </div>
          </NuxtLink>

          <h3 class="text-xl md:text-2xl leading-snug mb-4" :style="{ fontFamily: 'var(--font-heading)', fontWeight: 600, color: 'var(--color-text)' }">
            <NuxtLink :to="`/services/${course.slug}`" class="hover:text-primary transition">{{ course.name }}</NuxtLink>
          </h3>

          <div v-if="course.category || course.price_label" class="flex flex-wrap gap-x-8 gap-y-2 mb-6" :style="{ color: 'var(--color-text-muted)' }">
            <span v-if="course.category" class="inline-flex items-center gap-2">
              <ThemeIcon name="folder" class="w-4 h-4 text-primary" />{{ course.category }}
            </span>
            <span v-if="course.price_label" class="inline-flex items-center gap-2">
              <ThemeIcon name="tag" class="w-4 h-4 text-primary" />{{ course.price_label }}
            </span>
          </div>

          <NuxtLink
            v-if="data.showReadMore !== false"
            :to="`/services/${course.slug}`"
            class="mt-auto flex items-center justify-center gap-2 py-2.5 font-semibold text-white bg-primary hover:opacity-90 transition"
            :style="{ borderRadius: 'var(--radius-button)' }"
          >
            {{ data.readMoreText || 'View Details' }}
            <ThemeIcon name="arrow-right" class="w-4 h-4" />
          </NuxtLink>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
// Edubright's version of the Services block: course cards with category and price tags
const props = defineProps<{
  data: {
    eyebrow?: string
    heading?: string
    subheading?: string
    columns?: string | number
    showReadMore?: boolean
    readMoreText?: string
    items?: any[]
  }
  blockId: string
}>()

const { getImageUrl } = useImageUrl()
const items = computed(() => props.data.items || [])
const columnClass = computed(() => ({
  '2': 'lg:grid-cols-2',
  '3': 'lg:grid-cols-3',
  '4': 'lg:grid-cols-4',
}[String(props.data.columns || 4)] || 'lg:grid-cols-4'))
</script>
