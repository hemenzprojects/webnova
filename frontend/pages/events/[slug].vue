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
        <h1 class="text-4xl md:text-5xl font-bold text-white">Events</h1>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="pending" class="container mx-auto px-4 py-20">
      <div class="text-center">
        <div class="animate-pulse text-gray-400">Loading event...</div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="container mx-auto px-4 py-20">
      <div class="bg-red-50 border border-red-200 rounded-lg p-8 text-center">
        <h2 class="text-2xl font-bold text-red-900 mb-2">Event Not Found</h2>
        <p class="text-red-600 mb-6">{{ error.message }}</p>
        <NuxtLink to="/" class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Go to Homepage
        </NuxtLink>
      </div>
    </div>

    <!-- Event Content -->
    <div v-else class="container mx-auto px-4 py-12">
      <div :class="showSidebar ? 'max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-12' : 'max-w-4xl mx-auto'">
        <article :class="{ 'lg:col-span-2': showSidebar }">
          <!-- Title -->
          <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
            {{ event.title }}
          </h1>

          <!-- Meta Information -->
          <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-gray-600 mb-8 pb-8 border-b border-gray-200">
            <div class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>
                {{ formatDate(event.start_date) }}<template v-if="event.end_date"> – {{ formatDate(event.end_date) }}</template>
              </span>
            </div>
            <div v-if="event.venue || event.location" class="flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>{{ [event.venue, event.location].filter(Boolean).join(', ') }}</span>
            </div>
            <div v-if="event.is_featured" class="flex items-center gap-2 text-accent">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
              </svg>
              <span class="font-semibold">Featured</span>
            </div>
          </div>

          <!-- Featured Image -->
          <div v-if="event.featured_image" class="mb-8 rounded-2xl overflow-hidden shadow-xl">
            <img
              :src="getImageUrl(event.featured_image)"
              :alt="event.title"
              class="w-full h-auto object-cover"
            />
          </div>

          <!-- Event Description -->
          <div class="prose prose-lg max-w-none">
            <div class="text-gray-800 leading-relaxed" v-html="event.description"></div>
          </div>

          <!-- Registration -->
          <div v-if="event.registration_link" class="mt-8">
            <a
              :href="event.registration_link"
              target="_blank"
              rel="noopener"
              class="inline-block px-6 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-accent-dark transition"
            >
              Register for this event
            </a>
          </div>

          <!-- Attachments Section -->
          <div v-if="event.attachments && event.attachments.length > 0" class="mt-12 p-6 bg-gray-50 rounded-xl border border-gray-200">
            <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
              <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
              </svg>
              Downloads & Attachments
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <a
                v-for="(attachment, index) in event.attachments"
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
              </a>
            </div>
          </div>

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
          :items="otherEvents"
          :show-image="sidebar.showImage"
          :show-date="sidebar.showDate"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
const route = useRoute()
const { fetchEvent, fetchEvents } = useApi()
const { getImageUrl, getFileName } = useImageUrl()
const { getSidebarConfig } = useSidebarSettings()

// Fetch the event by slug
const { data: event, pending, error } = await useAsyncData(
  `event-${route.params.slug}`,
  () => fetchEvent(route.params.slug)
)

// Other events for the sidebar. Global options come from the admin's Sidebar
// Settings; each event can also turn its own sidebar off.
const { data: sidebar } = await useAsyncData('sidebar-config-events', () => getSidebarConfig('events'))

const { data: eventsData } = await useAsyncData('events-sidebar', () =>
  sidebar.value.enabled
    ? fetchEvents({
        per_page: sidebar.value.limit + 1,
        ...(sidebar.value.order === 'latest' ? { sort: 'latest' } : { upcoming: 1 }),
      })
    : { data: [] }
)

const otherEvents = computed(() =>
  (eventsData.value?.data || [])
    .filter((item) => item.id !== event.value?.id)
    .slice(0, sidebar.value.limit)
    .map((item) => ({ id: item.id, label: item.title, to: `/events/${item.slug}`, date: item.start_date, image: item.featured_image }))
)
const showSidebar = computed(() => sidebar.value.enabled && !!event.value?.show_sidebar && otherEvents.value.length > 0)

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })
}

// Strip HTML from the rich-text description for meta tags
const plainDescription = computed(() => (event.value?.description || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 160))

// Set SEO meta tags
useSeoMeta({
  title: event.value?.title || 'Event',
  description: plainDescription.value || 'Events from WEBNOVA',
  ogTitle: event.value?.title,
  ogDescription: plainDescription.value,
  ogImage: event.value?.featured_image ? getImageUrl(event.value.featured_image) : undefined,
})
</script>
