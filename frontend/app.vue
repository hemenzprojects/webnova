<template>
  <ComingSoonScreen v-if="comingSoon?.enabled" :settings="comingSoon" :branding="branding" />
  <div v-else class="min-h-screen bg-white">
    <div v-if="comingSoon?.preview && !route.path.startsWith('/elementor')" class="bg-amber-400 text-amber-950 text-sm text-center px-4 py-2">
      {{ comingSoon.mode === 'maintenance' ? 'Maintenance' : 'Coming soon' }} mode is on: visitors see the
      <a href="/?coming-soon-preview=1" class="underline font-semibold">{{ comingSoon.mode === 'maintenance' ? 'maintenance' : 'coming soon' }} screen</a>.
      You see the real site because you are signed in.
      <a href="/admin/coming-soon" class="underline font-semibold">Settings</a>
    </div>
    <TheNavigation :branding="branding" />
    <main>
      <NuxtPage />
    </main>
    <TheFooter :branding="branding" />
  </div>
</template>

<script setup>
const { fetchBranding, fetchComingSoon } = useApi()
const { applyColorTheme, updateFavicon } = useBranding()
const route = useRoute()

// Fetch branding settings
const { data: branding } = await useAsyncData('branding', () => fetchBranding())

// Coming Soon plugin: hides the site from visitors, not from signed-in admins
const { data: comingSoon } = await useAsyncData('coming-soon', () =>
  fetchComingSoon(route.query['coming-soon-preview'] === '1').catch(() => null)
)

if (comingSoon.value?.enabled) {
  useHead({
    title: branding.value?.site_name || undefined,
    meta: [{ name: 'robots', content: 'noindex' }],
  })
  // Maintenance: 503 tells search engines the site is coming back
  if (process.server && comingSoon.value.mode === 'maintenance') {
    const event = useRequestEvent()
    if (event) {
      setResponseStatus(event, 503)
      event.node.res.setHeader('Retry-After', '3600')
    }
  }
}

// Apply branding theme on mount
onMounted(() => {
  if (branding.value) {
    applyColorTheme(branding.value)
    updateFavicon(branding.value)
  }
})

// Watch for branding changes
watch(branding, (newBranding) => {
  if (newBranding) {
    applyColorTheme(newBranding)
    updateFavicon(newBranding)
  }
})
</script>
