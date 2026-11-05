<template>
  <button
    type="submit"
    :disabled="busy"
    class="w-full bg-primary text-white font-semibold px-5 py-3 rounded-lg hover:opacity-90 transition disabled:opacity-60"
  >
    {{ busy ? 'Please wait…' : label }}
  </button>
</template>

<script setup lang="ts">
defineProps<{ label: string; busy?: boolean }>()
</script>
