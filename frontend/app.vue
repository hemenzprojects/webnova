<template>
  <div class="min-h-screen bg-white">
    <TheNavigation :branding="branding" />
    <main>
      <NuxtPage />
    </main>
    <footer class="bg-primary text-white py-12">
      <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-4 gap-8">
          <div>
            <h3 class="text-xl font-bold mb-4">WEBNOVA</h3>
            <p class="text-gray-300">
              Ghanaian Academic and Research Network - Connecting Education
            </p>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Quick Links</h4>
            <ul class="space-y-2 text-gray-300">
              <li><NuxtLink to="/about" class="hover:text-accent transition">About Us</NuxtLink></li>
              <li><NuxtLink to="/services" class="hover:text-accent transition">Services</NuxtLink></li>
              <li><NuxtLink to="/members" class="hover:text-accent transition">Members</NuxtLink></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Resources</h4>
            <ul class="space-y-2 text-gray-300">
              <li><NuxtLink to="/news" class="hover:text-accent transition">News</NuxtLink></li>
              <li><NuxtLink to="/events" class="hover:text-accent transition">Events</NuxtLink></li>
              <li><NuxtLink to="/contact" class="hover:text-accent transition">Contact</NuxtLink></li>
            </ul>
          </div>
          <div>
            <h4 class="font-semibold mb-4">Connect</h4>
            <div class="flex gap-4">
              <a href="#" class="w-10 h-10 rounded-full flex items-center justify-center hover:scale-110 transition" :style="{ backgroundColor: 'var(--color-accent)' }">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              </a>
              <a href="#" class="w-10 h-10 rounded-full flex items-center justify-center hover:scale-110 transition" :style="{ backgroundColor: 'var(--color-accent)' }">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
              </a>
            </div>
          </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
          <p>&copy; {{ new Date().getFullYear() }} WEBNOVA. All rights reserved.</p>
        </div>
      </div>
    </footer>
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