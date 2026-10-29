<template>
  <AccountShell title="Membership details">
    <section class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
      <div v-for="row in rows" :key="row.label" class="grid sm:grid-cols-3 gap-1 sm:gap-4 px-6 py-4">
        <dt class="text-sm text-gray-500">{{ row.label }}</dt>
        <dd class="sm:col-span-2 text-gray-900" :class="row.mono ? 'font-mono' : ''">
          <AccountStatusBadge v-if="row.badge" :member="member" />
          <template v-else>{{ row.value || '—' }}</template>
        </dd>
      </div>
    </section>
    <p class="mt-4 text-sm text-gray-500">To change your name, email or membership category, please contact the secretariat.</p>
  </AccountShell>
</template>

<script setup lang="ts">
definePageMeta({ portal: true, middleware: 'member' })
useHead({ title: 'Membership details' })

const { member } = useMember()
const rows = computed(() => [
  { label: 'Name', value: member.value?.name },
  { label: 'Member ID', value: member.value?.member_number, mono: true },
  { label: 'Category', value: member.value?.membership_type },
  { label: 'Status', badge: true },
  { label: 'Member since', value: formatDate(member.value?.member_since) },
  {
    label: `Membership ${member.value?.current_year}`,
    value: member.value?.paid_year && member.value.paid_year >= member.value.current_year ? 'Paid' : 'Not paid yet',
  },
  { label: 'Sign-in email', value: member.value?.email },
])
</script>
