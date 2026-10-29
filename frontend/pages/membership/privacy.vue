<template>
  <!-- The site's privacy notice for applicants and members (Membership → Settings) -->
  <section class="py-16 md:py-20" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4 max-w-3xl">
      <div v-if="notice" class="prose prose-lg max-w-none" v-html="notice.html" />
      <p v-else :style="{ color: 'var(--color-text-muted)' }">There is no privacy notice yet.</p>
    </div>
  </section>
</template>

<script setup lang="ts">
const { fetchPrivacyNotice } = useApi()
const { data: notice } = await useAsyncData('privacy-notice', () => fetchPrivacyNotice().catch(() => null))
if (!notice.value) throw createError({ statusCode: 404, statusMessage: 'Page not found', fatal: true })
useHead({ title: 'Privacy notice' })
</script>
