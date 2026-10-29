<template>
  <!-- The site's privacy notice is new or has changed since the member agreed to it -->
  <div class="rounded-xl border border-blue-200 bg-blue-50 text-blue-950 p-4 text-sm flex flex-col sm:flex-row sm:items-center gap-3" role="status">
    <p class="flex-1">
      <strong>Our privacy notice has been updated.</strong>
      Please read how we use and keep your information, and confirm that you agree.
    </p>
    <div class="flex gap-2 shrink-0">
      <a href="/membership/privacy" target="_blank" rel="noopener" class="px-4 py-2 rounded-lg border border-blue-300 hover:bg-blue-100">Read it</a>
      <button type="button" :disabled="busy" class="px-4 py-2 rounded-lg bg-primary text-white font-semibold disabled:opacity-60" @click="agree">
        {{ busy ? 'Please wait…' : 'I agree' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
const { consent } = useMember()
const busy = ref(false)

const agree = async () => {
  busy.value = true
  try {
    await consent()
  } finally {
    busy.value = false
  }
}
</script>
