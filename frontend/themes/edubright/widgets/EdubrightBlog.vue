<template>
  <section class="py-16 md:py-24" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4">
      <PageBuilderSectionIntro :eyebrow="data.eyebrow" :heading="data.heading" :text="data.subheading" />

      <div v-if="items.length" class="mt-12 grid md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
        <article
          v-for="post in items"
          :key="post.id"
          class="flex flex-col overflow-hidden"
          :style="{ backgroundColor: 'var(--color-surface-muted, #F4F0FE)', borderRadius: 'var(--radius-card)' }"
        >
          <NuxtLink :to="`/news/${post.slug}`" class="block">
            <img
              v-if="post.featured_image"
              :src="getImageUrl(post.featured_image)"
              :alt="post.title"
              class="w-full aspect-[16/10] object-cover"
              loading="lazy"
            />
            <div v-else class="w-full aspect-[16/10] bg-gradient-to-br from-primary to-accent" />
          </NuxtLink>

          <div class="flex flex-col flex-1 p-6">
            <div v-if="post.published_at" class="flex items-center gap-2 text-sm mb-3" :style="{ color: 'var(--color-text-muted)' }">
              <ThemeIcon name="calendar" class="w-4 h-4 text-primary" />
              {{ formatDate(post.published_at) }}
            </div>
            <h3 class="text-lg md:text-xl leading-snug mb-6" :style="{ fontFamily: 'var(--font-heading)', fontWeight: 600, color: 'var(--color-text)' }">
              <NuxtLink :to="`/news/${post.slug}`" class="hover:text-primary transition">{{ post.title }}</NuxtLink>
            </h3>
            <NuxtLink
              v-if="data.showReadMore !== false"
              :to="`/news/${post.slug}`"
              class="mt-auto self-start px-5 py-2 text-sm font-semibold text-white bg-primary-light hover:bg-primary transition"
              :style="{ borderRadius: 'var(--radius-button)' }"
            >
              {{ data.readMoreText || 'See more' }}
            </NuxtLink>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
// Edubright's version of the Posts (news) block
const props = defineProps<{
  data: {
    eyebrow?: string
    heading?: string
    subheading?: string
    showReadMore?: boolean
    readMoreText?: string
    items?: any[]
  }
  blockId: string
}>()

const { getImageUrl } = useImageUrl()
const items = computed(() => props.data.items || [])
const formatDate = (date: string) =>
  new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })
</script>
