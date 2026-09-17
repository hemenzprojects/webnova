<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Page Header -->
    <section class="bg-primary py-12">
      <div class="container mx-auto px-4">
        <NuxtLink to="/" class="inline-flex items-center text-gray-300 hover:text-white mb-4 transition">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to Home
        </NuxtLink>
        <h1 class="text-4xl md:text-5xl font-bold text-white">News & Updates</h1>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="pending" class="container mx-auto px-4 py-20">
      <div class="text-center">
        <div class="animate-pulse text-gray-400">Loading article...</div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="container mx-auto px-4 py-20">
      <div class="bg-red-50 border border-red-200 rounded-lg p-8 text-center">
        <h2 class="text-2xl font-bold text-red-900 mb-2">Article Not Found</h2>
        <p class="text-red-600 mb-6">{{ error.message }}</p>
        <NuxtLink to="/" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Go to Homepage
        </NuxtLink>
      </div>
    </div>

    <!-- Article Content -->
    <article v-else class="container mx-auto px-4 py-12">
      <div class="max-w-4xl mx-auto">
        <!-- Article Header -->
        <div class="mb-8">
          <!-- Category Badge -->
          <div v-if="article.category" class="mb-4">
            <span class="inline-block px-4 py-2 bg-accent/10 text-accent rounded-full text-sm font-semibold">
              {{ article.category }}
            </span>
          </div>

          <!-- Title -->
          <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
            {{ article.title }}
          </h1>

          <!-- Meta Information -->
          <div class="flex items-center gap-6 text-gray-600 mb-8 pb-8 border-b border-gray-200">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>{{ new Date(article.published_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
            </div>
            <div v-if="article.is_featured" class="flex items-center gap-2 text-accent">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
              </svg>
              <span class="font-semibold">Featured</span>
            </div>
          </div>

          <!-- Featured Image -->
          <div v-if="article.featured_image" class="mb-8 rounded-2xl overflow-hidden shadow-xl">
            <img
              :src="getImageUrl(article.featured_image)"
              :alt="article.title"
              class="w-full h-auto object-cover"
            />
          </div>
        </div>

        <!-- Article Excerpt -->
        <div v-if="article.excerpt" class="text-xl text-gray-700 leading-relaxed mb-8 p-6 bg-gray-100 rounded-xl border-l-4 border-accent">
          {{ article.excerpt }}
        </div>

        <!-- Article Content -->
        <div class="prose prose-lg max-w-none">
          <div class="text-gray-800 leading-relaxed" v-html="article.content"></div>
        </div>

        <!-- Attachments Section -->
        <div v-if="article.attachments && article.attachments.length > 0" class="mt-12 p-6 bg-gray-50 rounded-xl border border-gray-200">
          <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
            Downloads & Attachments
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a
              v-for="(attachment, index) in article.attachments"
              :key="index"
              :href="getImageUrl(attachment)"
              target="_blank"
              download
              class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-accent hover:shadow-md transition group"
            >
              <div class="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 truncate group-hover:text-accent transition">
                  {{ getFileName(attachment) }}
                </p>
                <p class="text-sm text-gray-500">Click to download</p>
              </div>
              <svg class="w-5 h-5 text-gray-400 group-hover:text-accent transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </a>
          </div>
        </div>

        <!-- Share Section -->
        <div class="mt-12 pt-8 border-t border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Share this article</h3>
          <div class="flex gap-4">
            <a
              :href="`https://twitter.com/intent/tweet?text=${encodeURIComponent(article.title)}&url=${encodeURIComponent(currentUrl)}`"
              target="_blank"
              class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center hover:bg-accent hover:text-white transition"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
              </svg>
            </a>
            <a
              :href="`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentUrl)}`"
              target="_blank"
              class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center hover:bg-accent hover:text-white transition"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </a>
            <a
              :href="`https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(currentUrl)}&title=${encodeURIComponent(article.title)}`"
              target="_blank"
              class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center hover:bg-accent hover:text-white transition"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
              </svg>
            </a>
          </div>
        </div>

        <!-- Back to News -->
        <div class="mt-12">
          <NuxtLink
            to="/#news"
            class="inline-flex items-center gap-2 text-primary hover:text-accent transition font-semibold"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to All News
          </NuxtLink>
        </div>
      </div>
    </article>
  </div>
</template>

<script setup>
const route = useRoute()
const { fetchNewsItem } = useApi()
const { getImageUrl, getFileName } = useImageUrl()

// Get the current URL for sharing
const currentUrl = computed(() => {
  if (process.client) {
    return window.location.href
  }
  return ''
})

// Fetch the news article by slug
const { data: article, pending, error } = await useAsyncData(
  `news-${route.params.slug}`,
  () => fetchNewsItem(route.params.slug)
)

// Set SEO meta tags
useSeoMeta({
  title: article.value?.title || 'News Article',
  description: article.value?.excerpt || 'Read the latest news from WEBNOVA',
  ogTitle: article.value?.title,
  ogDescription: article.value?.excerpt,
  ogImage: article.value?.featured_image ? getImageUrl(article.value.featured_image) : undefined,
})
</script>