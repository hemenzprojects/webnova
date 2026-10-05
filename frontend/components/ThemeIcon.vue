<template>
  <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" :d="path" />
  </svg>
</template>

<script setup lang="ts">
// Outline icon by name; the set lives in utils/themeIcons.ts
const props = defineProps<{ name?: string }>()

const path = computed(() => THEME_ICONS[props.name || ''] || THEME_ICONS['star'])
</script>
