<template>
  <AccountShell title="Payments">
    <!-- Back from the payment page with ?renewal= -->
    <div v-if="returned" class="rounded-xl border p-4 mb-6 text-sm" :class="returned.payment_status === 'paid' ? 'bg-green-50 border-green-200 text-green-900' : 'bg-amber-50 border-amber-200 text-amber-900'" role="status">
      <template v-if="returned.payment_status === 'paid'">
        <strong>Thank you: your {{ returned.year }} membership is paid.</strong>
        <template v-if="returned.receipt"> Your receipt {{ returned.receipt.receipt_number }} is below and on its way to your email.</template>
      </template>
      <template v-else-if="returned.payment_status === 'failed'"><strong>The payment did not go through.</strong> No money was taken; you can try again.</template>
      <template v-else>
        <strong>We have not received your payment yet.</strong> If you have just paid, it can take a moment.
        <button type="button" class="underline font-medium ml-1" @click="checkReturn">Check again</button>
      </template>
    </div>

    <!-- Where the member stands with this year's fee -->
    <section v-if="standing" class="rounded-xl border p-6 mb-6" :class="tone.box">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
          <p class="text-xs uppercase tracking-wider font-semibold opacity-80">Membership fee</p>
          <p class="text-lg font-semibold mt-1">{{ standing.label }}</p>
          <p class="text-sm mt-1">{{ standing.message }}</p>
        </div>
        <div v-if="standing.renew_year" class="text-right">
          <p class="text-sm">{{ standing.renew_year }} fee</p>
          <p class="text-2xl font-semibold">{{ standing.currency }} {{ standing.amount.toFixed(2) }}</p>
          <button
            type="button"
            :disabled="renewing"
            class="mt-2 px-5 py-2.5 rounded-lg bg-primary text-white font-medium disabled:opacity-60"
            @click="renew"
          >
            {{ renewing ? 'Please wait…' : `Renew for ${standing.renew_year}` }}
          </button>
        </div>
      </div>
      <p v-if="renewError" class="mt-3 text-sm text-red-700" role="alert">{{ renewError }}</p>
      <p class="text-xs mt-4 opacity-80">
        Membership runs from 1 January to 31 December. A payment made any day in a year pays for that year.
      </p>
    </section>

    <!-- Without the Renewals feature: just this year's status -->
    <section
      v-else
      class="rounded-xl border p-6 mb-6"
      :class="paidThisYear ? 'bg-green-50 border-green-200 text-green-900' : 'bg-amber-50 border-amber-200 text-amber-900'"
    >
      <p class="font-semibold">
        {{ paidThisYear ? `Your membership is paid for ${member?.current_year}.` : `Your membership for ${member?.current_year} is not paid yet.` }}
      </p>
      <p class="text-sm mt-1">
        Membership runs from 1 January to 31 December: a payment made any day in a year covers that year.
        <template v-if="!paidThisYear"> Please contact the secretariat to renew.</template>
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

const route = useRoute()
const { member, withXsrf } = useMember()
const { fetchMemberPayments, startRenewal, fetchRenewal } = useApi()
const { data, refresh } = await useAsyncData('member-payments', () => fetchMemberPayments().catch(() => null))
const payments = computed<MembershipReceipt[]>(() => data.value?.payments || [])
const standing = computed(() => member.value?.standing ?? null)
const paidThisYear = computed(() => !!member.value?.paid_year && member.value.paid_year >= member.value.current_year)

const tone = computed(() => {
  switch (standing.value?.state) {
    case 'paid':
    case 'exempt':
      return { box: 'bg-green-50 border-green-200 text-green-900' }
    case 'due':
      return { box: 'bg-white border-gray-200 text-gray-900' }
    case 'overdue':
      return { box: 'bg-amber-50 border-amber-200 text-amber-900' }
    default:
      return { box: 'bg-red-50 border-red-200 text-red-900' }
  }
})

// Renew now: off to the payment page, back here with ?renewal=
const renewing = ref(false)
const renewError = ref('')
const renew = async () => {
  renewing.value = true
  renewError.value = ''
  try {
    const { payment_url } = await withXsrf(() => startRenewal())
    window.location.href = payment_url
  } catch (e) {
    renewError.value = apiErrorMessage(e)
    renewing.value = false
  }
}

const returned = ref<Awaited<ReturnType<typeof fetchRenewal>> | null>(null)
const checkReturn = async () => {
  const reference = String(route.query.renewal || '')
  if (!reference) return
  try {
    returned.value = await fetchRenewal(reference)
    member.value = returned.value.member
    await refresh()
  } catch {
    returned.value = null
  }
}
onMounted(checkReturn)
</script>
