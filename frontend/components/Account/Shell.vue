<template>
  <!-- Member portal layout: its own top bar and side menu, without the public site's header and footer -->
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <header class="bg-primary text-white sticky top-0 z-40 shadow-sm">
      <div class="flex items-center justify-between h-16 px-4 lg:px-6">
        <div class="flex items-center gap-3 min-w-0">
          <button
            type="button"
            class="lg:hidden -ml-1 p-2 rounded-md hover:bg-white/10"
            :aria-expanded="menuOpen"
            aria-label="Menu"
            @click="menuOpen = !menuOpen"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" :d="menuOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'" /></svg>
          </button>
          <NuxtLink to="/account" class="flex items-center gap-3 min-w-0">
            <span v-if="logoUrl" class="h-9 px-1.5 rounded bg-white flex items-center shrink-0"><img :src="logoUrl" :alt="siteName" class="h-7 w-auto object-contain"></span>
            <span class="font-semibold truncate" style="font-family: var(--font-heading)">{{ siteName }}</span>
            <span class="hidden sm:inline text-xs uppercase tracking-wider opacity-70 border-l border-white/30 pl-3">Member portal</span>
          </NuxtLink>
        </div>

        <div v-if="member" class="flex items-center gap-3">
          <div class="text-right leading-tight hidden sm:block">
            <div class="text-sm font-semibold">{{ member.name }}</div>
            <div class="text-xs opacity-75 font-mono">{{ member.member_number }}</div>
          </div>
          <span class="w-9 h-9 rounded-full bg-white/15 flex items-center justify-center text-sm font-semibold" aria-hidden="true">{{ initials }}</span>
        </div>
      </div>
    </header>

    <div class="flex flex-1">
      <!-- Side menu: always shown on large screens, a drawer on phones -->
      <div v-if="menuOpen" class="fixed inset-0 top-16 z-30 bg-black/30 lg:hidden" @click="menuOpen = false" />
      <aside
        :class="[
          'fixed lg:sticky top-16 z-30 h-[calc(100vh-4rem)] w-64 shrink-0 bg-white border-r border-gray-200 flex flex-col transition-transform',
          menuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        ]"
      >
        <nav class="flex-1 overflow-y-auto p-3 space-y-1" aria-label="Member portal">
          <NuxtLink
            v-for="item in items"
            :key="item.to"
            :to="item.to"
            :class="[
              'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition',
              isCurrent(item.to) ? 'bg-primary/10 text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100',
            ]"
            @click="menuOpen = false"
          >
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" /></svg>
            {{ item.label }}
          </NuxtLink>
        </nav>
        <div class="p-3 border-t border-gray-200 space-y-1">
          <NuxtLink to="/" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-700 hover:bg-gray-100">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.website" /></svg>
            Visit the website
          </NuxtLink>
          <button type="button" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-700 hover:bg-gray-100" @click="signOut">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="ICONS.logout" /></svg>
            Log out
          </button>
        </div>
      </aside>

      <main class="flex-1 min-w-0 px-4 py-6 lg:px-8 lg:py-8">
        <div class="max-w-5xl mx-auto">
          <h1 v-if="title" class="text-2xl md:text-3xl font-bold text-gray-900 mb-6" style="font-family: var(--font-heading)">{{ title }}</h1>
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ title?: string }>()

const ICONS = {
  dashboard: 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
  card: 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z',
  lock: 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z',
  pages: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
  website: 'M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418',
  logout: 'M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9',
}

// The portal's menu. Later features add their items here (profile, documents, certificate…).
const items = [
  { to: '/account', label: 'Dashboard', icon: ICONS.dashboard },
  { to: '/account/membership', label: 'Membership details', icon: ICONS.card },
  { to: '/account/pages', label: "Members' area", icon: ICONS.pages },
  { to: '/account/password', label: 'Change password', icon: ICONS.lock },
]

const route = useRoute()
const isCurrent = (to: string) => (to === '/account' ? route.path === '/account' || route.path === '/account/' : route.path.startsWith(to))
const menuOpen = ref(false)

const { member, logout } = useMember()
const initials = computed(() =>
  (member.value?.name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]!.toUpperCase()).join('')
)

const { data: branding } = useNuxtData('branding')
const siteName = computed(() => (branding.value as any)?.site_name || 'Member portal')
const { getImageUrl } = useImageUrl()
const logoUrl = computed(() => getImageUrl((branding.value as any)?.logo))

useHead({ meta: [{ name: 'robots', content: 'noindex' }] })

const signOut = async () => {
  await logout()
  await navigateTo('/account/login')
}
</script>
