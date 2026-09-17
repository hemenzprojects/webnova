<template>
  <section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <h2 v-if="data.heading" class="text-4xl font-bold text-gray-900 text-center mb-12">
        {{ data.heading }}
      </h2>
      <p v-if="data.subheading" class="text-gray-600 text-center max-w-2xl mx-auto mb-8">
        {{ data.subheading }}
      </p>

      <!-- Empty State -->
      <div v-if="!data.items || data.items.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No items to display</h3>
        <p class="text-gray-500">No {{ getContentTypeLabel() }} are available at the moment.</p>
      </div>

      <div v-else class="relative">
        <Swiper
          :modules="[Navigation, Pagination, Autoplay]"
          :slides-per-view="1"
          :space-between="30"
          :loop="data.items.length > slidesPerView"
          :autoplay="data.autoplay !== false ? { delay: data.autoplayDelay || 3000, disableOnInteraction: false } : false"
          :navigation="data.showNavigation !== false"
          :pagination="data.showPagination !== false ? { clickable: true } : false"
          :breakpoints="breakpoints"
          class="dynamic-carousel"
        >
          <SwiperSlide v-for="item in data.items" :key="item.id">
            <!-- Members -->
            <div v-if="data.contentType === 'members'" class="h-full">
              <button
                @click="openModal(item)"
                class="block bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 h-full group w-full text-left cursor-pointer"
              >
                <div v-if="item.logo" class="aspect-square w-full mb-4 flex items-center justify-center bg-gray-50 rounded-lg overflow-hidden">
                  <img
                    :src="getImageUrl(item.logo)"
                    :alt="item.name"
                    class="max-w-full max-h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                </div>
                <h3 class="text-center font-semibold text-gray-900 group-hover:text-primary transition">
                  {{ item.name }}
                </h3>
                <p v-if="item.type" class="text-center text-sm text-gray-500 mt-1 capitalize">
                  {{ item.type.replace('_', ' ') }}
                </p>
              </button>
            </div>

            <!-- Services -->
            <div v-else-if="data.contentType === 'services'" class="h-full">
              <NuxtLink
                :to="`/services/${item.slug}`"
                class="block bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 h-full group"
              >
                <div class="w-12 h-12 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center mb-4">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-primary transition">
                  {{ item.name }}
                </h3>
                <p v-if="item.description" class="text-gray-600 leading-relaxed">
                  {{ truncate(item.description, 120) }}
                </p>
              </NuxtLink>
            </div>

            <!-- News -->
            <div v-else-if="data.contentType === 'news'" class="h-full">
              <NuxtLink
                :to="`/news/${item.slug}`"
                class="block bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden h-full group"
              >
                <div v-if="item.featured_image" class="aspect-video overflow-hidden">
                  <img
                    :src="getImageUrl(item.featured_image)"
                    :alt="item.title"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                </div>
                <div class="p-6">
                  <div class="flex items-center gap-2 mb-3 text-sm text-gray-500">
                    <span v-if="item.category" class="px-2 py-1 bg-primary/10 text-primary rounded">{{ item.category }}</span>
                    <span v-if="item.published_at">{{ formatDate(item.published_at) }}</span>
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-primary transition">
                    {{ item.title }}
                  </h3>
                  <p v-if="item.excerpt" class="text-gray-600">
                    {{ truncate(item.excerpt, 120) }}
                  </p>
                </div>
              </NuxtLink>
            </div>

            <!-- Events -->
            <div v-else-if="data.contentType === 'events'" class="h-full">
              <NuxtLink
                :to="`/events/${item.slug}`"
                class="block bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden h-full group"
              >
                <div v-if="item.featured_image" class="aspect-video overflow-hidden">
                  <img
                    :src="getImageUrl(item.featured_image)"
                    :alt="item.title"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                </div>
                <div class="p-6">
                  <div class="flex items-center gap-2 mb-3 text-sm">
                    <div class="flex items-center gap-1 text-primary">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <span>{{ formatDate(item.start_date) }}</span>
                    </div>
                  </div>
                  <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:text-primary transition">
                    {{ item.title }}
                  </h3>
                  <p v-if="item.location" class="flex items-center gap-1 text-gray-600 text-sm mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ item.location }}
                  </p>
                  <p v-if="item.description" class="text-gray-600">
                    {{ truncate(item.description, 100) }}
                  </p>
                </div>
              </NuxtLink>
            </div>

            <!-- Team Members -->
            <div v-else-if="data.contentType === 'team_members'" class="h-full">
              <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden h-full group">
                <div v-if="item.photo" class="aspect-square overflow-hidden">
                  <img
                    :src="getImageUrl(item.photo)"
                    :alt="item.name"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                </div>
                <div class="p-6 text-center">
                  <h3 class="text-xl font-bold text-gray-900 mb-1 group-hover:text-primary transition">
                    {{ item.name }}
                  </h3>
                  <p v-if="item.role" class="text-primary font-medium mb-2">{{ item.role }}</p>
                  <p v-if="item.bio" class="text-gray-600 text-sm">
                    {{ truncate(item.bio, 80) }}
                  </p>
                </div>
              </div>
            </div>
          </SwiperSlide>
        </Swiper>
      </div>
    </div>
  </section>

  <MemberDetailModal
    :is-open="isModalOpen"
    :member="selectedMember"
    @close="closeModal"
  />
