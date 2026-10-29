<template>
  <section class="py-16 md:py-24" :style="{ backgroundColor: 'var(--color-surface, #fff)' }">
    <div class="container mx-auto px-4 max-w-xl">
      <div
        class="text-center px-6 py-12"
        :style="{ backgroundColor: 'var(--color-surface-muted, #f3f4f6)', borderRadius: 'var(--radius-card, 12px)' }"
      >
        <p v-if="pending" :style="{ color: 'var(--color-text-muted)' }">Checking your payment…</p>

        <template v-else-if="!registration">
          <h1 class="text-2xl mb-3" :style="{ fontFamily: 'var(--font-heading)', color: 'var(--color-text)' }">Registration not found</h1>
          <p :style="{ color: 'var(--color-text-muted)' }">We could not find this registration. If you have paid, please contact us with your payment receipt.</p>
        </template>

        <template v-else>
          <ThemeIcon :name="isPaid ? 'check' : 'clock'" class="w-14 h-14 mx-auto mb-4 text-primary" />
          <h1 class="text-2xl md:text-3xl mb-3" :style="{ fontFamily: 'var(--font-heading)', color: 'var(--color-text)' }">
            {{ heading }}
          </h1>
          <p class="mb-6" :style="{ color: 'var(--color-text-muted)' }">
            <template v-if="isPaid">{{ registration.message }}</template>
            <template v-else-if="registration.payment_status === 'failed'">Your payment did not go through. No money was taken; you can try again.</template>
            <template v-else>We have not received your payment yet. If you have just paid, it can take a moment: check again shortly.</template>
          </p>

          <!-- Paid: the receipt, with the new Member ID -->
          <div v-if="receipt" class="text-left bg-white border border-gray-200 rounded-lg p-5 mb-6 text-sm">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
              <div v-if="receipt.member_id" class="col-span-2">
                <dt class="text-xs uppercase tracking-wider text-gray-500">Your Member ID</dt>
                <dd class="font-mono text-2xl font-semibold text-gray-900">{{ receipt.member_id }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500">Receipt number</dt>
                <dd class="font-mono text-gray-900">{{ receipt.receipt_number }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500">Amount paid</dt>
                <dd class="text-gray-900">{{ receipt.currency }} {{ receipt.amount.toFixed(2) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500">Membership</dt>
                <dd class="text-gray-900">{{ receipt.membership_type || '—' }}, {{ receipt.year }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500">Date paid</dt>
                <dd class="text-gray-900">{{ formatDate(receipt.paid_at) }}</dd>
              </div>
            </dl>
            <p class="mt-4 text-gray-600">We have emailed this receipt (PDF) to {{ receipt.email }}<template v-if="receipt.member_id">, with a link to set your password for the member portal</template>.</p>
          </div>
          <div v-if="receipt" class="flex flex-wrap justify-center gap-3">
            <NuxtLink
              :to="`/membership/receipt?reference=${encodeURIComponent(registration.reference)}`"
              class="px-5 py-2.5 text-white bg-primary"
              :style="{ borderRadius: 'var(--radius-button, 8px)' }"
            >
              View or print receipt
            </NuxtLink>
            <a
              :href="`/api/v1/membership/registrations/${encodeURIComponent(registration.reference)}/receipt`"
              class="px-5 py-2.5 border"
              :style="{ borderRadius: 'var(--radius-button, 8px)', borderColor: 'var(--color-primary)', color: 'var(--color-primary)' }"
            >
              Download receipt (PDF)
            </a>
          </div>

          <dl v-else class="text-sm mb-8 space-y-1" :style="{ color: 'var(--color-text-muted)' }">
            <div>Reference: <strong class="font-mono" :style="{ color: 'var(--color-text)' }">{{ registration.reference }}</strong></div>
            <div v-if="registration.membership_type">Membership: {{ registration.membership_type }}</div>
            <div v-if="registration.amount > 0">Amount: {{ registration.currency }} {{ registration.amount.toFixed(2) }}</div>
          </dl>

          <div v-if="!isPaid" class="flex flex-wrap justify-center gap-3">
            <button
              type="button"
              class="px-5 py-2.5 border"
              :style="{ borderRadius: 'var(--radius-button, 8px)', borderColor: 'var(--color-primary)', color: 'var(--color-primary)' }"
              @click="refresh()"
            >
              Check again
            </button>
            <button
              type="button"
              :disabled="retrying"
              class="px-5 py-2.5 text-white bg-primary disabled:opacity-60"
              :style="{ borderRadius: 'var(--radius-button, 8px)' }"
              @click="retry"
            >
              {{ retrying ? 'Please wait…' : 'Pay now' }}
            </button>
          </div>
          <p v-if="retryError" class="mt-4 text-sm text-red-600">{{ retryError }}</p>
        </template>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
// The payment provider (Paystack or Stanbic) returns applicants here after paying (Membership plugin)
const route = useRoute()
const reference = computed(() => String(route.query.reference || ''))
const { fetchMembershipStatus, retryMembershipPayment } = useApi()

const { data: registration, pending, refresh } = await useAsyncData(
  () => `membership-status-${reference.value}`,
  async () => {
    if (!reference.value) return null
    try {
      return (await fetchMembershipStatus(reference.value)) as any
    } catch {
      return null
    }
  }
)

const receipt = computed(() => registration.value?.receipt ?? null)
const isPaid = computed(() => ['paid', 'not_required'].includes(registration.value?.payment_status))
const heading = computed(() => (isPaid.value ? 'Thank you!' : registration.value?.payment_status === 'failed' ? 'Payment not completed' : 'Waiting for payment'))

const retrying = ref(false)
const retryError = ref('')
const retry = async () => {
  retrying.value = true
  retryError.value = ''
  try {
    const result: any = await retryMembershipPayment(reference.value)
    if (result.payment_url) {
      window.location.href = result.payment_url
    } else {
      retryError.value = 'Online payment is not available right now. Please contact us to pay another way.'
    }
  } catch {
    retryError.value = 'Payment could not be started. Please try again in a moment.'
  } finally {
    retrying.value = false
  }
}

useHead({ title: 'Membership registration' })
</script>
