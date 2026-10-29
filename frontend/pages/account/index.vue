<template>
  <AccountShell :title="`Welcome, ${member?.name}`">
    <div
      v-if="member && !member.is_active"
      class="mb-6 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 p-4 text-sm"
      role="status"
    >
      <strong>Your membership is {{ member.status_label.toLowerCase() }}.</strong>
      You can still sign in, but members-only pages are closed to you until it is active again. Please contact the secretariat.
    </div>

    <div class="grid gap-6 md:grid-cols-3">
      <!-- Membership card -->
      <section class="md:col-span-2 bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Your membership</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs text-gray-500">Member ID</dt>
            <dd class="text-lg font-semibold font-mono text-gray-900">{{ member?.member_number || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500">Category</dt>
            <dd class="text-lg font-semibold text-gray-900">{{ member?.membership_type || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500">Status</dt>
            <dd><AccountStatusBadge :member="member" /></dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500">Member since</dt>
            <dd class="text-gray-900">{{ formatDate(member?.member_since) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500">Membership {{ member?.current_year }}</dt>
            <dd :class="paidThisYear ? 'text-green-700' : 'text-amber-700'" class="font-medium">
              {{ paidThisYear ? 'Paid' : 'Not paid yet' }}
              <NuxtLink to="/account/payments" class="ml-1 text-sm font-normal text-primary hover:underline">Payments</NuxtLink>
            </dd>
          </div>
        </dl>
        <NuxtLink to="/account/membership" class="inline-block mt-6 text-sm font-medium text-primary hover:underline">See membership details &rarr;</NuxtLink>
      </section>

      <!-- Quick links -->
      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Quick links</h2>
        <ul class="space-y-3 text-sm">
          <li><NuxtLink to="/account/profile" class="text-primary hover:underline">Update my profile</NuxtLink></li>
          <li><NuxtLink to="/account/payments" class="text-primary hover:underline">My payments and receipts</NuxtLink></li>
          <li v-if="member?.features?.documents"><NuxtLink to="/account/documents" class="text-primary hover:underline">My documents</NuxtLink></li>
          <li><NuxtLink to="/account/pages" class="text-primary hover:underline">Members' area</NuxtLink></li>
          <li><NuxtLink to="/account/password" class="text-primary hover:underline">Change password</NuxtLink></li>
          <li><NuxtLink to="/" class="text-primary hover:underline">Visit the website</NuxtLink></li>
        </ul>
      </section>
    </div>

    <!-- Members-only pages -->
    <section v-if="member?.is_active" class="mt-6 bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Members' area</h2>
      <AccountPageList :pages="pages" :limit="5" />
      <NuxtLink v-if="pages.length > 5" to="/account/pages" class="inline-block mt-4 text-sm font-medium text-primary hover:underline">See all {{ pages.length }} pages &rarr;</NuxtLink>
    </section>
  </AccountShell>
</template>

<script setup lang="ts">
definePageMeta({ portal: true, middleware: 'member' })
useHead({ title: 'Member portal' })

const { member } = useMember()
const { fetchMemberPages } = useApi()
const { data } = await useAsyncData('member-pages', () => fetchMemberPages().catch(() => ({ pages: [] })))
const pages = computed(() => data.value?.pages || [])
const paidThisYear = computed(() => !!member.value?.paid_year && member.value.paid_year >= member.value.current_year)
</script>
