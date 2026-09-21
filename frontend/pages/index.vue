<template>
  <!-- Loading -->
  <div v-if="pending" class="min-h-screen flex items-center justify-center">
    <svg class="w-10 h-10 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
    </svg>
  </div>

  <!-- Page found -->
  <div v-else-if="page">
    <div v-if="page.template_type === 'content'" class="container mx-auto px-4 py-12">
      <h1 class="text-4xl md:text-5xl font-bold mb-6 text-gray-900">{{ page.title }}</h1>
      <p v-if="page.excerpt" class="text-xl text-gray-600 mb-8">{{ page.excerpt }}</p>
      <div v-if="page.featured_image" class="mb-8 rounded-2xl overflow-hidden">
        <img :src="getImageUrl(page.featured_image)" :alt="page.title" class="w-full h-auto" />
      </div>
      <div class="prose prose-lg max-w-none" v-html="page.content" />
    </div>

    <PageBuilderPageRenderer v-else-if="page.blocks && page.blocks.length > 0" :blocks="page.blocks" />

    <div v-else class="container mx-auto px-4 py-12">
      <h1 class="text-4xl md:text-5xl font-bold mb-6 text-gray-900">{{ page.title }}</h1>
      <p v-if="page.excerpt" class="text-xl text-gray-600">{{ page.excerpt }}</p>
    </div>
  </div>

  <!-- No page configured yet -->
  <div v-else class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center px-4">
      <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-6">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <h2 class="text-2xl font-semibold text-gray-700 mb-2">Page coming soon</h2>
      <p class="text-gray-500">This site is being set up. Check back soon.</p>
    </div>
  </div>
</template>

<script setup>
const { fetchPage } = useApi()
const { getImageUrl } = useImageUrl()

const { data: page, pending } = await useAsyncData(
  'homepage',
  () => fetchPage('home').catch(() => null)
)

useSeoMeta({
  title: page.value?.meta_title || page.value?.title || '',
  description: page.value?.meta_description || page.value?.excerpt || '',
  ogTitle: page.value?.meta_title || page.value?.title || '',
  ogDescription: page.value?.meta_description || page.value?.excerpt || '',
  ogImage: page.value?.featured_image ? getImageUrl(page.value.featured_image) : undefined,
  twitterCard: 'summary_large_image',
})
</script>