<template>
  <ComingSoonScreen v-if="comingSoon?.enabled" :settings="comingSoon" :branding="branding" />
  <!-- Member portal pages (/account) bring their own layout -->
  <NuxtPage v-else-if="route.meta.portal" />
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
const { fetchBranding, fetchComingSoon, fetchCustomCss } = useApi()
const { colorVars, updateFavicon } = useBranding()
const route = useRoute()

// Fetch branding settings
const { data: branding } = await useAsyncData('branding', () => fetchBranding())

// Site-wide title and description from Branding; pages with their own replace them
const siteDescription = computed(() =>
  branding.value?.meta_description || branding.value?.site_description || branding.value?.site_tagline || undefined
)
useSeoMeta({
  title: () => branding.value?.site_name || undefined,
  description: siteDescription,
  ogSiteName: () => branding.value?.site_name || undefined,
  ogTitle: () => branding.value?.site_name || undefined,
  ogDescription: siteDescription,
})

// Member portal (Membership plugin): whether the site has one, and who is signed in. Used by the
// header's "Member login" link; the portal's pages load it again through their middleware if needed.
const { load: loadMember } = useMember()
await useAsyncData('member-session', async () => {
  await loadMember()
  return true
})

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

// Branding colours go in the server-rendered HTML so the page never shows the theme's colours first.
// `html:root` outranks the theme plugin's `:root`, whatever order the two styles end up in.
useHead({
  style: [{
    key: 'branding-colors',
    innerHTML: computed(() => {
      const vars = Object.entries(colorVars(branding.value)).map(([k, v]) => `${k}: ${v};`).join(' ')
      return vars ? `html:root { ${vars} }` : ''
    }),
  }],
})

// Appearance → Custom CSS, last so it wins over the theme's and Branding's styles
const { data: customCss } = await useAsyncData('custom-css', () => fetchCustomCss().catch(() => null))
useHead({
  style: [{ key: 'custom-css', innerHTML: computed(() => customCss.value?.css || ''), tagPriority: 'low' }],
})

onMounted(() => updateFavicon(branding.value))
watch(branding, (newBranding) => updateFavicon(newBranding))
</script>
