<template>
  <!-- Shown in place of a members-only page the visitor may not see (Membership plugin) -->
  <section class="py-16 md:py-24" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4 max-w-xl">
      <div
        class="text-center px-6 py-12"
        :style="{ backgroundColor: 'var(--color-surface-muted, #f3f4f6)', borderRadius: 'var(--radius-card, 12px)' }"
      >
        <svg class="w-14 h-14 mx-auto mb-4 text-primary" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
        </svg>
        <p class="text-sm font-semibold uppercase tracking-wider text-primary mb-2">Members only</p>
        <h1 class="text-2xl md:text-3xl mb-3" :style="{ fontFamily: 'var(--font-heading)', color: 'var(--color-text)' }">{{ title }}</h1>
        <p class="mb-8" :style="{ color: 'var(--color-text-muted)' }">{{ message }}</p>

        <div v-if="reason === 'login'" class="flex flex-wrap justify-center gap-3">
          <NuxtLink
            :to="{ path: '/account/login', query: { redirect: route.fullPath } }"
            class="px-6 py-2.5 text-white bg-primary font-semibold"
            :style="{ borderRadius: 'var(--radius-button, 8px)' }"
          >
            Log in
          </NuxtLink>
          <NuxtLink
            to="/membership"
            class="px-6 py-2.5 border font-semibold"
            :style="{ borderRadius: 'var(--radius-button, 8px)', borderColor: 'var(--color-primary)', color: 'var(--color-primary)' }"
          >
            Become a member
          </NuxtLink>
        </div>
        <NuxtLink
          v-else-if="reason === 'inactive'"
          to="/account"
          class="inline-block px-6 py-2.5 text-white bg-primary font-semibold"
          :style="{ borderRadius: 'var(--radius-button, 8px)' }"
        >
          Go to my account
        </NuxtLink>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
// login: not signed in · inactive: signed in, membership not active · unavailable: the site has no member portal
const props = defineProps<{ reason: 'login' | 'inactive' | 'unavailable' | string; title: string }>()
const route = useRoute()

const message = computed(() => {
  if (props.reason === 'login') return 'This page is for members. Please log in to read it.'
  if (props.reason === 'inactive') return 'Your membership is not active at the moment, so this page is closed to you. Please contact the secretariat.'
  return 'This page is not available.'
})
</script>
