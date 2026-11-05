<template>
  <span
    v-if="member"
    :class="['inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-sm font-medium', member.is_active ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-900']"
  >
    <span :class="['w-1.5 h-1.5 rounded-full', member.is_active ? 'bg-green-600' : 'bg-amber-600']" aria-hidden="true" />
    {{ member.status_label }}
  </span>
</template>

<script setup lang="ts">
import type { Member } from '~/composables/useApi'

defineProps<{ member: Member | null }>()
</script>
