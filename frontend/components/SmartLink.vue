<template>
  <a v-if="isExternal" :href="to" :target="newTab ? '_blank' : undefined" :rel="newTab ? 'noopener noreferrer' : undefined">
    <slot />
  </a>
  <NuxtLink v-else :to="to || '/'">
    <slot />
  </NuxtLink>
</template>

<script setup lang="ts">
// Link for editor-entered URLs: site paths use NuxtLink, anything else a plain <a>
const props = defineProps<{ to?: string; newTab?: boolean }>()

const isExternal = computed(() => /^(https?:|mailto:|tel:|#)/.test(props.to || ''))
</script>
