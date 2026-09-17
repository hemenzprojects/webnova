<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/50 backdrop-blur-sm"
        @click.self="close"
      >
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 scale-95 translate-y-4"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-95 translate-y-4"
        >
          <div
            v-if="isOpen"
            class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
          >
            <!-- Close Button -->
            <button
              @click="close"
              class="absolute top-4 right-4 z-10 p-2 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors"
              aria-label="Close"
            >
              <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>

            <!-- Modal Content -->
            <div class="p-8">
              <!-- Logo -->
              <div v-if="member.logo" class="flex justify-center mb-6">
                <div class="w-48 h-48 flex items-center justify-center bg-gray-50 rounded-xl p-6">
                  <img
                    :src="getImageUrl(member.logo)"
                    :alt="member.name"
                    class="max-w-full max-h-full object-contain"
                  />
                </div>
              </div>

              <!-- Name & Type -->
              <div class="text-center mb-6">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">
                  {{ member.name }}
                </h2>
                <p v-if="member.type" class="text-lg text-gray-500 capitalize">
                  {{ member.type.replace('_', ' ') }}
                </p>
              </div>

              <!-- Description -->
              <div v-if="member.description" class="mb-6">
                <p class="text-gray-700 leading-relaxed">
                  {{ member.description }}
                </p>
              </div>

              <!-- Contact Information -->
              <div class="space-y-4">
                <!-- Location -->
                <div v-if="member.location" class="flex items-start gap-3">
                  <svg class="w-6 h-6 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-500">Location</p>
                    <p class="text-gray-900">{{ member.location }}</p>
                  </div>
                </div>

                <!-- Email -->
                <div v-if="member.email" class="flex items-start gap-3">
                  <svg class="w-6 h-6 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-500">Email</p>
                    <a :href="`mailto:${member.email}`" class="text-primary hover:underline">
                      {{ member.email }}
                    </a>
                  </div>
                </div>

                <!-- Phone -->
                <div v-if="member.phone" class="flex items-start gap-3">
                  <svg class="w-6 h-6 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-500">Phone</p>
                    <a :href="`tel:${member.phone}`" class="text-primary hover:underline">
                      {{ member.phone }}
                    </a>
                  </div>
                </div>

                <!-- Website -->
                <div v-if="member.website" class="flex items-start gap-3">
                  <svg class="w-6 h-6 text-primary flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-500">Website</p>
                    <a
                      :href="member.website"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="text-primary hover:underline inline-flex items-center gap-1"
                    >
                      <span>{{ formatUrl(member.website) }}</span>
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                      </svg>
                    </a>
                  </div>
                </div>
              </div>

              <!-- Action Button -->
              <div v-if="member.website" class="mt-8 flex justify-center">
                <a
                  :href="member.website"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="inline-flex items-center gap-2 px-8 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium shadow-lg hover:shadow-xl"
                >
                  <span>Visit Website</span>
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                  </svg>
                </a>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
interface Member {
  id: number
  name: string
  slug: string
  type?: string
  description?: string
  logo?: string
  website?: string
  email?: string
  phone?: string
  location?: string
}

interface Props {
  isOpen: boolean
  member: Member | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  close: []
}>()

const { getImageUrl } = useImageUrl()

const close = () => {
  emit('close')
}

const formatUrl = (url: string) => {
  if (!url) return ''
  return url.replace(/^https?:\/\//, '').replace(/\/$/, '')
}

// Close modal on Escape key
onMounted(() => {
  const handleEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.isOpen) {
      close()
    }
  }
  window.addEventListener('keydown', handleEscape)

  // Cleanup
  onUnmounted(() => {
    window.removeEventListener('keydown', handleEscape)
  })
})

// Prevent body scroll when modal is open
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
</script>
