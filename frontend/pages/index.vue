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

  <!-- Fallback to old homepage if no page found -->
  <div v-else>
    <!-- Hero Section -->
    <HeroSection />

    <!-- Stats Section -->
    <StatsSection />

    <!-- Services Section -->
    <section class="py-20 bg-white">
      <div class="container mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">Our Services</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Delivering cutting-edge connectivity and digital solutions for Ghana's academic community
          </p>
        </div>

        <div v-if="servicesPending" class="text-center py-8">
          <div class="animate-pulse text-gray-400">Loading services...</div>
        </div>

        <div v-else-if="servicesError" class="text-center py-8">
          <p class="text-red-600">Error loading services: {{ servicesError.message }}</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="service in services"
            :key="service.id"
            class="group p-8 border border-gray-200 rounded-2xl hover:border-accent hover:shadow-xl transition-all duration-300"
          >
            <div class="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition">{{ service.name }}</h3>
            <p class="text-gray-600 leading-relaxed">{{ service.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Latest News Section -->
    <section class="py-20 bg-gray-50">
      <div class="container mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">Latest News</h2>
          <p class="text-gray-600 max-w-2xl mx-auto">
            Stay updated with the latest developments in Ghana's academic network
          </p>
        </div>

        <div v-if="newsPending" class="text-center py-8">
          <div class="animate-pulse text-gray-400">Loading news...</div>
        </div>

        <div v-else-if="newsError" class="text-center py-8">
          <p class="text-red-600">Error loading news: {{ newsError.message }}</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <NuxtLink
            v-for="item in news"
            :key="item.id"
            :to="`/news/${item.slug}`"
            class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 group overflow-hidden flex flex-col"
          >
            <!-- Featured Image -->
            <div v-if="item.featured_image" class="aspect-video w-full overflow-hidden bg-gray-100">
              <img
                :src="getNewsImageUrl(item.featured_image)"
                :alt="item.title"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
              />
            </div>
            <div v-else class="aspect-video w-full bg-gradient-to-br from-primary to-accent flex items-center justify-center">
              <svg class="w-16 h-16 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
              </svg>
            </div>

            <!-- Card Content -->
            <div class="p-8 flex-1 flex flex-col">
              <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ new Date(item.published_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
              </div>
              <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition">{{ item.title }}</h3>
              <p class="text-gray-600 mb-6 leading-relaxed flex-1">{{ item.excerpt }}</p>
              <div class="inline-flex items-center gap-2 group-hover:gap-3 transition-all font-semibold" :style="{ color: 'var(--color-accent)' }">
                <span>Read more</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
              </div>
            </div>
          </NuxtLink>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
const { fetchPage, fetchServices, fetchNews } = useApi()
const { getImageUrl } = useImageUrl()

// Try to fetch a dynamic homepage with slug 'home'
const { data: page, error: pageError } = await useAsyncData(
  'homepage',
  () => fetchPage('home'),
  {
    // Don't throw error if page doesn't exist - fallback to old homepage
    server: false,
  }
)

// Helper to get news image URL
const getNewsImageUrl = (path) => getImageUrl(path)

// Fetch services from backend (for fallback homepage)
const { data: services, pending: servicesPending, error: servicesError } = await useAsyncData(
  'services',
  () => fetchServices()
)

// Fetch latest news from backend (for fallback homepage)
const { data: newsData, pending: newsPending, error: newsError } = await useAsyncData(
  'news',
  () => fetchNews({ limit: 3 })
)

const news = computed(() => newsData.value?.data || [])

// SEO Meta Tags - use page data if available, otherwise default
useSeoMeta({
  title: page.value?.meta_title || page.value?.title || 'WEBNOVA - Ghanaian Academic and Research Network',
  description: page.value?.meta_description || page.value?.excerpt || 'Connecting Ghana\'s education sector through high-speed internet and innovation',
  ogTitle: page.value?.meta_title || page.value?.title || 'WEBNOVA - Ghanaian Academic and Research Network',
  ogDescription: page.value?.meta_description || page.value?.excerpt || 'Connecting Ghana\'s education sector through high-speed internet and innovation',
  ogImage: page.value?.featured_image ? getImageUrl(page.value.featured_image) : undefined,
  twitterCard: 'summary_large_image',
})
</script>