<template>
  <!-- Members-only page and the visitor may not see it (Membership plugin) -->
  <MembersOnlyNotice v-if="page?.restricted" :reason="page.reason" :title="page.title" />
  <div v-else-if="page">
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
  async (): Promise<any> => {
    try {
      return await fetchPage(route.params.slug as string)
    } catch (e: any) {
      // A members-only page: show who may see it instead of a 404
      if (e?.statusCode === 403 && e?.data?.restricted) return e.data
      throw e
    }
  }
)

// Handle 404
if (error.value || !page.value) {
  throw createError({
    statusCode: 404,
    statusMessage: 'Page not found',
    fatal: true
  })
}

if (page.value?.restricted) {
  useHead({ title: page.value.title, meta: [{ name: 'robots', content: 'noindex' }] })
  if (process.server) {
    const event = useRequestEvent()
    if (event) setResponseStatus(event, 403)
  }
}

// SEO Meta Tags
if (!page.value?.restricted) useSeoMeta({
  title: page.value.meta_title || page.value.title,
  description: page.value.meta_description || page.value.excerpt || undefined,
  ogTitle: page.value.meta_title || page.value.title,
  ogDescription: page.value.meta_description || page.value.excerpt || undefined,
  ogImage: page.value.featured_image ? getImageUrl(page.value.featured_image) : undefined,
  twitterCard: 'summary_large_image',
})
</script>
