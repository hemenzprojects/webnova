<template>
  <p v-if="!pages.length" class="text-sm text-gray-600">Nothing here yet. Pages the Association shares with members, such as meeting minutes and reports, will be listed here.</p>
  <ul v-else class="divide-y divide-gray-100">
    <li v-for="page in shown" :key="page.slug">
      <NuxtLink :to="`/${page.slug}`" class="flex items-start justify-between gap-4 py-3 group">
        <span>
          <span class="block font-medium text-gray-900 group-hover:text-primary">{{ page.title }}</span>
          <span v-if="page.excerpt" class="block text-sm text-gray-600 mt-0.5">{{ page.excerpt }}</span>
        </span>
        <span class="text-gray-400 group-hover:text-primary" aria-hidden="true">&rarr;</span>
      </NuxtLink>
    </li>
  </ul>
</template>

<script setup lang="ts">
const props = defineProps<{ pages: { title: string; slug: string; excerpt: string | null }[]; limit?: number }>()
const shown = computed(() => (props.limit ? props.pages.slice(0, props.limit) : props.pages))
</script>
