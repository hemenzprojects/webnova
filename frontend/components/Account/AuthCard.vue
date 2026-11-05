<template>
  <!-- Member login, forgot password and set password: a card on its own, without the public site's header -->
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-4 py-12">
    <NuxtLink to="/" class="mb-8 flex flex-col items-center gap-3">
      <img v-if="logoUrl" :src="logoUrl" :alt="siteName" class="h-16 w-auto object-contain">
      <span class="text-lg font-semibold text-gray-900" style="font-family: var(--font-heading)">{{ siteName }}</span>
    </NuxtLink>

    <div class="w-full max-w-md bg-white shadow-sm border border-gray-200 rounded-2xl p-6 sm:p-8">
      <h1 class="text-2xl font-bold text-gray-900 mb-2" style="font-family: var(--font-heading)">{{ title }}</h1>
      <p v-if="subtitle" class="text-gray-600 mb-6">{{ subtitle }}</p>
      <slot />
    </div>

    <div class="mt-6 text-sm text-gray-600 text-center space-y-2">
      <slot name="footer" />
      <p><NuxtLink to="/" class="hover:text-primary">&larr; Back to the website</NuxtLink></p>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ title: string; subtitle?: string }>()

const { data: branding } = useNuxtData('branding')
const siteName = computed(() => (branding.value as any)?.site_name || '')
const { getImageUrl } = useImageUrl()
const logoUrl = computed(() => getImageUrl((branding.value as any)?.logo))

useHead({ meta: [{ name: 'robots', content: 'noindex' }] })
</script>
