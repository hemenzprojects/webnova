<template>
  <section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
      <h2 v-if="data.heading" class="text-4xl font-bold text-gray-900 text-center mb-12">
        {{ data.heading }}
      </h2>

      <!-- Carousel Display -->
      <div v-if="displayMode === 'carousel'" class="relative">
        <Swiper
          :modules="[Navigation, Pagination, Autoplay]"
          :slides-per-view="1"
          :space-between="30"
          :loop="true"
          :autoplay="{ delay: 3000, disableOnInteraction: false }"
          :navigation="true"
          :pagination="{ clickable: true }"
          :breakpoints="{
            640: { slidesPerView: 2 },
            768: { slidesPerView: 3 },
            1024: { slidesPerView: 4 },
            1280: { slidesPerView: 5 },
          }"
          class="members-carousel"
        >
          <SwiperSlide v-for="member in data.items" :key="member.id">
            <button
              @click="openModal(member)"
              class="block bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 h-full group w-full text-left cursor-pointer"
            >
              <div v-if="member.logo" class="aspect-square w-full mb-4 flex items-center justify-center bg-gray-50 rounded-lg overflow-hidden">
                <img
                  :src="getImageUrl(member.logo)"
                  :alt="member.name"
                  class="max-w-full max-h-full object-contain p-4 group-hover:scale-105 transition-transform duration-300"
                  loading="lazy"
                />
              </div>
              <h3 class="text-center font-semibold text-gray-900 group-hover:text-primary transition">
                {{ member.name }}
              </h3>
              <p v-if="member.type" class="text-center text-sm text-gray-500 mt-1 capitalize">
                {{ member.type.replace('_', ' ') }}
              </p>
            </button>
          </SwiperSlide>
        </Swiper>
      </div>

      <!-- Grid Display -->
      <div v-else-if="displayMode === 'grid'" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
        <button
          v-for="member in data.items"
          :key="member.id"
          @click="openModal(member)"
          class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 group text-left cursor-pointer w-full"
        >
          <div v-if="member.logo" class="aspect-square w-full mb-4 flex items-center justify-center bg-gray-50 rounded-lg overflow-hidden">
            <img
              :src="getImageUrl(member.logo)"
              :alt="member.name"
              class="max-w-full max-h-full object-contain p-4"
              loading="lazy"
            />
          </div>
          <h3 class="text-center font-semibold text-gray-900 group-hover:text-primary transition">
            {{ member.name }}
          </h3>
          <p v-if="member.type" class="text-center text-sm text-gray-500 mt-1 capitalize">
            {{ member.type.replace('_', ' ') }}
          </p>
        </button>
      </div>

      <div v-else-if="displayMode === 'logos'" class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
        <a
          v-for="member in data.items"
          :key="member.id"
          :href="member.website"
          target="_blank"
          rel="noopener noreferrer"
          class="aspect-square bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-300 p-4 flex items-center justify-center group"
          :title="member.name"
        >
          <img
            v-if="member.logo"
            :src="getImageUrl(member.logo)"
            :alt="member.name"
            class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform"
            loading="lazy"
          />
        </a>
      </div>

      <div v-else-if="displayMode === 'list'" class="max-w-4xl mx-auto space-y-6">
        <button
          v-for="member in data.items"
          :key="member.id"
          @click="openModal(member)"
          class="flex gap-6 bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 group text-left cursor-pointer w-full"
        >
          <div v-if="member.logo" class="w-24 h-24 flex-shrink-0 bg-gray-50 rounded-lg flex items-center justify-center p-3">
            <img
              :src="getImageUrl(member.logo)"
              :alt="member.name"
              class="max-w-full max-h-full object-contain"
              loading="lazy"
            />
          </div>
          <div class="flex-1">
            <h3 class="text-xl font-bold text-gray-900 mb-1 group-hover:text-primary transition">
              {{ member.name }}
            </h3>
            <p v-if="member.type" class="text-sm text-gray-500 mb-2 capitalize">
              {{ member.type.replace('_', ' ') }}
            </p>
            <p v-if="member.description" class="text-gray-600">
              {{ truncate(member.description, 150) }}
            </p>
            <a
              v-if="member.website"
              :href="member.website"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center gap-1 text-accent hover:underline mt-2"
              @click.stop
            >
              <span>Visit website</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
              </svg>
            </a>
          </div>
        </button>
      </div>
    </div>

    <!-- Member Detail Modal -->
    <MemberDetailModal
      :is-open="isModalOpen"
      :member="selectedMember"
      @close="closeModal"
    />
  </section>
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
    items: any[]
    display?: 'grid' | 'logos' | 'list' | 'carousel'
    layout?: 'grid' | 'logos' | 'list' | 'carousel' // Backward compatibility
    limit: string | number
  }
  blockId: string
}>()

const { getImageUrl } = useImageUrl()

// Use new property name, fallback to old one for backward compatibility
const displayMode = computed(() => props.data.display ?? props.data.layout ?? 'grid')

// Modal state
const isModalOpen = ref(false)
const selectedMember = ref(null)

const openModal = (member: any) => {
  selectedMember.value = member
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
  setTimeout(() => {
    selectedMember.value = null
  }, 300) // Wait for modal close animation
}

const truncate = (text: string, length: number) => {
  if (!text) return ''
  if (text.length <= length) return text
  return text.substring(0, length) + '...'
}
</script>

<style scoped>
.members-carousel :deep(.swiper-button-next),
.members-carousel :deep(.swiper-button-prev) {
  color: #0ea5e9;
  background: white;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.members-carousel :deep(.swiper-button-next::after),
.members-carousel :deep(.swiper-button-prev::after) {
  font-size: 20px;
  font-weight: bold;
}

.members-carousel :deep(.swiper-button-next:hover),
.members-carousel :deep(.swiper-button-prev:hover) {
  background: #0ea5e9;
  color: white;
}

.members-carousel :deep(.swiper-pagination-bullet) {
  width: 12px;
  height: 12px;
  background: #cbd5e1;
  opacity: 1;
}

.members-carousel :deep(.swiper-pagination-bullet-active) {
  background: #0ea5e9;
  width: 30px;
  border-radius: 6px;
}

.members-carousel :deep(.swiper-pagination) {
  bottom: -40px;
}
</style>
