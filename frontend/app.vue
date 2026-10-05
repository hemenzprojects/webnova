<template>
  <div class="min-h-screen bg-white">
    <TheNavigation :branding="branding" />
    <main>
      <NuxtPage />
    </main>
    <TheFooter :branding="branding" />
  </div>
</template>

<script setup>
const { fetchBranding } = useApi()
const { applyColorTheme, updateFavicon } = useBranding()

// Fetch branding settings
const { data: branding } = await useAsyncData('branding', () => fetchBranding())

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