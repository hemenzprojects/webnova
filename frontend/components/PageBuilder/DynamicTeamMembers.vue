<template>
  <section class="py-20">
    <div class="container mx-auto px-4">
      <!-- Section Header -->
      <div class="text-center mb-16">
        <p class="text-primary font-medium text-sm uppercase tracking-wider mb-4">
          TEAM MEMBER
        </p>
        <h2 v-if="data.heading" class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
          {{ data.heading }}
        </h2>
        <p v-if="data.subheading" class="text-gray-600 max-w-3xl mx-auto text-lg">
          {{ data.subheading }}
        </p>
      </div>

      <!-- Grid Layout -->
      <div v-if="data.layout === 'grid'" class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-7xl mx-auto" :class="{
        'lg:grid-cols-2': data.columns === '2',
        'lg:grid-cols-3': data.columns === '3',
        'lg:grid-cols-4': data.columns === '4'
      }">
        <div
          v-for="member in data.items"
          :key="member.id"
          class="group"
        >
          <!-- Photo Container -->
          <div v-if="data.showPhoto" class="relative mb-6 overflow-hidden rounded-2xl">
            <img
              v-if="member.photo"
              :src="getImageUrl(member.photo)"
              :alt="member.name"
              class="w-full h-96 object-cover transition-transform duration-500 group-hover:scale-105"
              loading="lazy"
            />
            <div v-else class="w-full h-96 bg-gradient-to-br from-primary/10 to-accent/10 flex items-center justify-center">
              <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>

          <!-- Member Info Card -->
          <div class="bg-white rounded-xl p-6 shadow-sm text-center">
            <h3 class="text-2xl font-bold text-gray-900 mb-2">
              {{ member.name }}
            </h3>
            <p class="text-gray-600 text-base mb-4">
              {{ member.role }}
            </p>

            <p v-if="data.showBio && member.bio" class="text-gray-600 text-sm leading-relaxed mb-4">
              {{ member.bio }}
            </p>

            <div v-if="data.showEmail && member.email" class="text-sm text-gray-500 mb-2">
              <a :href="`mailto:${member.email}`" class="hover:text-primary transition">
                {{ member.email }}
              </a>
            </div>

            <!-- Social Links -->
            <div v-if="data.showSocialLinks && member.social_links" class="flex justify-center gap-3 mt-4">
              <a
                v-for="(url, platform) in member.social_links"
                :key="platform"
                :href="url"
                target="_blank"
                rel="noopener noreferrer"
                class="w-10 h-10 bg-gray-100 hover:bg-primary hover:text-white rounded-full flex items-center justify-center transition-all duration-300"
                :aria-label="`${member.name} on ${platform}`"
              >
                <svg v-if="platform.toLowerCase().includes('linkedin')" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
                <svg v-else-if="platform.toLowerCase().includes('twitter')" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                </svg>
                <svg v-else-if="platform.toLowerCase().includes('facebook')" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- List Layout -->
      <div v-else-if="data.layout === 'list'" class="max-w-4xl mx-auto space-y-6">
        <div
          v-for="member in data.items"
          :key="member.id"
          class="flex flex-col md:flex-row gap-6 bg-white border border-gray-200 rounded-xl hover:shadow-lg transition-all duration-300 p-6 group"
        >
          <div v-if="data.showPhoto" class="flex-shrink-0">
            <img
              v-if="member.photo"
              :src="getImageUrl(member.photo)"
              :alt="member.name"
              class="w-full md:w-48 h-48 object-cover rounded-lg"
              loading="lazy"
            />
            <div v-else class="w-full md:w-48 h-48 bg-gradient-to-br from-primary/10 to-accent/10 rounded-lg flex items-center justify-center">
              <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
          </div>

          <div class="flex-1">
            <h3 class="text-2xl font-bold text-gray-900 mb-2">
              {{ member.name }}
            </h3>
            <p class="text-primary font-medium mb-3">
              {{ member.role }}
            </p>

            <p v-if="data.showBio && member.bio" class="text-gray-600 mb-4">
              {{ member.bio }}
            </p>

            <div v-if="data.showEmail && member.email" class="text-sm text-gray-500 mb-2">
              <a :href="`mailto:${member.email}`" class="hover:text-primary transition">
                {{ member.email }}
              </a>
            </div>

            <div v-if="data.showSocialLinks && member.social_links" class="flex gap-3 mt-4">
              <a
                v-for="(url, platform) in member.social_links"
                :key="platform"
                :href="url"
                target="_blank"
                rel="noopener noreferrer"
                class="w-10 h-10 bg-gray-100 hover:bg-primary hover:text-white rounded-full flex items-center justify-center transition-all duration-300"
                :aria-label="`${member.name} on ${platform}`"
              >
                <svg v-if="platform.toLowerCase().includes('linkedin')" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
                <svg v-else-if="platform.toLowerCase().includes('twitter')" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                </svg>
                <svg v-else-if="platform.toLowerCase().includes('facebook')" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const props = defineProps<{
  data: {
    heading?: string
    subheading?: string
    items: any[]
    limit: string | number
    layout: 'grid' | 'list'
    columns: string
    showPhoto: boolean
    showBio: boolean
    showEmail: boolean
    showSocialLinks: boolean
  }
  blockId: string
}>()

const { getImageUrl } = useImageUrl()
</script>