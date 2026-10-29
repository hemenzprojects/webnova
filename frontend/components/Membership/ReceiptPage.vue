<template>
  <!-- A receipt on its own page, without the site's header and footer, with Print / Save as PDF -->
  <div class="min-h-screen bg-gray-100 py-8 px-4 print:bg-white print:p-0">
    <div class="max-w-3xl mx-auto">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4 print:hidden">
        <NuxtLink :to="backTo" class="text-sm text-primary hover:underline">&larr; {{ backLabel }}</NuxtLink>
        <div v-if="receipt" class="flex flex-wrap gap-2">
          <button type="button" class="px-4 py-2 rounded-lg border border-primary text-primary text-sm font-medium" @click="print">Print</button>
          <a :href="pdfUrl" class="px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium">Download PDF</a>
        </div>
      </div>
      <MembershipReceipt v-if="receipt" :receipt="receipt" />
      <div v-else class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-600">
        This receipt could not be found.
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { MembershipReceipt } from '~/composables/useApi'

defineProps<{ receipt: MembershipReceipt | null; backTo: string; backLabel: string; pdfUrl: string }>()

const print = () => window.print()
useHead({ meta: [{ name: 'robots', content: 'noindex' }] })
</script>
