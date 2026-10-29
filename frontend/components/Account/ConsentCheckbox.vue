<template>
  <!-- "I agree to the privacy notice", where the site has one -->
  <label class="flex items-start gap-3 text-sm" :style="{ color: 'var(--color-text, #111827)' }">
    <input :checked="modelValue" type="checkbox" class="mt-0.5 rounded border-gray-300" @change="$emit('update:modelValue', ($event.target as HTMLInputElement).checked)">
    <span>
      I have read and agree to the
      <a href="/membership/privacy" target="_blank" rel="noopener" class="underline text-primary">privacy notice</a>,
      which explains how my information is used and how long it is kept.
    </span>
  </label>
  <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
</template>

<script setup lang="ts">
defineProps<{ modelValue: boolean; error?: string }>()
defineEmits<{ 'update:modelValue': [value: boolean] }>()
</script>