</template>

<script setup lang="ts">
import { Swiper, SwiperSlide } from 'swiper/vue'
import { Navigation, Pagination, Autoplay } from 'swiper/modules'
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'

const props = defineProps<{
  data: {
    heading?: string
    subheading?: string
    contentType: 'members' | 'services' | 'news' | 'events' | 'team_members'
    items: any[]
    limit: string | number
    itemsPerView?: number
    autoplay?: boolean
    autoplayDelay?: number
    showNavigation?: boolean
    showPagination?: boolean
    // Style options
    navigationColor?: string
    navigationBgColor?: string
    navigationHoverColor?: string
    paginationColor?: string
    paginationActiveColor?: string
  }
  blockId: string
}>()

const { getImageUrl } = useImageUrl()

const isModalOpen = ref(false)
const selectedMember = ref(null)

const openModal = (member: any) => {
  selectedMember.value = member
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  setTimeout(() => { selectedMember.value = null }, 300)
}

// Calculate slides per view based on content type
const slidesPerView = computed(() => {
  const itemsPerView = props.data.itemsPerView || 4
  return Math.min(itemsPerView, props.data.items.length)
})

// Responsive breakpoints
const breakpoints = computed(() => {
  const items = props.data.itemsPerView || 4
  return {
    320: { slidesPerView: 1 },
    640: { slidesPerView: Math.min(2, items) },
    768: { slidesPerView: Math.min(3, items) },
    1024: { slidesPerView: Math.min(4, items) },
    1280: { slidesPerView: items },
  }
})

const truncate = (text: string, length: number) => {
  if (!text) return ''
  if (text.length <= length) return text
  return text.substring(0, length) + '...'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getContentTypeLabel = () => {
  const labels: Record<string, string> = {
    members: 'members',
    services: 'services',
    news: 'news articles',
    events: 'events',
    team_members: 'team members'
  }
  return labels[props.data.contentType] || 'items'
}
</script>

<style scoped>
.dynamic-carousel :deep(.swiper-button-next),
.dynamic-carousel :deep(.swiper-button-prev) {
  color: v-bind('navigationColor || "#0ea5e9"');
  background: v-bind('navigationBgColor || "white"');
  width: 40px;
  height: 40px;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transition: all 0.3s ease;
}

.dynamic-carousel :deep(.swiper-button-next::after),
.dynamic-carousel :deep(.swiper-button-prev::after) {
  font-size: 20px;
  font-weight: bold;
}

.dynamic-carousel :deep(.swiper-button-next:hover),
.dynamic-carousel :deep(.swiper-button-prev:hover) {
  background: v-bind('navigationHoverColor || navigationColor || "#0ea5e9"');
  color: white;
  transform: scale(1.1);
}

.dynamic-carousel :deep(.swiper-pagination-bullet) {
  width: 12px;
  height: 12px;
  background: v-bind('paginationColor || "#cbd5e1"');
  opacity: 1;
  transition: all 0.3s ease;
}

.dynamic-carousel :deep(.swiper-pagination-bullet-active) {
  background: v-bind('paginationActiveColor || navigationColor || "#0ea5e9"');
  width: 30px;
  border-radius: 6px;
}

.dynamic-carousel :deep(.swiper-pagination) {
  bottom: -40px;
}
</style>