<template>
  <!-- A membership payment receipt, laid out to print on one page (Print / Save as PDF) -->
  <article class="receipt bg-white text-gray-900 rounded-xl border border-gray-200 p-6 sm:p-10">
    <header class="flex flex-wrap items-start justify-between gap-6 pb-6 border-b border-gray-200">
      <div class="flex items-center gap-3 min-w-0">
        <img v-if="logoUrl" :src="logoUrl" :alt="siteName" class="h-12 w-auto object-contain">
        <span class="text-lg font-semibold">{{ siteName }}</span>
      </div>
      <div class="text-right">
        <p class="text-xs uppercase tracking-wider text-gray-500">Receipt</p>
        <p class="font-mono text-lg font-semibold">{{ receipt.receipt_number }}</p>
        <p class="text-sm text-gray-600">{{ formatDate(receipt.paid_at) }}</p>
      </div>
    </header>

    <div class="grid gap-6 sm:grid-cols-2 py-6 border-b border-gray-200">
      <div>
        <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Received from</p>
        <p class="font-medium">{{ receipt.name || '—' }}</p>
        <p v-if="receipt.email" class="text-sm text-gray-600 break-all">{{ receipt.email }}</p>
      </div>
      <div v-if="receipt.member_id" class="sm:text-right">
        <p class="text-xs uppercase tracking-wider text-gray-500 mb-1">Member ID</p>
        <p class="font-mono text-lg font-semibold">{{ receipt.member_id }}</p>
      </div>
    </div>

    <table class="w-full text-sm my-6">
      <thead>
        <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
          <th class="pb-2 font-medium">Description</th>
          <th class="pb-2 font-medium text-right">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr class="border-t border-gray-100">
          <td class="py-3">
            {{ receipt.purpose }}, {{ receipt.year }}
            <span v-if="receipt.membership_type" class="block text-gray-500">{{ receipt.membership_type }} membership</span>
          </td>
          <td class="py-3 text-right whitespace-nowrap">{{ money }}</td>
        </tr>
        <tr class="border-t-2 border-gray-900 font-semibold">
          <td class="pt-3">Total paid</td>
          <td class="pt-3 text-right whitespace-nowrap">{{ money }}</td>
        </tr>
      </tbody>
    </table>

    <dl class="grid gap-x-6 gap-y-2 sm:grid-cols-2 text-sm">
      <div>
        <dt class="text-gray-500">Payment method</dt>
        <dd>{{ receipt.method }}</dd>
      </div>
      <div v-if="receipt.reference">
        <dt class="text-gray-500">Payment reference</dt>
        <dd class="font-mono break-all">{{ receipt.reference }}</dd>
      </div>
      <div v-if="receipt.application_reference">
        <dt class="text-gray-500">Application reference</dt>
        <dd class="font-mono">{{ receipt.application_reference }}</dd>
      </div>
      <div>
        <dt class="text-gray-500">Membership year</dt>
        <dd>1 January to 31 December {{ receipt.year }}</dd>
      </div>
    </dl>

    <p class="mt-8 pt-6 border-t border-gray-200 text-xs text-gray-500">
      Thank you for your payment. This receipt was issued by {{ siteName }}'s membership system and is valid without a signature.
    </p>
  </article>
</template>

<script setup lang="ts">
import type { MembershipReceipt } from '~/composables/useApi'

const props = defineProps<{ receipt: MembershipReceipt }>()

const money = computed(() => `${props.receipt.currency} ${props.receipt.amount.toFixed(2)}`)

const { data: branding } = useNuxtData('branding')
const siteName = computed(() => (branding.value as any)?.site_name || '')
const { getImageUrl } = useImageUrl()
const logoUrl = computed(() => getImageUrl((branding.value as any)?.logo))
</script>

<style scoped>
@media print {
  .receipt { border: 0; padding: 0; border-radius: 0; }
}
</style>
