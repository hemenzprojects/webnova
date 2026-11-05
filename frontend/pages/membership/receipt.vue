<template>
  <MembershipReceiptPage :receipt="receipt" :back-to="`/membership/complete?reference=${encodeURIComponent(reference)}`" back-label="Back" :pdf-url="`/api/v1/membership/registrations/${encodeURIComponent(reference)}/receipt`" />
</template>

<script setup lang="ts">
// The receipt of an application's payment, opened from the page the payment provider returns to
definePageMeta({ portal: true })

const route = useRoute()
const reference = computed(() => String(route.query.reference || ''))
const { fetchMembershipStatus } = useApi()
const { data: receipt } = await useAsyncData(
  () => `membership-receipt-${reference.value}`,
  async () => (reference.value ? (((await fetchMembershipStatus(reference.value).catch(() => null)) as any)?.receipt ?? null) : null)
)
useHead({ title: () => (receipt.value ? `Receipt ${receipt.value.receipt_number}` : 'Receipt') })
</script>
