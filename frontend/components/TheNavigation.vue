<template>
  <!-- The design is chosen in the admin under Customize → Header Settings -->
  <header
    :class="['shadow-sm z-50', header.sticky ? 'sticky top-0' : 'relative']"
    :style="{ backgroundColor: 'var(--color-header-bg, #ffffff)' }"
  >
    <!-- Top bar design: coloured strip with social icons and contact details -->
    <div
      v-if="header.layout === 'top_bar' && (header.social.length || header.phone || header.email)"
      class="hidden md:block text-white text-sm"
      :style="{ backgroundColor: 'var(--color-primary, #0A1E3E)' }"
    >
      <div class="container mx-auto px-4 flex items-center justify-between h-11">
        <div class="flex items-center h-full">
          <a
            v-for="link in header.social"
            :key="link.platform"
            :href="link.url"
            :aria-label="link.label"
            :title="link.label"
            target="_blank"
            rel="noopener noreferrer"
            class="h-full px-4 flex items-center border-l border-white/20 last:border-r hover:bg-white/10 transition"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path :d="link.icon" /></svg>
          </a>
        </div>
        <div class="flex items-center gap-8">
          <a v-if="header.phone" :href="`tel:${header.phone}`" class="flex items-center gap-2 hover:opacity-80 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons.phone" /></svg>
            <span>Call us: {{ header.phone }}</span>
          </a>
          <a v-if="header.email" :href="`mailto:${header.email}`" class="flex items-center gap-2 hover:opacity-80 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons.email" /></svg>
            <span>Email: {{ header.email }}</span>
          </a>
        </div>
      </div>
    </div>

    <nav class="container mx-auto px-4">
      <div class="flex items-center justify-between py-4">
        <!-- Logo -->
        <NuxtLink to="/" class="flex items-center space-x-2">
          <div v-if="logoUrl" class="h-12 flex items-center">
            <img :src="logoUrl" :alt="siteName" class="h-full w-auto object-contain" />
          </div>
          <div v-else class="w-10 h-10 bg-gradient-to-br from-primary to-accent rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-xl">{{ siteName.charAt(0) }}</span>
          </div>
          <span v-if="header.show_site_name !== false || !logoUrl" class="text-2xl font-bold text-gray-900">{{ siteName }}<span class="text-accent">.</span></span>
        </NuxtLink>

        <!-- Contact bar design: phone, email and button beside the logo -->
        <div v-if="header.layout === 'info_bar'" class="hidden md:flex items-center">
          <a v-if="header.phone" :href="`tel:${header.phone}`" class="flex items-center gap-3 px-6 group">
            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.phone" /></svg>
            <span>
              <span class="block text-xs uppercase tracking-wide text-gray-500">Telephone</span>
              <span class="block font-semibold text-gray-900 group-hover:text-primary transition">{{ header.phone }}</span>
            </span>
          </a>
          <a v-if="header.email" :href="`mailto:${header.email}`" class="flex items-center gap-3 px-6 group" :class="{ 'border-l border-gray-200': header.phone }">
            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.email" /></svg>
            <span>
              <span class="block text-xs uppercase tracking-wide text-gray-500">Email</span>
              <span class="block font-semibold text-gray-900 group-hover:text-primary transition">{{ header.email }}</span>
            </span>
          </a>
          <HeaderCta
            v-for="b in buttons"
            :key="b.variant"
            :button="b.button"
            :variant="b.variant"
            :accent-color="branding?.accent_color"
            class="ml-2 px-6 py-3 font-semibold uppercase tracking-wide text-sm rounded hover:opacity-90 transition"
          />
        </div>

        <!-- Simple and top bar designs: menu and button beside the logo -->
        <div v-else class="hidden md:flex items-center gap-4">
          <HeaderMenu :menu="headerMenu" :loading="menuLoading" :style="navStyle" />
          <HeaderCta
            v-for="b in buttons"
            :key="b.variant"
            :button="b.button"
            :variant="b.variant"
            :accent-color="branding?.accent_color"
            :class="['px-5 py-2 font-semibold hover:opacity-90 transition whitespace-nowrap', header.layout === 'top_bar' ? 'rounded-full' : 'rounded-lg']"
          />
        </div>

        <!-- Mobile Menu Button -->
        <button
          @click="mobileMenuOpen = !mobileMenuOpen"
          class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition"
          aria-label="Toggle menu"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              v-if="!mobileMenuOpen"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
            <path
              v-else
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Contact bar design: the menu in a coloured strip below -->
      <div
        v-if="header.layout === 'info_bar'"
        class="hidden md:block px-2 py-1"
        :style="{ backgroundColor: 'var(--color-primary, #0A1E3E)' }"
      >
        <HeaderMenu :menu="headerMenu" :loading="menuLoading" variant="dark" :style="navStyle" />
      </div>

      <!-- Mobile Menu -->
      <Transition
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div
          v-if="mobileMenuOpen"
          class="md:hidden py-4 border-t border-gray-200"
        >
          <!-- Loading state -->
          <div v-if="menuLoading" class="flex flex-col space-y-3">
            <div class="h-4 w-24 bg-gray-200 rounded animate-pulse" />
            <div class="h-4 w-32 bg-gray-200 rounded animate-pulse" />
            <div class="h-4 w-28 bg-gray-200 rounded animate-pulse" />
          </div>

          <!-- Menu items -->
          <MenuItems
            v-else-if="headerMenu && headerMenu.items.length > 0"
            :items="headerMenu.items"
            mode="mobile"
          />

          <!-- Fallback to hardcoded menu if no dynamic menu exists -->
          <div v-else class="flex flex-col space-y-4">
            <NuxtLink to="/" class="text-gray-700 hover:text-primary transition">Home</NuxtLink>
            <NuxtLink to="/about" class="text-gray-700 hover:text-primary transition">About</NuxtLink>
            <NuxtLink to="/services" class="text-gray-700 hover:text-primary transition">Services</NuxtLink>
            <NuxtLink to="/news" class="text-gray-700 hover:text-primary transition">News</NuxtLink>
            <NuxtLink to="/members" class="text-gray-700 hover:text-primary transition">Members</NuxtLink>
            <NuxtLink to="/contact" class="text-gray-700 hover:text-primary transition">Contact</NuxtLink>
          </div>

          <!-- Contact details and button (designs that show them on desktop) -->
          <div v-if="buttons.length || (header.layout !== 'classic' && (header.phone || header.email))" class="mt-4 pt-4 border-t border-gray-200 space-y-3">
            <template v-if="header.layout !== 'classic'">
              <a v-if="header.phone" :href="`tel:${header.phone}`" class="flex items-center gap-3 px-4 text-gray-700">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons.phone" /></svg>
                <span>{{ header.phone }}</span>
              </a>
              <a v-if="header.email" :href="`mailto:${header.email}`" class="flex items-center gap-3 px-4 text-gray-700">
                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icons.email" /></svg>
                <span>{{ header.email }}</span>
              </a>
            </template>
            <HeaderCta
              v-for="b in buttons"
              :key="b.variant"
              :button="b.button"
              :variant="b.variant"
              :accent-color="branding?.accent_color"
              class="block text-center mx-4 px-6 py-3 font-semibold rounded-lg"
            />
          </div>
        </div>
      </Transition>
    </nav>
  </header>
