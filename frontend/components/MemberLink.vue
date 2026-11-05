<template>
  <!-- Header link to the member portal: "Member login", or "My account" once signed in -->
  <NuxtLink to="/account" class="inline-flex items-center gap-2 font-medium whitespace-nowrap transition">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
    </svg>
    {{ member ? 'My account' : 'Member login' }}
  </NuxtLink>
</template>

<script setup lang="ts">
import type { Member } from '~/composables/useApi'

defineProps<{ member: Member | null }>()
</script>
