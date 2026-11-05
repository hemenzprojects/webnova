<template>
  <label class="block mb-4">
    <span class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</span>
    <select
      :value="modelValue ?? ''"
      :class="['w-full rounded-lg border px-3.5 py-2.5 text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-primary', error ? 'border-red-500' : 'border-gray-300']"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value || null)"
    >
      <option value="">—</option>
      <option v-for="option in normalised" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
    <span v-if="error" class="block text-sm text-red-600 mt-1">{{ error }}</span>
  </label>
</template>

<script setup lang="ts">
const props = defineProps<{ label: string; modelValue: string | null; options: (string | { value: string; label: string })[]; error?: string }>()
defineEmits<{ 'update:modelValue': [value: string | null] }>()

const normalised = computed(() => props.options.map((o) => (typeof o === 'string' ? { value: o, label: o } : o)))
</script>