</template>

<script setup lang="ts">
import type { Menu } from '~/composables/useMenu'

interface HeaderConfig {
  layout: 'classic' | 'info_bar' | 'top_bar'
  sticky: boolean
  show_site_name?: boolean
  cta: { text: string; url: string; new_tab: boolean } | null
  cta2?: { text: string; url: string; new_tab: boolean } | null
  phone: string | null
  email: string | null
  social: { platform: string; label: string; icon: string; url: string }[]
}

const props = defineProps({
  branding: {
    type: Object,
    default: null
  }
})

const { fetchHeader } = useApi()
const mobileMenuOpen = ref(false)

// Used until a design is saved in the admin, or if the settings cannot be loaded
const defaultHeader: HeaderConfig = { layout: 'classic', sticky: true, cta: null, phone: null, email: null, social: [] }

const { data: headerData } = await useAsyncData('header', async () => {
  try {
    return await fetchHeader()
  } catch (error) {
    console.error('Failed to fetch header settings:', error)
    return defaultHeader
  }
})

const header = computed<HeaderConfig>(() => (headerData.value as HeaderConfig) || defaultHeader)

const icons = {
  phone: 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
  email: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
}

// Menu lettering comes from the theme (e.g. Edubright uses spaced capitals)
const navStyle = { textTransform: 'var(--nav-transform, none)', letterSpacing: 'var(--nav-letter-spacing, normal)' } as const

// Call-to-action buttons: the main one filled, the optional second one outlined
const buttons = computed(() => [
  ...(header.value.cta ? [{ button: header.value.cta, variant: 'solid' as const }] : []),
  ...(header.value.cta2 ? [{ button: header.value.cta2, variant: 'outline' as const }] : []),
])

// Fetch the header menu dynamically
const { fetchMenuByLocation } = useMenu()
const headerMenu = ref<Menu | null>(null)
const menuLoading = ref(true)
const menuError = ref(null)

// Fetch menu on component mount
onMounted(async () => {
  try {
    menuLoading.value = true
    headerMenu.value = await fetchMenuByLocation('header')
  } catch (error) {
    menuError.value = error
    console.error('Failed to load header menu:', error)
  } finally {
    menuLoading.value = false
  }
})

// Computed properties for branding
const siteName = computed(() => props.branding?.site_name || 'WEBNOVA')
const { getImageUrl } = useImageUrl()
const logoUrl = computed(() => getImageUrl(props.branding?.logo))
</script>
