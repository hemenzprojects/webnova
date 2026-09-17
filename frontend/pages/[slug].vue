<template>
  <div v-if="page">
    <!-- Legacy Content Mode -->
    <div v-if="page.template_type === 'content'" class="container mx-auto px-4 py-12">
      <h1 class="text-4xl md:text-5xl font-bold mb-6 text-gray-900">{{ page.title }}</h1>
      <p v-if="page.excerpt" class="text-xl text-gray-600 mb-8">{{ page.excerpt }}</p>
      <div v-if="page.featured_image" class="mb-8 rounded-2xl overflow-hidden">
        <img :src="getImageUrl(page.featured_image)" :alt="page.title" class="w-full h-auto" />
      </div>
      <div class="prose prose-lg max-w-none" v-html="page.content" />
    </div>

    <!-- Page Builder Mode -->
    <PageBuilderPageRenderer v-else-if="page.blocks && page.blocks.length > 0" :blocks="page.blocks" />

    <!-- Fallback -->
    <div v-else class="container mx-auto px-4 py-12">
      <h1 class="text-4xl md:text-5xl font-bold mb-6 text-gray-900">{{ page.title }}</h1>
      <p v-if="page.excerpt" class="text-xl text-gray-600">{{ page.excerpt }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const { fetchPage } = useApi()
const { getImageUrl } = useImageUrl()

// Fetch page data
const { data: page, error } = await useAsyncData(
  `page-${route.params.slug}`,
  () => fetchPage(route.params.slug as string)
)

// Handle 404
if (error.value || !page.value) {
  throw createError({
    statusCode: 404,
    statusMessage: 'Page not found',
    fatal: true
  })
}

// SEO Meta Tags
useSeoMeta({
  title: page.value.meta_title || page.value.title,
  description: page.value.meta_description || page.value.excerpt,
  ogTitle: page.value.meta_title || page.value.title,
  ogDescription: page.value.meta_description || page.value.excerpt,
  ogImage: page.value.featured_image ? getImageUrl(page.value.featured_image) : undefined,
  twitterCard: 'summary_large_image',
})
</script>
