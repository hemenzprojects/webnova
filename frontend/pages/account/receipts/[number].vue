<template>
  <MembershipReceiptPage :receipt="receipt" back-to="/account/payments" back-label="Back to my payments" :pdf-url="`/api/v1/membership/account/payments/${encodeURIComponent(String(route.params.number))}/pdf`" />
</template>

<script setup lang="ts">
definePageMeta({ portal: true, middleware: 'member' })

const route = useRoute()
const { fetchMemberReceipt } = useApi()
const { data: receipt } = await useAsyncData(
  () => `member-receipt-${route.params.number}`,
  async () => (await fetchMemberReceipt(String(route.params.number)).catch(() => null))?.receipt ?? null
)
useHead({ title: () => (receipt.value ? `Receipt ${receipt.value.receipt_number}` : 'Receipt') })
</script>
