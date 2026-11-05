<template>
  <!-- Loading state -->
  <div v-if="loading" class="flex items-center space-x-4 py-2">
    <div v-for="n in 3" :key="n" class="h-4 w-16 rounded animate-pulse" :class="variant === 'dark' ? 'bg-white/30' : 'bg-gray-200'" />
  </div>

  <!-- Menu items -->
  <MenuItems
    v-else-if="menu && menu.items.length > 0"
    :items="menu.items"
    mode="desktop"
    :variant="variant"
  />

  <!-- Fallback to default links if no header menu exists -->
  <div v-else class="flex items-center" :class="variant === 'dark' ? 'space-x-1' : 'space-x-8'">
    <NuxtLink
      v-for="link in fallbackLinks"
      :key="link.to"
      :to="link.to"
      :class="variant === 'dark'
        ? 'px-4 py-2 font-medium text-white rounded-md hover:bg-white/10 transition'
        : 'text-gray-700 hover:text-primary transition'"
    >
      {{ link.label }}
    </NuxtLink>
  </div>
</template>

<script setup lang="ts">
import type { Menu } from '~/composables/useMenu'

withDefaults(defineProps<{
  menu: Menu | null
  loading?: boolean
  // 'dark' is for menus sitting on a coloured strip
  variant?: 'light' | 'dark'
}>(), {
  loading: false,
  variant: 'light',
})

const fallbackLinks = [
  { label: 'Home', to: '/' },
  { label: 'About', to: '/about' },
  { label: 'Services', to: '/services' },
  { label: 'News', to: '/news' },
  { label: 'Members', to: '/members' },
  { label: 'Contact', to: '/contact' },
]
</script>
