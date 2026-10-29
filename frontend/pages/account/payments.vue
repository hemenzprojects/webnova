<template>
  <AccountShell title="Payments">
    <!-- This year's membership -->
    <section
      class="rounded-xl border p-6 mb-6"
      :class="paidThisYear ? 'bg-green-50 border-green-200 text-green-900' : 'bg-amber-50 border-amber-200 text-amber-900'"
    >
      <p class="font-semibold">
        {{ paidThisYear ? `Your membership is paid for ${member?.current_year}.` : `Your membership for ${member?.current_year} is not paid yet.` }}
      </p>
      <p class="text-sm mt-1">
        Membership runs from 1 January to 31 December: a payment made any day in a year covers that year.
        <template v-if="!paidThisYear"> Online renewal is coming soon; until then, please contact the secretariat to renew.</template>
      </p>
    </section>

    <section class="bg-white rounded-xl border border-gray-200">
      <h2 class="px-6 pt-6 pb-4 text-sm font-semibold uppercase tracking-wider text-gray-500">Payment history</h2>
      <p v-if="!payments.length" class="px-6 pb-6 text-gray-600">No payments recorded yet.</p>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs uppercase tracking-wider text-gray-500 border-b border-gray-100">
              <th class="px-6 py-2 font-medium">Date</th>
              <th class="px-6 py-2 font-medium">For</th>
              <th class="px-6 py-2 font-medium text-right">Amount</th>
              <th class="px-6 py-2 font-medium">Receipt</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in payments" :key="payment.receipt_number" class="border-b border-gray-100 last:border-0">
              <td class="px-6 py-3 whitespace-nowrap">{{ formatDate(payment.paid_at) }}</td>
              <td class="px-6 py-3">{{ payment.purpose }}, {{ payment.year }}</td>
              <td class="px-6 py-3 text-right whitespace-nowrap">{{ payment.currency }} {{ payment.amount.toFixed(2) }}</td>
              <td class="px-6 py-3 whitespace-nowrap">
                <NuxtLink :to="`/account/receipts/${encodeURIComponent(payment.receipt_number)}`" class="font-mono text-primary hover:underline">
                  {{ payment.receipt_number }}
                </NuxtLink>
                <a :href="`/api/v1/membership/account/payments/${encodeURIComponent(payment.receipt_number)}/pdf`" class="ml-3 text-primary hover:underline">PDF</a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </AccountShell>
</template>

<script setup lang="ts">
import type { MembershipReceipt } from '~/composables/useApi'

definePageMeta({ portal: true, middleware: 'member' })
useHead({ title: 'Payments' })

const { member } = useMember()
const { fetchMemberPayments } = useApi()
const { data } = await useAsyncData('member-payments', () => fetchMemberPayments().catch(() => null))
const payments = computed<MembershipReceipt[]>(() => data.value?.payments || [])
const paidThisYear = computed(() => !!member.value?.paid_year && member.value.paid_year >= member.value.current_year)
</script>
