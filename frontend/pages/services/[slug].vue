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
        <h1 class="text-4xl md:text-5xl font-bold text-white">Our Services</h1>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="pending" class="container mx-auto px-4 py-20">
      <div class="text-center">
        <div class="animate-pulse text-gray-400">Loading service...</div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="container mx-auto px-4 py-20">
      <div class="bg-red-50 border border-red-200 rounded-lg p-8 text-center">
        <h2 class="text-2xl font-bold text-red-900 mb-2">Service Not Found</h2>
        <p class="text-red-600 mb-6">{{ error.message }}</p>
        <NuxtLink to="/" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Go to Homepage
        </NuxtLink>
      </div>
    </div>

    <!-- Service Content -->
    <div v-else class="container mx-auto px-4 py-12">
      <div :class="showSidebar ? 'max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-12' : 'max-w-4xl mx-auto'">
      <article :class="{ 'lg:col-span-2': showSidebar }">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-8">
          {{ service.name }}
        </h1>

        <!-- Featured Image -->
        <div v-if="service.featured_image" class="mb-8 rounded-2xl overflow-hidden shadow-xl">
          <img
            :src="getImageUrl(service.featured_image)"
            :alt="service.name"
            class="w-full h-auto object-cover"
          />
        </div>

        <!-- Full content, falling back to the short description -->
        <div
          v-if="service.content && service.content.trim()"
          class="text-lg text-gray-800 leading-relaxed whitespace-pre-line"
          v-html="service.content.trim()"
        ></div>
        <p v-else class="text-lg text-gray-800 leading-relaxed whitespace-pre-line">
          {{ service.description }}
        </p>

        <!-- Back -->
        <div class="mt-12 pt-8 border-t border-gray-200">
          <NuxtLink
            to="/"
            class="inline-flex items-center gap-2 text-primary hover:text-accent transition font-semibold"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Home
          </NuxtLink>
        </div>
      </article>

      <DetailSidebar
        v-if="showSidebar"
        :class="{ 'lg:order-first': sidebar.position === 'left' }"
        :title="sidebar.title"
        :items="otherServices"
        :show-image="sidebar.showImage"
        :show-date="sidebar.showDate"
      />
      </div>
    </div>
  </div>
</template>

<script setup>
const route = useRoute()
const { fetchService, fetchServices } = useApi()
const { getImageUrl } = useImageUrl()
const { getSidebarConfig } = useSidebarSettings()

// Fetch the service by slug
const { data: service, pending, error } = await useAsyncData(
  `service-${route.params.slug}`,
  () => fetchService(route.params.slug)
)

// Other services for the sidebar. Global options come from the admin's Sidebar
// Settings; each service can also turn its own sidebar off.
const { data: sidebar } = await useAsyncData('sidebar-config-services', () => getSidebarConfig('services'))

const { data: allServices } = await useAsyncData('services-sidebar', () =>
  sidebar.value.enabled
    ? fetchServices({ limit: sidebar.value.limit + 1, sort: sidebar.value.order })
    : []
)

const otherServices = computed(() =>
  (allServices.value || [])
    .filter((item) => item.id !== service.value?.id)
    .slice(0, sidebar.value.limit)
    .map((item) => ({ id: item.id, label: item.name, to: `/services/${item.slug}`, image: item.featured_image }))
)
const showSidebar = computed(() => sidebar.value.enabled && !!service.value?.show_sidebar && otherServices.value.length > 0)

// Set SEO meta tags
useSeoMeta({
  title: service.value?.name || 'Service',
  description: service.value?.description || 'Services offered by WEBNOVA',
  ogTitle: service.value?.name,
  ogDescription: service.value?.description,
  ogImage: service.value?.featured_image ? getImageUrl(service.value.featured_image) : undefined,
})
</script>