<template>
  <AccountShell title="Members' area">
    <section class="bg-white rounded-xl border border-gray-200 p-6">
      <p v-if="!member?.is_active" class="text-sm text-gray-600">
        Members-only pages open again once your membership is active.
      </p>
      <AccountPageList v-else :pages="pages" />
    </section>
  </AccountShell>
</template>

<script setup lang="ts">
definePageMeta({ portal: true, middleware: 'member' })
useHead({ title: "Members' area" })

const { member } = useMember()
const { fetchMemberPages } = useApi()
const { data } = await useAsyncData('member-pages', () => fetchMemberPages().catch(() => ({ pages: [] })))
const pages = computed(() => data.value?.pages || [])
</script>
